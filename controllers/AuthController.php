<?php
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';

/**
 * Traite la connexion, l'inscription et la déconnexion des visiteurs et praticiens.
 */
class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?? '';

            $visiteurModel = new Visiteur();
            $practicienModel = new Practicien();

            // Email unique sur toute l'application : un seul compte correspond
            $user = $visiteurModel->findByMail($email);

            if ($user && $visiteurModel->verifyPassword($user['mot_de_passe_vis'] ?? '', $password)) {
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
            } elseif ($visiteurModel->findByMail($email) || (new Practicien())->findByMail($email)) {
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
