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

    public function verifyPassword($hash, $password) {
        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    public function getAllVisiteurs() {
        $stmt = $this->db->query("SELECT v.id_vis, v.telephone_vis, v.nom_vis, v.prenom_vis, v.mail_vis, v.adresse_vis, 
                                  (SELECT t.reg_code FROM Travailler t WHERE t.id_vis = v.id_vis ORDER BY t.dateaffect DESC LIMIT 1) AS reg_code
                                  FROM Visiteur v 
                                  ORDER BY v.nom_vis, v.prenom_vis");
        return $stmt->fetchAll();
    }

    public function getAllRegions() {
        return $this->db->query("SELECT * FROM Region ORDER BY reg_code ASC")->fetchAll();
    }

    public function getVisiteurById($idVis) {
        $stmt = $this->db->prepare("SELECT v.id_vis, v.telephone_vis, v.nom_vis, v.prenom_vis, v.mail_vis, v.adresse_vis, 
                                    (SELECT t.reg_code FROM Travailler t WHERE t.id_vis = v.id_vis ORDER BY t.dateaffect DESC LIMIT 1) AS reg_code
                                    FROM Visiteur v 
                                    WHERE v.id_vis = :id 
                                    LIMIT 1");
        $stmt->execute(['id' => $idVis]);
        return $stmt->fetch();
    }

    /**
     * Modifie les informations d'un visiteur. $password null = mot de passe inchangé.
     * Une nouvelle affectation (Travailler) est enregistrée si la région change.
     */
    public function updateVisiteur($idVis, $nom, $prenom, $email, $telephone, $adresse, $regCode, $password = null) {
        try {
            $this->db->beginTransaction();

            if ($password === null) {
                $stmt = $this->db->prepare("UPDATE Visiteur SET nom_vis = :nom, prenom_vis = :prenom, mail_vis = :email, telephone_vis = :tel, adresse_vis = :adresse WHERE id_vis = :id");
                $params = ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'tel' => $telephone, 'adresse' => $adresse, 'id' => $idVis];
            } else {
                $stmt = $this->db->prepare("UPDATE Visiteur SET nom_vis = :nom, prenom_vis = :prenom, mail_vis = :email, telephone_vis = :tel, adresse_vis = :adresse, mot_de_passe_vis = :password WHERE id_vis = :id");
                $params = ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'tel' => $telephone, 'adresse' => $adresse, 'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $idVis];
            }
            $stmt->execute($params);

            if (!empty($regCode)) {
                $stmtCur = $this->db->prepare("SELECT reg_code FROM Travailler WHERE id_vis = :id ORDER BY dateaffect DESC LIMIT 1");
                $stmtCur->execute(['id' => $idVis]);
                $regionActuelle = $stmtCur->fetchColumn();

                if ($regionActuelle !== $regCode) {
                    // Une seule affectation par jour : on remplace celle du jour si elle existe déjà
                    // (sinon deux lignes à date identique rendraient "la région courante" ambiguë).
                    $stmtJour = $this->db->prepare("SELECT reg_code FROM Travailler WHERE id_vis = :id AND dateaffect = CURDATE() LIMIT 1");
                    $stmtJour->execute(['id' => $idVis]);
                    $regionDuJour = $stmtJour->fetchColumn();

                    if ($regionDuJour !== false && $regionDuJour !== $regCode) {
                        $stmtRemplace = $this->db->prepare("DELETE FROM Travailler WHERE id_vis = :id AND dateaffect = CURDATE() AND reg_code <> :reg");
                        $stmtRemplace->execute(['id' => $idVis, 'reg' => $regCode]);
                    }

                    $stmtExiste = $this->db->prepare("SELECT COUNT(*) FROM Travailler WHERE id_vis = :id AND reg_code = :reg AND dateaffect = CURDATE()");
                    $stmtExiste->execute(['id' => $idVis, 'reg' => $regCode]);
                    if (!$stmtExiste->fetchColumn()) {
                        $stmtAff = $this->db->prepare("INSERT INTO Travailler (id_vis, reg_code, dateaffect) VALUES (:id_vis, :reg_code, CURDATE())");
                        $stmtAff->execute(['id_vis' => $idVis, 'reg_code' => $regCode]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Échec de modification du visiteur : ' . $e->getMessage());
            return false; 
        }
    }

    public function createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode, $password) {
        try {
            $this->db->beginTransaction();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO Visiteur (nom_vis, prenom_vis, mail_vis, telephone_vis, adresse_vis, mot_de_passe_vis) VALUES (:nom, :prenom, :email, :tel, :adresse, :password)");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'tel' => $telephone,
                'adresse' => $adresse,
                'password' => $hashedPassword
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
