<?php
require_once __DIR__ . '/../models/AC.php';
require_once __DIR__ . '/../models/Visiteur.php';

class AcController {
    private $acModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->acModel = new ACModel();
    }

    public function index() {
        $activites = $this->acModel->getAllAC();
        require __DIR__ . '/../views/ac/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $theme = trim($_POST['theme']);
            $lieu = trim($_POST['lieu']);
            $date = $_POST['date_ac'];
            $organisateurs = $_POST['organisateurs'] ?? [$_SESSION['user']['id_vis']];

            if ($this->acModel->createAC($theme, $lieu, $date, $organisateurs)) {
                header("Location: index.php?action=ac");
                exit();
            }
        }

        $visiteurModel = new Visiteur();
        $visiteurs = $visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/ac/create.php';
    }
}
