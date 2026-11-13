<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Fournit les opérations de lecture et de création des activités complémentaires.
 */
class ACModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllAC() {
        $sql = "SELECT ac.*, GROUP_CONCAT(DISTINCT CONCAT(v.nom_vis, ' ', v.prenom_vis) SEPARATOR ', ') AS organisateurs 
                FROM AC ac
                LEFT JOIN Organiser o ON ac.id_ac = o.id_ac
                LEFT JOIN Visiteur v ON o.id_vis = v.id_vis
                GROUP BY ac.id_ac
                ORDER BY ac.date_ac DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function createAC($theme, $lieu, $date, $organisateurs) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO AC (theme, lieu, date_ac) VALUES (:theme, :lieu, :date_ac)");
            $stmt->execute(['theme' => $theme, 'lieu' => $lieu, 'date_ac' => $date]);
            $idAc = $this->db->lastInsertId();

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
