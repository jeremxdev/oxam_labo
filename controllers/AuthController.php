<?php
require_once __DIR__ . '/../models/Visiteur.php';

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);

            $visiteurModel = new Visiteur();
            $user = $visiteurModel->findByMail($email);

            if ($user) {
                $_SESSION['user'] = [
                    'id_vis' => $user['id_vis'],
                    'nom' => $user['nom_vis'],
                    'prenom' => $user['prenom_vis'],
                    'email' => $user['mail_vis'],
                    'reg_code' => $user['reg_code'] ?? 'Non affecté'
                ];
                header("Location: index.php?action=visites");
                exit();
            } else {
                $error = "Visiteur introuvable avec cette adresse email ($email). N'hésitez pas à créer votre profil via le formulaire d'inscription.";
                require __DIR__ . '/../views/auth/login.php';
            }
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

            if ($visiteurModel->findByMail($email)) {
                $error = "Cette adresse email est déjà enregistrée.";
            } else {
                if ($visiteurModel->createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode)) {
                    $success = "Compte utilisateur créé avec succès ! Vous pouvez maintenant vous connecter avec votre adresse email ($email).";
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
