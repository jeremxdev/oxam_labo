<?php
require_once __DIR__ . '/../models/User.php';

/**
 * Contrôleur des comptes internes du laboratoire (personnel).
 *
 * Actions gérées : index() (liste des comptes) et create() (ajout d'un compte).
 * S'appuie sur le modèle User (tables du personnel) et rend les vues
 * views/users/index.php et views/users/create.php.
 * Contrôle d'accès : une session doit être ouverte ; la gestion fine des rôles
 * est assurée par les vues et le routeur, ce contrôleur vérifie seulement la connexion.
 */
class UserController {
    /** Modèle des comptes internes, initialisé par le constructeur. */
    private $userModel;

    /**
     * Vérifie qu'un utilisateur est connecté puis instancie le modèle.
     * Sans session : redirection vers la page de connexion.
     */
    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->userModel = new User();
    }

    /**
     * Affiche la liste des comptes du personnel.
     * Récupère tous les comptes via User::getAllUsers() puis rend views/users/index.php.
     */
    public function index() {
        $users = $this->userModel->getAllUsers();
        require __DIR__ . '/../views/users/index.php';
    }

    /**
     * Affiche le formulaire de création d'un compte interne et traite sa soumission.
     *
     * En POST : lit nom, prénom, email, mot de passe, rôle et région ; refuse un
     * email déjà enregistré puis crée le compte via User::createUser().
     * En GET (ou après échec) : charge les régions et rend views/users/create.php
     * avec $error et $success.
     */
    public function create() {
        $error = null;
        $success = null;

        // Création uniquement sur soumission du formulaire.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $role = $_POST['role'];
            $idRegion = $_POST['id_region'];

            // Un email sert d'identifiant : il doit être unique dans cette table.
            if ($this->userModel->findByEmail($email)) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                if ($this->userModel->createUser($nom, $prenom, $email, $password, $role, $idRegion)) {
                    $success = "L'utilisateur $prenom $nom a été créé avec succès.";
                } else {
                    $error = "Une erreur est survenue lors de la création.";
                }
            }
        }

        // Régions proposées dans la liste déroulante du formulaire.
        $regions = $this->userModel->getAllRegions();
        require __DIR__ . '/../views/users/create.php';
    }
}
