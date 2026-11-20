<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Gère les données des praticiens, leurs spécialités et leurs comptes de connexion.
 */
class Practicien {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllSpecialites() {
        return $this->db->query("SELECT id_spe, nom_spe FROM Specialite ORDER BY nom_spe")->fetchAll();
    }

    /**
     * Récupère les régions existantes pour les proposer lors de l'inscription d'un praticien.
     */
    public function getAllRegions() {
        return $this->db->query("SELECT reg_code FROM Region ORDER BY reg_code")->fetchAll();
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
        $stmt = $this->db->prepare("SELECT p.id_spe, p.Id_pra, p.note_par, p.nom_par, p.prenom_par, p.mail_par, p.rue_par, p.codePostal, p.ville_par, s.nom_spe,
                                    (SELECT a.reg_code FROM affecter a WHERE a.id_spe = p.id_spe AND a.Id_pra = p.Id_pra ORDER BY a.dateArrivee DESC LIMIT 1) AS reg_code
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

    public function createPracticien($idSpe, $nouvelleSpecialite, $nom, $prenom, $rue, $codePostal, $ville, $email, $password, $regCode = null) {
        try {
            $this->db->beginTransaction();

            if ($regCode !== null && $regCode !== '') {
                $stmtRegion = $this->db->prepare("SELECT reg_code FROM Region WHERE reg_code = :reg_code LIMIT 1");
                $stmtRegion->execute(['reg_code' => $regCode]);
                $regionExistante = $stmtRegion->fetchColumn();

                if ($regionExistante === false) {
                    $stmtRegion = $this->db->prepare("INSERT INTO Region (reg_code) VALUES (:reg_code)");
                    $stmtRegion->execute(['reg_code' => $regCode]);
                } else {
                    $regCode = $regionExistante;
                }
            }

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
                "INSERT INTO Practicien (id_spe, nom_par, prenom_par, rue_par, codePostal, ville_par, mail_par, mot_de_passe_par, statut_validation)
                 VALUES (:id_spe, :nom, :prenom, :rue, :code_postal, :ville, :email, :password, 'en_attente')"
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
            $idPra = $this->db->lastInsertId();

            if ($regCode !== null && $regCode !== '') {
                $stmtRegion = $this->db->prepare("INSERT INTO affecter (id_spe, Id_pra, reg_code, dateArrivee) VALUES (:id_spe, :id_pra, :reg_code, CURDATE())");
                $stmtRegion->execute([
                    'id_spe' => $idSpe,
                    'id_pra' => $idPra,
                    'reg_code' => $regCode
                ]);
            }

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

    public function getPendingForApproval() {
        $sql = "SELECT p.id_spe, p.Id_pra, p.nom_par, p.prenom_par, p.mail_par, p.rue_par, p.codePostal, p.ville_par, s.nom_spe
                FROM Practicien p
                JOIN Specialite s ON p.id_spe = s.id_spe
                WHERE p.statut_validation = 'en_attente'
                ORDER BY p.nom_par, p.prenom_par";
        return $this->db->query($sql)->fetchAll();
    }

    public function setPendingApprovalStatus($idSpe, $idPra, $status) {
        if (!in_array($status, ['approuve', 'refuse'], true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE Practicien SET statut_validation = :status
             WHERE id_spe = :id_spe AND Id_pra = :id_pra AND statut_validation = 'en_attente'"
        );
        $stmt->execute(['status' => $status, 'id_spe' => $idSpe, 'id_pra' => $idPra]);
        return $stmt->rowCount() === 1;
    }
}
