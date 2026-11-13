<?php
require_once __DIR__ . '/../config/database.php';

class Practicien {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllSpecialites() {
        return $this->db->query("SELECT id_spe, nom_spe FROM Specialite ORDER BY nom_spe")->fetchAll();
    }

    public function findByMail($email) {
        $stmt = $this->db->prepare("SELECT p.*, s.nom_spe 
                                    FROM Practicien p 
                                    LEFT JOIN Specialite s ON p.id_spe = s.id_spe 
                                    WHERE p.mail_par = :email 
                                    LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function verifyPassword($hash, $password) {
        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    public function getPracticienById($idSpe, $idPra) {
        $stmt = $this->db->prepare("SELECT p.id_spe, p.Id_pra, p.note_par, p.nom_par, p.prenom_par, p.mail_par, p.rue_par, p.codePostal, p.ville_par, s.nom_spe 
                                    FROM Practicien p 
                                    LEFT JOIN Specialite s ON p.id_spe = s.id_spe 
                                    WHERE p.id_spe = :id_spe AND p.Id_pra = :id_pra 
                                    LIMIT 1");
        $stmt->execute(['id_spe' => $idSpe, 'id_pra' => $idPra]);
        return $stmt->fetch();
    }

    /**
     * Modifie les informations d'un praticien. $password null = mot de passe inchangé.
     * La spécialité (id_spe) n'est pas modifiable : elle fait partie de la clé liant les visites.
     */
    public function updatePracticien($idPra, $nom, $prenom, $email, $rue, $codePostal, $ville, $password = null) {
        try {
            $this->db->beginTransaction();

            if ($password === null) {
                $stmt = $this->db->prepare("UPDATE Practicien SET nom_par = :nom, prenom_par = :prenom, mail_par = :email, rue_par = :rue, codePostal = :code_postal, ville_par = :ville WHERE Id_pra = :id");
                $params = ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'rue' => $rue, 'code_postal' => $codePostal, 'ville' => $ville, 'id' => $idPra];
            } else {
                $stmt = $this->db->prepare("UPDATE Practicien SET nom_par = :nom, prenom_par = :prenom, mail_par = :email, rue_par = :rue, codePostal = :code_postal, ville_par = :ville, mot_de_passe_par = :password WHERE Id_pra = :id");
                $params = ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'rue' => $rue, 'code_postal' => $codePostal, 'ville' => $ville, 'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $idPra];
            }
            $stmt->execute($params);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Échec de modification du praticien : ' . $e->getMessage());
            return false;
        }
    }

    public function createPracticien($idSpe, $nouvelleSpecialite, $nom, $prenom, $rue, $codePostal, $ville, $email, $password) {
        try {
            $this->db->beginTransaction();

            if ($idSpe === null) {
                $stmtSpecialite = $this->db->prepare("SELECT id_spe FROM Specialite WHERE nom_spe = :nom LIMIT 1");
                $stmtSpecialite->execute(['nom' => $nouvelleSpecialite]);
                $idSpe = $stmtSpecialite->fetchColumn();

                if (!$idSpe) {
                    $stmtSpecialite = $this->db->prepare("INSERT INTO Specialite (nom_spe) VALUES (:nom)");
                    $stmtSpecialite->execute(['nom' => $nouvelleSpecialite]);
                    $idSpe = $this->db->lastInsertId();
                }
            }

            $stmt = $this->db->prepare(
                "INSERT INTO Practicien (id_spe, nom_par, prenom_par, rue_par, codePostal, ville_par, mail_par, mot_de_passe_par)
                 VALUES (:id_spe, :nom, :prenom, :rue, :code_postal, :ville, :email, :password)"
            );
            $created = $stmt->execute([
                'id_spe' => $idSpe,
                'nom' => $nom,
                'prenom' => $prenom,
                'rue' => $rue,
                'code_postal' => $codePostal,
                'ville' => $ville,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            $this->db->commit();
            return $created;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Échec de création du profil praticien : ' . $e->getMessage());
            return false;
        }
    }
}
