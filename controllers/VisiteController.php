<?php
require_once __DIR__ . '/../models/Visite.php';

class VisiteController {
    private $visiteModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->visiteModel = new Visite();
    }

    public function index() {
        $u = $_SESSION['user'];
        $visites = $this->visiteModel->getVisitesByVisiteur($u['id_vis']);
        require __DIR__ . '/../views/visites/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dateVis = $_POST['date_vis'];
            $motifVis = trim($_POST['motif_vis']);
            $bilanVis = trim($_POST['bilan_vis']);
            $practicienData = explode('-', $_POST['practicien']);
            $idSpe = $practicienData[0] ?? 0;
            $idPra = $practicienData[1] ?? 0;
            $medsPresentes = $_POST['meds_presentes'] ?? [];
            $echantillons = $_POST['echantillons'] ?? [];

            if ($this->visiteModel->createVisite($dateVis, $motifVis, $bilanVis, $idSpe, $idPra, $_SESSION['user']['id_vis'], $medsPresentes, $echantillons)) {
                header("Location: index.php?action=visites");
                exit();
            } else {
                $error = "Erreur lors de l'enregistrement de la visite.";
            }
        }

        $practiciens = $this->visiteModel->getPracticiensByPortefeuille($_SESSION['user']['id_vis']);
        $medicaments = $this->visiteModel->getMedicaments();
        require __DIR__ . '/../views/visites/create.php';
    }

    public function delete() {
        $id = $_GET['id'] ?? 0;
        if ($this->visiteModel->deleteVisite($id, $_SESSION['user']['id_vis'])) {
            header("Location: index.php?action=visites&msg=deleted");
        } else {
            header("Location: index.php?action=visites&err=cannot_delete");
        }
        exit();
    }

    public function pdf() {
        $id = $_GET['id'] ?? 0;
        $visite = $this->visiteModel->getVisiteById($id);
        if (!$visite) {
            die("Compte-rendu introuvable.");
        }
        require __DIR__ . '/../views/visites/pdf_template.php';
    }
}
