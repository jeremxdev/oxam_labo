<?php
require_once __DIR__ . '/../models/User.php';

/**
 * Gère la consultation et la création des comptes du personnel du laboratoire.
 */
class UserController {
    private $userModel;

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->userModel = new User();
    }

    public function index() {
        $users = $this->userModel->getAllUsers();
        require __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $role = $_POST['role'];
            $idRegion = $_POST['id_region'];

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

        $regions = $this->userModel->getAllRegions();
        require __DIR__ . '/../views/users/create.php';
    }
}
