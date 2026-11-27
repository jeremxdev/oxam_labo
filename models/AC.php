<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle des activités complémentaires (ancien schéma) : couvre les tables SQL
 * « AC » et « Organiser » (liens avec les visiteurs organisateurs).
 * Propose la lecture de toutes les activités avec leurs organisateurs agrégés,
 * la lecture d'une activité avec ses organisateurs (pour l'édition), la création,
 * la modification et la suppression d'une activité.
 * Les écritures sont réalisées dans une transaction (activité + lignes Organiser,
 * avec nettoyage de « participe » à la suppression).
 */
class ACModel {
    private $db;

    /**
     * Initialise la connexion à la base de données utilisée par les méthodes du modèle.
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Liste toutes les activités complémentaires, de la plus récente à la plus ancienne,
     * avec la liste concaténée de leurs organisateurs.
     * @return array Activités enrichies du champ « organisateurs » (noms séparés par des virgules).
     */
    public function getAllAC() {
        // Agrégation : LEFT JOIN + GROUP_CONCAT pour obtenir, par activité, la liste
        // des visiteurs organisateurs (noms concaténés) même si aucun n'est enregistré.
        $sql = "SELECT ac.*, GROUP_CONCAT(DISTINCT CONCAT(v.nom_vis, ' ', v.prenom_vis) SEPARATOR ', ') AS organisateurs 
                FROM AC ac
                LEFT JOIN Organiser o ON ac.id_ac = o.id_ac
                LEFT JOIN Visiteur v ON o.id_vis = v.id_vis
                GROUP BY ac.id_ac
                ORDER BY ac.date_ac DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Crée une activité complémentaire et enregistre ses organisateurs.
     * @param string $theme Thème de l'activité.
     * @param string $lieu Lieu de déroulement.
     * @param string $date Date de l'activité (format attendu par la colonne date_ac).
     * @param array $organisateurs Liste d'identifiants de visiteurs organisateurs (peut être vide).
     * @return bool true si la création a réussi, false en cas d'erreur (rollback).
     * Effets de bord : INSERT dans AC puis, pour chaque organisateur, INSERT dans Organiser ;
     * l'ensemble est réalisé dans une transaction annulée en cas d'exception.
     */
    public function createAC($theme, $lieu, $date, $organisateurs) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO AC (theme, lieu, date_ac) VALUES (:theme, :lieu, :date_ac)");
            $stmt->execute(['theme' => $theme, 'lieu' => $lieu, 'date_ac' => $date]);
            $idAc = $this->db->lastInsertId();

            // Une ligne par visiteur organisateur, liée à l'activité créée.
            if (!empty($organisateurs)) {
                $stmtOrg = $this->db->prepare("INSERT INTO Organiser (id_vis, id_ac) VALUES (:id_vis, :id_ac)");
                foreach ($organisateurs as $idVis) {
                    $stmtOrg->execute(['id_vis' => $idVis, 'id_ac' => $idAc]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Récupère une activité complémentaire et les identifiants de ses organisateurs.
     *
     * Utilisée par l'administration pour préremplir le formulaire de modification.
     * @param int $id Identifiant de l'activité (colonne id_ac).
     * @return array|false Ligne de l'activité avec « organisateur_ids » (ids séparés par des virgules,
     *                      ou null si aucun organisateur), ou false si elle n'existe pas.
     */
    public function getACById($id) {
        $stmt = $this->db->prepare(
            "SELECT ac.*, GROUP_CONCAT(o.id_vis) AS organisateur_ids
             FROM AC ac
             LEFT JOIN Organiser o ON ac.id_ac = o.id_ac
             WHERE ac.id_ac = :id
             GROUP BY ac.id_ac"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Met à jour une activité complémentaire et remplace intégralement ses organisateurs.
     *
     * @param int $id Identifiant de l'activité à modifier.
     * @param string $theme Nouveau thème (20 caractères maximum en base).
     * @param string $lieu Nouveau lieu (50 caractères maximum en base).
     * @param string $date Nouvelle date au format YYYY-MM-DD.
     * @param array $organisateurs Liste d'identifiants de visiteurs organisateurs (peut être vide).
     * @return bool true si la mise à jour a réussi, false si l'activité est absente ou en cas d'erreur.
     * Effets de bord : UPDATE de AC, DELETE puis INSERT dans Organiser, le tout dans une transaction.
     */
    public function updateAC($id, $theme, $lieu, $date, $organisateurs) {
        // On refuse la transaction si l'activité n'existe pas (évite une mise à jour fictive).
        $check = $this->db->prepare("SELECT 1 FROM AC WHERE id_ac = :id");
        $check->execute(['id' => $id]);
        if (!$check->fetchColumn()) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE AC SET theme = :theme, lieu = :lieu, date_ac = :date_ac WHERE id_ac = :id"
            );
            $stmt->execute([
                'theme' => $theme,
                'lieu' => $lieu,
                'date_ac' => $date,
                'id' => $id
            ]);

            // La liste soumise remplace l'ancienne : on efface les anciennes lignes.
            $this->db->prepare("DELETE FROM Organiser WHERE id_ac = :id")->execute(['id' => $id]);

            if (!empty($organisateurs)) {
                $stmtOrg = $this->db->prepare(
                    "INSERT INTO Organiser (id_vis, id_ac) VALUES (:id_vis, :id_ac)"
                );
                foreach ($organisateurs as $idVis) {
                    $stmtOrg->execute(['id_vis' => $idVis, 'id_ac' => $id]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Supprime définitivement une activité complémentaire et ses dépendances.
     *
     * @param int $id Identifiant de l'activité à supprimer.
     * @return bool true si l'activité existait et a été supprimée, false sinon.
     * Effets de bord : suppression dans « participe » et « Organiser » avant « AC »,
     * dans une transaction, pour respecter les clés étrangères.
     */
    public function deleteAC($id) {
        $check = $this->db->prepare("SELECT 1 FROM AC WHERE id_ac = :id");
        $check->execute(['id' => $id]);
        if (!$check->fetchColumn()) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // Les tables filles doivent être vidées avant la table mère (clés étrangères).
            $this->db->prepare("DELETE FROM participe WHERE id_ac = :id")->execute(['id' => $id]);
            $this->db->prepare("DELETE FROM Organiser WHERE id_ac = :id")->execute(['id' => $id]);
            $this->db->prepare("DELETE FROM AC WHERE id_ac = :id")->execute(['id' => $id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
