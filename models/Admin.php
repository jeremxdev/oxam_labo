<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Gère les comptes administrateur et les opérations de gestion des comptes.
 */
class Admin {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByMail($email) {
        $stmt = $this->db->prepare(
            "SELECT id_admin, mail_admin, mot_de_passe_admin, statut_compte
             FROM Administrateur WHERE mail_admin = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function isEmailReserved($email) {
        $bootstrapEmail = getenv('ADMIN_EMAIL');
        if (!is_string($bootstrapEmail) || $bootstrapEmail === '') {
            $bootstrapEmail = 'admin@oxam-labo.fr';
        }
        return (bool) $this->findByMail($email) || hash_equals($bootstrapEmail, $email);
    }

    public function authenticate($email, $password) {
        $admin = $this->findByMail($email);
        if ($admin) {
            return $admin['statut_compte'] === 'actif' &&
                password_verify($password, $admin['mot_de_passe_admin']);
        }

        if ((int) $this->db->query("SELECT COUNT(*) FROM Administrateur")->fetchColumn() !== 0) {
            return false;
        }

        $bootstrapEmail = getenv('ADMIN_EMAIL');
        if (!is_string($bootstrapEmail) || $bootstrapEmail === '') {
            $bootstrapEmail = 'admin@oxam-labo.fr';
        }
        if (!hash_equals($bootstrapEmail, $email)) {
            return false;
        }

        $bootstrapPassword = getenv('ADMIN_PASSWORD');
        if (!is_string($bootstrapPassword) ||
            !filter_var($bootstrapEmail, FILTER_VALIDATE_EMAIL) || strlen($bootstrapPassword) < 12) {
            error_log('Provisionnement admin impossible : ADMIN_EMAIL valide et ADMIN_PASSWORD de 12 caractères minimum requis.');
            return false;
        }

        if (!hash_equals($bootstrapPassword, $password)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT 1 FROM Visiteur WHERE mail_vis = :email_vis
             UNION
             SELECT 1 FROM Practicien WHERE mail_par = :email_pra
             LIMIT 1"
        );
        $stmt->execute(['email_vis' => $bootstrapEmail, 'email_pra' => $bootstrapEmail]);
        if ($stmt->fetchColumn() !== false) {
            error_log('Provisionnement admin impossible : cette adresse est déjà utilisée par un autre compte.');
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO Administrateur (mail_admin, mot_de_passe_admin) VALUES (:email, :password)"
        );
        $stmt->execute([
            'email' => $bootstrapEmail,
            'password' => password_hash($bootstrapPassword, PASSWORD_DEFAULT)
        ]);
        return true;
    }

    public function getAllAccounts() {
        $sql = "SELECT 'visiteur' AS type, id_vis AS id, NULL AS id_spe, nom_vis AS nom, prenom_vis AS prenom,
                       mail_vis AS email, NULL AS statut_validation, statut_compte
                FROM Visiteur
                UNION ALL
                SELECT 'practicien', Id_pra, id_spe, nom_par, prenom_par, mail_par, statut_validation, statut_compte
                FROM Practicien
                UNION ALL
                SELECT 'admin', id_admin, NULL, 'Administrateur', '', mail_admin, NULL, statut_compte
                FROM Administrateur
                ORDER BY nom, prenom";
        return $this->db->query($sql)->fetchAll();
    }

    public function getAccountForEdit($type, $id, $idSpe = null) {
        if ($type === 'visiteur') {
            $stmt = $this->db->prepare(
                "SELECT v.id_vis AS id, v.nom_vis AS nom, v.prenom_vis AS prenom, v.mail_vis AS email,
                        v.telephone_vis AS telephone, v.adresse_vis AS adresse, v.statut_compte,
                        (SELECT t.reg_code FROM Travailler t WHERE t.id_vis = v.id_vis ORDER BY t.dateaffect DESC LIMIT 1) AS reg_code
                 FROM Visiteur v WHERE v.id_vis = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);
        } elseif ($type === 'practicien') {
            $stmt = $this->db->prepare(
                "SELECT p.Id_pra AS id, p.id_spe, p.nom_par AS nom, p.prenom_par AS prenom,
                        p.mail_par AS email, p.rue_par AS adresse, p.codePostal AS code_postal,
                        p.ville_par AS ville, p.statut_validation, p.statut_compte
                 FROM Practicien p WHERE p.Id_pra = :id AND p.id_spe = :id_spe LIMIT 1"
            );
            $stmt->execute(['id' => $id, 'id_spe' => $idSpe]);
        } elseif ($type === 'admin') {
            $stmt = $this->db->prepare(
                "SELECT id_admin AS id, mail_admin AS email, statut_compte
                 FROM Administrateur WHERE id_admin = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);
        } else {
            return false;
        }
        return $stmt->fetch();
    }

    public function emailInUse($email, $exceptType, $exceptId) {
        $tables = [
            'visiteur' => ['Visiteur', 'mail_vis', 'id_vis'],
            'practicien' => ['Practicien', 'mail_par', 'Id_pra'],
            'admin' => ['Administrateur', 'mail_admin', 'id_admin']
        ];

        foreach ($tables as $type => $fields) {
            list($table, $emailColumn, $idColumn) = $fields;
            $sql = "SELECT 1 FROM $table WHERE $emailColumn = :email";
            $params = ['email' => $email];
            if ($type === $exceptType) {
                $sql .= " AND $idColumn <> :id";
                $params['id'] = $exceptId;
            }
            $stmt = $this->db->prepare($sql . " LIMIT 1");
            $stmt->execute($params);
            if ($stmt->fetchColumn() !== false) {
                return true;
            }
        }
        return false;
    }

    public function updateAdminEmail($id, $email) {
        $stmt = $this->db->prepare("UPDATE Administrateur SET mail_admin = :email WHERE id_admin = :id");
        $stmt->execute(['email' => $email, 'id' => $id]);
        return $stmt->rowCount() === 1 || (bool) $this->getAccountForEdit('admin', $id);
    }

    public function resetPassword($type, $id, $idSpe, $password) {
        if ($type === 'visiteur') {
            $stmt = $this->db->prepare("UPDATE Visiteur SET mot_de_passe_vis = :password WHERE id_vis = :id");
            $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
        } elseif ($type === 'practicien') {
            $stmt = $this->db->prepare(
                "UPDATE Practicien SET mot_de_passe_par = :password WHERE Id_pra = :id AND id_spe = :id_spe"
            );
            $stmt->execute([
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $id,
                'id_spe' => $idSpe
            ]);
        } elseif ($type === 'admin') {
            $stmt = $this->db->prepare("UPDATE Administrateur SET mot_de_passe_admin = :password WHERE id_admin = :id");
            $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
        } else {
            return false;
        }
        return $stmt->rowCount() === 1;
    }

    public function setApprovalStatus($idSpe, $idPra, $status) {
        if (!in_array($status, ['en_attente', 'approuve', 'refuse'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE Practicien SET statut_validation = :status WHERE id_spe = :id_spe AND Id_pra = :id_pra"
        );
        $stmt->execute(['status' => $status, 'id_spe' => $idSpe, 'id_pra' => $idPra]);
        if ($stmt->rowCount() === 1) {
            return true;
        }
        $stmt = $this->db->prepare(
            "SELECT statut_validation FROM Practicien WHERE id_spe = :id_spe AND Id_pra = :id_pra LIMIT 1"
        );
        $stmt->execute(['id_spe' => $idSpe, 'id_pra' => $idPra]);
        return $stmt->fetchColumn() === $status;
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

    public function setAccountStatus($type, $id, $idSpe, $status) {
        if (!in_array($status, ['actif', 'inactif'], true)) {
            return 'invalid';
        }

        if ($type === 'admin') {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT statut_compte FROM Administrateur WHERE id_admin = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $currentStatus = $stmt->fetchColumn();
            if ($currentStatus === false) {
                $this->db->rollBack();
                return 'not_found';
            }
            if ($currentStatus === $status) {
                $this->db->rollBack();
                return 'unchanged';
            }
            if ($status === 'inactif' && count($this->lockActiveAdministrators()) <= 1) {
                $this->db->rollBack();
                return 'last_admin';
            }
            $stmt = $this->db->prepare("UPDATE Administrateur SET statut_compte = :status WHERE id_admin = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);
            $this->db->commit();
            return 'updated';
        }

        if ($type === 'visiteur') {
            $stmt = $this->db->prepare("UPDATE Visiteur SET statut_compte = :status WHERE id_vis = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);
        } elseif ($type === 'practicien') {
            $stmt = $this->db->prepare(
                "UPDATE Practicien SET statut_compte = :status WHERE Id_pra = :id AND id_spe = :id_spe"
            );
            $stmt->execute(['status' => $status, 'id' => $id, 'id_spe' => $idSpe]);
        } else {
            return 'invalid';
        }
        if ($stmt->rowCount() === 1) {
            return 'updated';
        }
        return $this->getAccountForEdit($type, $id, $idSpe) ? 'unchanged' : 'not_found';
    }

    public function isAccountActive($type, $id, $idSpe = null) {
        if ($type === 'visiteur') {
            $stmt = $this->db->prepare("SELECT statut_compte FROM Visiteur WHERE id_vis = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetchColumn() === 'actif';
        }
        if ($type === 'practicien') {
            $stmt = $this->db->prepare(
                "SELECT statut_compte, statut_validation FROM Practicien
                 WHERE Id_pra = :id AND id_spe = :id_spe LIMIT 1"
            );
            $stmt->execute(['id' => $id, 'id_spe' => $idSpe]);
            $account = $stmt->fetch();
            return $account && $account['statut_compte'] === 'actif' && $account['statut_validation'] === 'approuve';
        }
        if ($type === 'admin') {
            $stmt = $this->db->prepare("SELECT statut_compte FROM Administrateur WHERE id_admin = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetchColumn() === 'actif';
        }
        return false;
    }

    public function deleteAccount($type, $id, $idSpe = null) {
        $this->db->beginTransaction();
        try {
            if ($type === 'visiteur') {
                $stmt = $this->db->prepare("SELECT id_vis FROM Visiteur WHERE id_vis = :id FOR UPDATE");
                $stmt->execute(['id' => $id]);
                if ($stmt->fetchColumn() === false) {
                    $this->db->rollBack();
                    return 'not_found';
                }
                $stmt = $this->db->prepare(
                    "SELECT (SELECT COUNT(*) FROM Visite WHERE id_vis_1 = :id_visite)
                          + (SELECT COUNT(*) FROM Organiser WHERE id_vis = :id_organiser)"
                );
                $stmt->execute(['id_visite' => $id, 'id_organiser' => $id]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $this->db->rollBack();
                    return 'has_history';
                }
                foreach (['Travailler', 'portefeuille'] as $table) {
                    $stmt = $this->db->prepare("DELETE FROM $table WHERE id_vis = :id");
                    $stmt->execute(['id' => $id]);
                }
                $stmt = $this->db->prepare("DELETE FROM Visiteur WHERE id_vis = :id");
                $stmt->execute(['id' => $id]);
            } elseif ($type === 'practicien') {
                $stmt = $this->db->prepare(
                    "SELECT Id_pra FROM Practicien WHERE Id_pra = :id AND id_spe = :id_spe FOR UPDATE"
                );
                $stmt->execute(['id' => $id, 'id_spe' => $idSpe]);
                if ($stmt->fetchColumn() === false) {
                    $this->db->rollBack();
                    return 'not_found';
                }
                $stmt = $this->db->prepare(
                    "SELECT (SELECT COUNT(*) FROM Visite WHERE Id_pra = :id_visite AND id_spe = :spe_visite)
                          + (SELECT COUNT(*) FROM participe WHERE Id_pra = :id_participe AND id_spe = :spe_participe)"
                );
                $stmt->execute([
                    'id_visite' => $id, 'spe_visite' => $idSpe,
                    'id_participe' => $id, 'spe_participe' => $idSpe
                ]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $this->db->rollBack();
                    return 'has_history';
                }
                foreach (['affecter', 'portefeuille'] as $table) {
                    $stmt = $this->db->prepare("DELETE FROM $table WHERE Id_pra = :id AND id_spe = :id_spe");
                    $stmt->execute(['id' => $id, 'id_spe' => $idSpe]);
                }
                $stmt = $this->db->prepare("DELETE FROM Practicien WHERE Id_pra = :id AND id_spe = :id_spe");
                $stmt->execute(['id' => $id, 'id_spe' => $idSpe]);
            } elseif ($type === 'admin') {
                $stmt = $this->db->prepare("SELECT statut_compte FROM Administrateur WHERE id_admin = :id FOR UPDATE");
                $stmt->execute(['id' => $id]);
                $status = $stmt->fetchColumn();
                if ($status === false) {
                    $this->db->rollBack();
                    return 'not_found';
                }
                if ($status === 'actif' && count($this->lockActiveAdministrators()) <= 1) {
                    $this->db->rollBack();
                    return 'last_admin';
                }
                $stmt = $this->db->prepare("DELETE FROM Administrateur WHERE id_admin = :id");
                $stmt->execute(['id' => $id]);
            } else {
                $this->db->rollBack();
                return 'invalid';
            }

            $this->db->commit();
            return 'deleted';
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e->getCode() === '23000') {
                return 'has_history';
            }
            throw $e;
        }
    }

    private function lockActiveAdministrators() {
        $stmt = $this->db->query("SELECT id_admin FROM Administrateur WHERE statut_compte = 'actif' FOR UPDATE");
        return $stmt->fetchAll();
    }
}
