<?php
require_once __DIR__ . '/../models/Visite.php';

/**
 * Contrôleur des comptes rendus de visite du portail visiteur.
 *
 * Actions gérées : index() (liste), create() (saisie d'une visite), delete(),
 * setStatut() (acceptation/refus par le praticien) et pdf() (compte-rendu imprimable).
 * S'appuie sur le modèle Visite pour toutes les opérations en base.
 * Rend views/visites/index.php, views/visites/create.php et
 * views/visites/pdf_template.php ; les redirections sont utilisées pour les actions
 * sans affichage (suppression, changement de statut).
 * Contrôle d'accès : session obligatoire ; les actions visiteur sont réservées au
 * rôle "visiteur", setStatut() au rôle "practicien" et pdf() vérifie l'appartenance.
 */
class VisiteController {
    /** Modèle des visites, initialisé par le constructeur. */
    private $visiteModel;

    /**
     * Vérifie la session puis instancie le modèle Visite.
     * Sans session : redirection vers la connexion.
     */
    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->visiteModel = new Visite();
    }

    /**
     * Garde d'accès interne : autorise uniquement le rôle "visiteur".
     * Les autres rôles sont redirigés vers leur espace (admin ou praticien).
     */
    private function requireVisiteur() {
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'visiteur') {
            $destination = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin' : 'practicien';
            header("Location: index.php?action=$destination");
            exit();
        }
    }

    /**
     * Affiche les visites appartenant au visiteur connecté.
     * Rend views/visites/index.php avec $visites (et $error/$success si fournis).
     */
    public function index() {
        $this->requireVisiteur();
        $u = $_SESSION['user'];
        // Seules les visites de ce visiteur sont chargées (pas de liste globale).
        $visites = $this->visiteModel->getVisitesByVisiteur($u['id_vis']);
        require __DIR__ . '/../views/visites/index.php';
    }

    /**
     * Affiche le formulaire de visite et enregistre la visite soumise.
     *
     * En POST : lit la date, le motif, le bilan, le praticien sélectionné, les
     * médicaments présentés et les échantillons offerts ; délègue l'écriture
     * atomique au modèle Visite::createVisite() puis redirige vers la liste.
     * En GET (ou en cas d'erreur) : charge praticiens et médicaments et rend
     * views/visites/create.php avec $error si l'enregistrement a échoué.
     */
    public function create() {
        $this->requireVisiteur();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dateVis = $_POST['date_vis'];
            $motifVis = trim($_POST['motif_vis']);
            $bilanVis = trim($_POST['bilan_vis']);
            // Le select renvoie "id_spe-Id_pra" : la clé du praticien est composée.
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

    /**
     * Supprime une visite de l'utilisateur connecté.
     *
     * Lit l'identifiant dans $_GET['id'] et passe l'id du visiteur au modèle pour
     * qu'il vérifie la propriété de la visite. Redirige vers action=visites avec
     * msg=deleted ou err=cannot_delete (aucune vue dédiée).
     */
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

    /**
     * Enregistre la décision du praticien (acceptée / refusée) sur une visite.
     *
     * Réservé au rôle "practicien" ; vérifie à nouveau la session car cette action
     * est appelée directement par URL. Lit $_POST['id'] et $_POST['statut'], passe
     * l'identité du praticien au modèle pour limiter la mise à jour à ses visites,
     * puis redirige vers son espace avec msg=<statut> ou err=statut.
     */
    public function setStatut() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? '') !== 'practicien') {
            $destination = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin' : 'visites';
            header("Location: index.php?action=$destination");
            exit();
        }

        $idVisite = (int) ($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        $idSpe = (int) $_SESSION['user']['id_spe'];
        $idPra = (int) $_SESSION['user']['id_pra'];

        if ($this->visiteModel->setStatutVisite($idVisite, $idSpe, $idPra, $statut)) {
            header("Location: index.php?action=practicien&msg=" . $statut);
        } else {
            header("Location: index.php?action=practicien&err=statut");
        }
        exit();
    }

    /**
     * Affiche le compte-rendu imprimable d'une visite (vue PDF HTML).
     *
     * Lit $_GET['id'], charge la visite puis autorise l'accès uniquement au
     * visiteur qui l'a créée ou au praticien concerné (comparaison des identifiants).
     * Rend views/visites/pdf_template.php avec $visite ; un message et un arrêt
     * sont produits si la visite n'existe pas ou si l'accès est refusé.
     */
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
