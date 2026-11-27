<?php
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Visite.php';

/**
 * Contrôleur des comptes visiteur : administration et profil personnel.
 *
 * Actions gérées : index() et create() (réservées à l'administrateur),
 * profil() (visiteur connecté).
 * S'appuie sur Visiteur, Practicien, Visite et Admin pour vérifier l'unicité
 * des emails et charger les visites refusées.
 * Rend views/visiteurs/index.php, views/visiteurs/create.php et
 * views/visiteurs/profil.php.
 * Contrôle d'accès : session obligatoire ; requireAdmin() et requireVisiteur()
 * redirigent les rôles non autorisés vers leur espace.
 */
class VisiteurController {
    /** Modèle des visiteurs, initialisé par le constructeur. */
    private $visiteurModel;

    /**
     * Vérifie la session puis instancie le modèle Visiteur.
     * Sans session : redirection vers la connexion.
     */
    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        $this->visiteurModel = new Visiteur();
    }

    /**
     * Garde d'accès interne : autorise uniquement le rôle "admin".
     * Redirige les visiteurs vers action=visites, les praticiens vers
     * action=practicien et les anonymes vers la connexion.
     */
    private function requireAdmin() {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $role = $_SESSION['user']['role'] ?? '';
            $destination = $role === 'visiteur' ? 'visites' : ($role === 'practicien' ? 'practicien' : 'login');
            header("Location: index.php?action=$destination");
            exit();
        }
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
     * Affiche la liste de tous les visiteurs (espace administrateur).
     * Charge les visiteurs via Visiteur::getAllVisiteurs() et rend views/visiteurs/index.php.
     */
    public function index() {
        $this->requireAdmin();
        $visiteurs = $this->visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/visiteurs/index.php';
    }

    /**
     * Crée un compte visiteur depuis l'espace administrateur.
     *
     * En POST : valide la taille du mot de passe (8 minimum), sa confirmation,
     * l'unicité de l'email sur visiteurs/praticiens/admins, puis crée le compte
     * via Visiteur::createVisiteur().
     * En GET (ou après échec) : charge les régions et rend views/visiteurs/create.php.
     */
    public function create() {
        $this->requireAdmin();
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
            } elseif ($this->visiteurModel->findByMail($email) || (new Practicien())->findByMail($email) ||
                      (new Admin())->isEmailReserved($email)) {
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

    /**
     * Affiche et met à jour le profil du visiteur connecté.
     *
     * En POST : vérifie les champs obligatoires, l'email, les longueurs autorisées,
     * le mot de passe facultatif (8 caractères minimum s'il est fourni) et
     * l'unicité de l'email hors son propre compte, puis appelle
     * Visiteur::updateVisiteur(). La session est mise à jour après succès.
     * Charge enfin le profil, les régions et les visites refusées, puis rend
     * views/visiteurs/profil.php avec $profil, $regions, $visitesRefusees, $error, $success.
     */
    public function profil() {
        $this->requireVisiteur();
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
                $adminExistant = (new Admin())->isEmailReserved($email);
                $emailOccupe = ($visiteurExistant && (int) $visiteurExistant['id_vis'] !== $idVis) || $practicienExistant || $adminExistant;

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
        // Visites refusées affichées dans le profil pour information du visiteur.
        $visitesRefusees = (new Visite())->getVisitesRefuseesByVisiteur($idVis);
        require __DIR__ . '/../views/visiteurs/profil.php';
    }
}
