<?php
require_once __DIR__ . '/../models/Visiteur.php';

class VisiteurController {
    private $visiteurModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
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

            if ($this->visiteurModel->createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode)) {
                $success = "Le visiteur $prenom $nom a été créé avec succès.";
            } else {
                $error = "Erreur lors de la création du visiteur.";
            }
        }

        $regions = $this->visiteurModel->getAllRegions();
        require __DIR__ . '/../views/visiteurs/create.php';
    }
}
