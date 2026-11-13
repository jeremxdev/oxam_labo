<?php
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';

class VisiteurController {
    private $visiteurModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'visiteur') {
            header("Location: index.php?action=practicien");
            exit();
        }
        $this->visiteurModel = new Visiteur();
    }

    public function index() {
        $visiteurs = $this->visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/visiteurs/index.php';
    }

    public function create() {
        $error = null;
        $success = null;

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
            } elseif ($this->visiteurModel->findByMail($email) || (new Practicien())->findByMail($email)) {
                $error = "Cette adresse email est déjà utilisée par un autre compte.";
            } elseif ($this->visiteurModel->createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode, $password)) {
                $success = "Le visiteur $prenom $nom a été créé avec succès. Il pourra se connecter avec l'email $email.";
            } else {
                $error = "Erreur lors de la création du visiteur.";
            }
        }

        $regions = $this->visiteurModel->getAllRegions();
        require __DIR__ . '/../views/visiteurs/create.php';
    }

    public function profil() {
        $error = null;
        $success = null;
        $idVis = (int) $_SESSION['user']['id_vis'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom_vis'] ?? '');
            $prenom = trim($_POST['prenom_vis'] ?? '');
            $email = trim($_POST['mail_vis'] ?? '');
            $telephone = trim($_POST['telephone_vis'] ?? '');
            $adresse = trim($_POST['adresse_vis'] ?? '');
            $regCode = trim($_POST['reg_code'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($nom === '' || $prenom === '' || $email === '' || $telephone === '' || $adresse === '' || $regCode === '') {
                $error = "Tous les champs sont obligatoires.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez saisir une adresse email valide.";
            } elseif (mb_strlen($nom) > 25 || mb_strlen($prenom) > 20 || mb_strlen($email) > 50 ||
                      mb_strlen($telephone) > 20 || mb_strlen($adresse) > 100) {
                $error = "Certains champs dépassent la longueur autorisée (25 pour le nom, 20 pour le prénom et le téléphone, 50 pour l'email, 100 pour l'adresse).";
            } elseif ($password !== '' && strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères (laissez vide pour le conserver).";
            } elseif ($password !== $passwordConfirm) {
                $error = "Les deux mots de passe ne correspondent pas.";
            } else {
                // Email unique sur toute l'application, hors son propre compte
                $visiteurExistant = $this->visiteurModel->findByMail($email);
                $practicienExistant = (new Practicien())->findByMail($email);
                $emailOccupe = ($visiteurExistant && (int) $visiteurExistant['id_vis'] !== $idVis) || $practicienExistant;

                if ($emailOccupe) {
                    $error = "Cette adresse email est déjà utilisée par un autre compte.";
                } elseif ($this->visiteurModel->updateVisiteur($idVis, $nom, $prenom, $email, $telephone, $adresse, $regCode, $password !== '' ? $password : null)) {
                    $success = "Vos informations ont été mises à jour avec succès.";
                    // La session doit refléter les nouvelles valeurs (navigation et contrôle d'accès)
                    $_SESSION['user']['nom'] = $nom;
                    $_SESSION['user']['prenom'] = $prenom;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['reg_code'] = $regCode;
                } else {
                    $error = "Erreur lors de la mise à jour de votre profil.";
                }
            }
        }

        $profil = $this->visiteurModel->getVisiteurById($idVis);
        if (!$profil) {
            session_destroy();
            header("Location: index.php?action=login");
            exit();
        }
        $regions = $this->visiteurModel->getAllRegions();
        require __DIR__ . '/../views/visiteurs/profil.php';
    }
}
