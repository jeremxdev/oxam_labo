<?php
require_once __DIR__ . '/../models/Visite.php';

/**
 * Gère les rapports de visite : liste, création, suppression et rendu imprimable.
 */
class VisiteController {
    private $visiteModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->visiteModel = new Visite();
    }

    private function requireVisiteur() {
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'visiteur') {
            header("Location: index.php?action=practicien");
            exit();
        }
    }

    public function index() {
        $this->requireVisiteur();
        $u = $_SESSION['user'];
        $visites = $this->visiteModel->getVisitesByVisiteur($u['id_vis']);
        require __DIR__ . '/../views/visites/index.php';
    }

    public function create() {
        $this->requireVisiteur();
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

        $practiciens = $this->visiteModel->getAllPracticiens();
        $medicaments = $this->visiteModel->getMedicaments();
        require __DIR__ . '/../views/visites/create.php';
    }

    public function delete() {
        $this->requireVisiteur();
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

        // Le compte-rendu n'est accessible qu'au visiteur émetteur ou au praticien concerné
        $role = $_SESSION['user']['role'] ?? 'visiteur';
        $estProprietaire = $role === 'visiteur' && (int) $visite['id_vis_1'] === (int) $_SESSION['user']['id_vis'];
        $estPracticien = $role === 'practicien'
            && (int) $visite['id_spe'] === (int) $_SESSION['user']['id_spe']
            && (int) $visite['Id_pra'] === (int) $_SESSION['user']['id_pra'];

        if (!$estProprietaire && !$estPracticien) {
            die("Accès refusé à ce compte-rendu.");
        }

        require __DIR__ . '/../views/visites/pdf_template.php';
    }
}
