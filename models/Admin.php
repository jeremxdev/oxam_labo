<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle d'administration : couvre la table SQL « Administrateur » ainsi que la gestion
 * transverse des comptes des trois types d'utilisateurs (Visiteur, Practicien, Admin).
 * Propose l'authentification (avec provisionnement du premier admin), la consultation
 * et la modification des comptes, l'activation/désactivation, la réinitialisation de
 * mot de passe, la validation des praticiens et la suppression définitive des comptes.
 * Les opérations sensibles (activation admin, suppression) sont protégées par transaction.
 */
class Admin {
    private $db;

    /**
     * Initialise la connexion à la base de données utilisée par les méthodes du modèle.
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Recherche un compte administrateur par adresse e-mail.
     * @param string $email Adresse e-mail recherchée.
     * @return array|false Ligne du compte (id, mail, empreinte du mot de passe, statut)
     *                      ou false si aucun compte ne correspond.
     */
    public function findByMail($email) {
        $stmt = $this->db->prepare(
            "SELECT id_admin, mail_admin, mot_de_passe_admin, statut_compte
             FROM Administrateur WHERE mail_admin = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Indique si une adresse e-mail est déjà réservée par un compte existant
     * ou par l'adresse e-mail du provisionnement de l'administrateur initial.
     * @param string $email Adresse e-mail à contrôler.
     * @return bool true si l'adresse est réservée, false sinon.
     */
    public function isEmailReserved($email) {
        $bootstrapEmail = getenv('ADMIN_EMAIL');
        if (!is_string($bootstrapEmail) || $bootstrapEmail === '') {
            $bootstrapEmail = 'admin@oxam-labo.fr';
        }
        return (bool) $this->findByMail($email) || hash_equals($bootstrapEmail, $email);
    }

    /**
     * Authentifie un administrateur par e-mail et mot de passe.
     * @param string $email Adresse e-mail saisie.
     * @param string $password Mot de passe en clair saisi.
     * @return bool true si les identifiants sont valides et le compte actif.
     *
     * Effets de bord : si aucun administrateur n'existe encore en base (premier démarrage),
     * la méthode effectue le « provisionnement » du premier admin à partir des variables
     * d'environnement ADMIN_EMAIL / ADMIN_PASSWORD et INSERT une ligne dans Administrateur.
     * Les mots de passe en base sont vérifiés avec password_verify(), le mot de
     * passe d'amorçage avec hash_equals().
     */
    public function authenticate($email, $password) {
        $admin = $this->findByMail($email);
        if ($admin) {
            // Cas nominal : le compte existe, il doit être actif et le mot de passe correspondre.
            return $admin['statut_compte'] === 'actif' &&
                password_verify($password, $admin['mot_de_passe_admin']);
        }

        // Provisionnement impossible dès qu'au moins un administrateur est déjà en base.
        if ((int) $this->db->query("SELECT COUNT(*) FROM Administrateur")->fetchColumn() !== 0) {
            return false;
        }

        // Adresse de provisionnement : variable d'environnement, sinon valeur par défaut.
        $bootstrapEmail = getenv('ADMIN_EMAIL');
        if (!is_string($bootstrapEmail) || $bootstrapEmail === '') {
            $bootstrapEmail = 'admin@oxam-labo.fr';
        }
        if (!hash_equals($bootstrapEmail, $email)) {
            return false;
        }

        // Le mot de passe d'amorçage doit être fourni, l'e-mail valide et le mot de passe suffisamment long.
        $bootstrapPassword = getenv('ADMIN_PASSWORD');
        if (!is_string($bootstrapPassword) ||
            !filter_var($bootstrapEmail, FILTER_VALIDATE_EMAIL) || strlen($bootstrapPassword) < 12) {
            error_log('Provisionnement admin impossible : ADMIN_EMAIL valide et ADMIN_PASSWORD de 12 caractères minimum requis.');
            return false;
        }

        if (!hash_equals($bootstrapPassword, $password)) {
            return false;
        }

        // Contrôle que l'adresse n'est pas déjà utilisée par un visiteur ou un praticien.
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

        // Création du premier compte administrateur (empreinte hachée, jamais en clair).
        $stmt = $this->db->prepare(
            "INSERT INTO Administrateur (mail_admin, mot_de_passe_admin) VALUES (:email, :password)"
        );
        $stmt->execute([
            'email' => $bootstrapEmail,
            'password' => password_hash($bootstrapPassword, PASSWORD_DEFAULT)
        ]);
        return true;
    }

    /**
     * Liste tous les comptes des trois types (visiteur, praticien, administrateur)
     * dans un format homogène, triés par nom puis prénom.
     * Union : chaque SELECT projette les colonnes communes (id, nom, prénom, e-mail,
     * statut de validation éventuel, statut de compte) avec un type discriminant.
     * @return array Liste des comptes normalisés pour l'affichage d'un tableau unique.
     */
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

    /**
     * Récupère la fiche d'un compte à modifier, selon son type.
     * Pour un visiteur, la sous-requête renvoie aussi sa dernière région affectée (Travailler).
     * @param string $type Type de compte : 'visiteur', 'practicien' ou 'admin'.
     * @param int $id Identifiant du compte.
     * @param int|null $idSpe Spécialité du praticien (le Practicien est identifié par la double clé Id_pra + id_spe).
     * @return array|false La fiche à éditer, ou false si le type est inconnu ou le compte absent.
     */
    public function getAccountForEdit($type, $id, $idSpe = null) {
        if ($type === 'visiteur') {
            // Sous-requête corrélée : dernière région d'affectation du visiteur, null si aucune.
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

    /**
     * Vérifie si une adresse e-mail est déjà utilisée par un autre compte.
     * Parcourt les trois tables d'utilisateurs ; pour le type exclu (compte en cours
     * d'édition), la ligne correspondante est ignorée afin de ne pas se signaler soi-même.
     * @param string $email Adresse e-mail à contrôler.
     * @param string $exceptType Type de compte à exclure du contrôle ('visiteur', 'practicien', 'admin').
     * @param int $exceptId Identifiant du compte à exclure.
     * @return bool true si l'e-mail est déjà pris par un autre compte.
     */
    public function emailInUse($email, $exceptType, $exceptId) {
        $tables = [
            // type => [table SQL, colonne e-mail, colonne identifiant]
            'visiteur' => ['Visiteur', 'mail_vis', 'id_vis'],
            'practicien' => ['Practicien', 'mail_par', 'Id_pra'],
            'admin' => ['Administrateur', 'mail_admin', 'id_admin']
        ];

        foreach ($tables as $type => $fields) {
            list($table, $emailColumn, $idColumn) = $fields;
            $sql = "SELECT 1 FROM $table WHERE $emailColumn = :email";
            $params = ['email' => $email];
            // Sur le type du compte édité, on exclut explicitement sa propre ligne.
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
