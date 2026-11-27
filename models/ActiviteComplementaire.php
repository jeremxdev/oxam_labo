<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle des activités complémentaires (nouveau schéma) : couvre les tables SQL
 * « activite_complementaire », « region » et « ac_organisateur ».
 * Propose la lecture de toutes les activités avec leur région d'affectation et
 * la création d'une activité ainsi que l'enregistrement de ses organisateurs.
 * La création est réalisée dans une transaction (activité + lignes organisateurs).
 */
class ActiviteComplementaire {
    private $db;

    /**
     * Initialise la connexion à la base de données utilisée par les méthodes du modèle.
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Liste toutes les activités complémentaires, de la plus récente à la plus ancienne,
     * avec le nom de la région à laquelle chacune est rattachée.
     * @return array Liste des activités enrichie de « region_nom ».
     */
    public function getAllActivites() {
        // JOIN interne sur region : seules les activités rattachées à une région existante sont listées.
        $sql = "SELECT ac.*, r.nom AS region_nom 
                FROM activite_complementaire ac 
                JOIN region r ON ac.id_region = r.id 
                ORDER BY ac.date_ac DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Crée une activité complémentaire, la rattache à une région et enregistre ses organisateurs.
     * @param string $theme Thème de l'activité.
     * @param string $lieu Lieu de déroulement.
     * @param string $date Date de l'activité (format attendu par la colonne date_ac).
     * @param int $idRegion Identifiant de la région d'affectation.
     * @param array $organisateursIds Liste d'identifiants de visiteurs organisateurs (peut être vide).
     * @return bool true si la création a réussi, false en cas d'erreur (rollback).
     * Effets de bord : INSERT dans activite_complementaire puis, pour chaque organisateur,
     * INSERT dans ac_organisateur ; l'ensemble est réalisé dans une transaction.
     */
    public function createActivite($theme, $lieu, $date, $idRegion, $organisateursIds) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO activite_complementaire (theme, lieu, date_ac, id_region) VALUES (:theme, :lieu, :date_ac, :id_region)");
            $stmt->execute([
                'theme' => $theme,
                'lieu' => $lieu,
                'date_ac' => $date,
                'id_region' => $idRegion
            ]);
            $acId = $this->db->lastInsertId();

            // Une ligne par visiteur organisateur, liée à l'activité créée.
            if (!empty($organisateursIds)) {
                $stmtOrg = $this->db->prepare("INSERT INTO ac_organisateur (id_ac, id_visiteur) VALUES (:id_ac, :id_visiteur)");
                foreach ($organisateursIds as $vId) {
                    $stmtOrg->execute(['id_ac' => $acId, 'id_visiteur' => $vId]);
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
