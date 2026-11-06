<?php
require_once __DIR__ . '/../config/database.php';

class ActiviteComplementaire {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllActivites() {
        $sql = "SELECT ac.*, r.nom AS region_nom 
                FROM activite_complementaire ac 
                JOIN region r ON ac.id_region = r.id 
                ORDER BY ac.date_ac DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

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
