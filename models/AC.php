<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle des activités complémentaires (ancien schéma) : couvre les tables SQL
 * « AC » et « Organiser » (liens avec les visiteurs organisateurs).
 * Propose la lecture de toutes les activités avec leurs organisateurs agrégés
 * et la création d'une activité ainsi que l'enregistrement de ses organisateurs.
 * La création est réalisée dans une transaction (activité + lignes Organiser).
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
}
