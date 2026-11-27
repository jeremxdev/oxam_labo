<?php
require_once __DIR__ . '/../models/AC.php';
require_once __DIR__ . '/../models/Visiteur.php';

/**
 * Contrôleur des activités complémentaires (AC) du portail visiteur.
 *
 * Actions gérées : index() (liste des AC) et create() (organisation d'une AC).
 * S'appuie sur ACModel pour la lecture/écriture en base et sur Visiteur pour
 * proposer la liste des organisateurs potentiels.
 * Rend les vues views/ac/index.php et views/ac/create.php.
 * Contrôle d'accès : réservé aux comptes "visiteur" connectés (les autres rôles
 * sont redirigés vers leur espace dans le constructeur).
 */
class AcController {
    /** Modèle des activités complémentaires, initialisé par le constructeur. */
    private $acModel;

    /**
     * Vérifie la session et le rôle, puis instancie le modèle AC.
     *
     * Sans session : redirection vers la connexion.
     * Rôle différent de "visiteur" : redirection vers l'espace admin ou praticien.
     */
    public function __construct() {
        // Une action AC ne peut pas être traitée sans compte connecté.
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        // Les administrateurs et praticiens n'ont pas accès aux AC : on les renvoie chez eux.
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'visiteur') {
            $destination = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin' : 'practicien';
            header("Location: index.php?action=$destination");
            exit();
        }
        $this->acModel = new ACModel();
    }

    /**
     * Affiche la liste des activités complémentaires.
     *
     * Récupère toutes les AC via ACModel::getAllAC() (organisateurs agrégés),
     * puis rend views/ac/index.php qui affiche le tableau.
     */
    public function index() {
        $activites = $this->acModel->getAllAC();
        require __DIR__ . '/../views/ac/index.php';
    }

    /**
     * Affiche le formulaire d'organisation d'une AC et traite sa soumission.
     *
     * En POST : lit le thème, le lieu, la date et la liste des organisateurs
     * (le visiteur connecté est l'organisateur par défaut), crée l'AC via le
     * modèle puis redirige vers la liste (action=ac).
     * En GET : charge la liste des visiteurs et rend views/ac/create.php.
     * La vue est également réaffichée en cas d'échec de création.
     */
    public function create() {
        // Traitement du formulaire : la création n'est tentée que sur une requête POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $theme = trim($_POST['theme']);
            $lieu = trim($_POST['lieu']);
            $date = $_POST['date_ac'];
            // Cases à cocher : au moins l'utilisateur connecté est organisateur.
            $organisateurs = $_POST['organisateurs'] ?? [$_SESSION['user']['id_vis']];

            // Succès : retour à la liste ; échec : le formulaire est réaffiché ci-dessous.
            if ($this->acModel->createAC($theme, $lieu, $date, $organisateurs)) {
                header("Location: index.php?action=ac");
                exit();
            }
        }

        // Liste des visiteurs proposés comme co-organisateurs dans le formulaire.
        $visiteurModel = new Visiteur();
        $visiteurs = $visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/ac/create.php';
    }
}
