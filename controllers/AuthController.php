<?php
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Admin.php';

/**
 * Traite la connexion, l'inscription et la déconnexion des comptes utilisateur.
 */
class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

            $visiteurModel = new Visiteur();
            $practicienModel = new Practicien();
            $adminModel = new Admin();

            $adminAccount = $adminModel->findByMail($email);
            if ($adminAccount && $adminAccount['statut_compte'] === 'inactif' &&
                password_verify($password, $adminAccount['mot_de_passe_admin'])) {
                $error = "Ce compte administrateur est désactivé. Contactez un autre administrateur.";
                require __DIR__ . '/../views/auth/login.php';
                return;
            }

            if ($adminModel->authenticate($email, $password)) {
                $admin = $adminAccount ?: $adminModel->findByMail($email);
                $_SESSION['user'] = [
                    'role' => 'admin',
                    'id_admin' => $admin['id_admin'],
                    'nom' => '',
                    'prenom' => 'Administrateur',
                    'email' => $email
                ];
                header("Location: index.php?action=admin");
                exit();
            }

            // Email unique sur toute l'application : un seul compte correspond
            $user = $visiteurModel->findByMail($email);

            if ($user && $visiteurModel->verifyPassword($user['mot_de_passe_vis'] ?? '', $password)) {
                if (($user['statut_compte'] ?? 'actif') !== 'actif') {
                    $error = "Ce compte visiteur est désactivé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                $_SESSION['user'] = [
                    'role' => 'visiteur',
                    'id_vis' => $user['id_vis'],
                    'nom' => $user['nom_vis'],
                    'prenom' => $user['prenom_vis'],
                    'email' => $user['mail_vis'],
                    'reg_code' => $user['reg_code'] ?? 'Non affecté'
                ];
                header("Location: index.php?action=visites");
                exit();
            }

            $practicien = $practicienModel->findByMail($email);

            if ($practicien && $practicienModel->verifyPassword($practicien['mot_de_passe_par'] ?? '', $password)) {
                if (($practicien['statut_validation'] ?? 'approuve') !== 'approuve') {
                    $error = ($practicien['statut_validation'] ?? '') === 'en_attente'
                        ? "Votre compte praticien est en attente de validation par un administrateur."
                        : "Votre compte praticien n'a pas été approuvé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                if (($practicien['statut_compte'] ?? 'actif') !== 'actif') {
                    $error = "Ce compte praticien est désactivé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                $_SESSION['user'] = [
                    'role' => 'practicien',
                    'id_spe' => $practicien['id_spe'],
                    'id_pra' => $practicien['Id_pra'],
                    'nom' => $practicien['nom_par'],
                    'prenom' => $practicien['prenom_par'],
                    'email' => $practicien['mail_par'],
                    'specialite' => $practicien['nom_spe'] ?? 'Non renseignée'
                ];
                header("Location: index.php?action=practicien");
                exit();
            }

            $error = "Adresse email ou mot de passe incorrect. N'hésitez pas à créer votre profil via le formulaire d'inscription.";
            require __DIR__ . '/../views/auth/login.php';
        } else {
            $error = ($_GET['err'] ?? '') === 'inactive'
                ? "Votre compte a été désactivé ou supprimé. Contactez un administrateur."
                : null;
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    public function register() {
        $error = null;
        $success = null;

        $visiteurModel = new Visiteur();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom_vis']);
            $prenom = trim($_POST['prenom_vis']);
            $email = trim($_POST['mail_vis']);
            $telephone = $_POST['telephone_vis'];
            $adresse = trim($_POST['adresse_vis']);
            $regCode = $_POST['reg_code'];
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($password !== $passwordConfirm) {
                $error = "Les deux mots de passe ne correspondent pas.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez saisir une adresse email valide.";
            } elseif ($visiteurModel->findByMail($email) || (new Practicien())->findByMail($email) ||
                      (new Admin())->isEmailReserved($email)) {
                $error = "Cette adresse email est déjà enregistrée.";
            } else {
                if ($visiteurModel->createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode, $password)) {
                    $success = "Compte utilisateur créé avec succès ! Vous pouvez maintenant vous connecter avec votre adresse email ($email) et votre mot de passe.";
                } else {
                    $error = "Erreur lors de la création du compte.";
                }
            }
        }

        $regions = $visiteurModel->getAllRegions();
        require __DIR__ . '/../views/auth/register.php';
    }

    public function logout() {
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
