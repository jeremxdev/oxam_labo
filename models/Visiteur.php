<?php
require_once __DIR__ . '/../config/database.php';

class Visiteur {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByMail($email) {
        $stmt = $this->db->prepare("SELECT v.*, t.reg_code 
                                    FROM Visiteur v 
                                    LEFT JOIN Travailler t ON v.id_vis = t.id_vis 
                                    WHERE v.mail_vis = :email 
                                    ORDER BY t.dateaffect DESC LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function getAllVisiteurs() {
        $stmt = $this->db->query("SELECT v.*, t.reg_code 
                                  FROM Visiteur v 
                                  LEFT JOIN Travailler t ON v.id_vis = t.id_vis 
                                  GROUP BY v.id_vis 
                                  ORDER BY v.nom_vis, v.prenom_vis");
        return $stmt->fetchAll();
    }

    public function getAllRegions() {
        return $this->db->query("SELECT * FROM Region ORDER BY reg_code ASC")->fetchAll();
    }

    public function createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO Visiteur (nom_vis, prenom_vis, mail_vis, telephone_vis, adresse_vis) VALUES (:nom, :prenom, :email, :tel, :adresse)");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'tel' => $telephone,
                'adresse' => $adresse
            ]);
            $idVis = $this->db->lastInsertId();

            if (!empty($regCode)) {
                $stmtAff = $this->db->prepare("INSERT INTO Travailler (id_vis, reg_code, dateaffect) VALUES (:id_vis, :reg_code, CURDATE())");
                $stmtAff->execute(['id_vis' => $idVis, 'reg_code' => $regCode]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Échec de création du visiteur : ' . $e->getMessage());
            return false;
        }
    }
}
