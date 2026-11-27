<?php
/**
 * Point d'entrée unique de l'application (front controller / routeur).
 *
 * Rôle : démarrer la session PHP, contrôler l'état du compte connecté
 * (activation/désactivation par l'administrateur) puis distribuer le
 * paramètre GET "action" vers le contrôleur correspondant.
 *
 * Il interagit avec :
 *  - les contrôleurs de controllers/ (Auth, Practicien, Admin, AdminAc, Visite,
 *    Visiteur, Ac) chargés à la volée selon la route ;
 *  - le modèle models/Admin.php pour vérifier que le compte est actif ;
 *  - les vues rendues par ces contrôleurs (views/...).
 *
 * Convention d'URL : index.php?action=<route> (+ paramètres GET).
 */
session_start();

$action = $_GET['action'] ?? 'visites';

// Garde de session : pour toute requête hormis la déconnexion, on vérifie que le
// compte connecté (visiteur, praticien ou administrateur) est toujours "actif"
// en base — un compte désactivé par l'admin est déconnecté immédiatement.
if (isset($_SESSION['user']) && $action !== 'logout') {
    require_once __DIR__ . '/models/Admin.php';
    $account = $_SESSION['user'];
    $role = $account['role'] ?? '';
    $isActive = false;
    if ($role === 'visiteur') {
        $isActive = (new Admin())->isAccountActive('visiteur', (int) ($account['id_vis'] ?? 0));
    } elseif ($role === 'practicien') {
        $isActive = (new Admin())->isAccountActive(
            'practicien',
            (int) ($account['id_pra'] ?? 0),
            (int) ($account['id_spe'] ?? 0)
        );
    } elseif ($role === 'admin') {
        $isActive = (new Admin())->isAccountActive('admin', (int) ($account['id_admin'] ?? 0));
    }

    if (!$isActive) {
        session_unset();
        session_destroy();
        header("Location: index.php?action=login&err=inactive");
        exit();
    }
}

// Par défaut, l'administrateur atterrit sur son tableau de bord
// plutôt que sur la liste des visites.
if (!isset($_GET['action']) && (($_SESSION['user']['role'] ?? '') === 'admin')) {
    header("Location: index.php?action=admin");
    exit();
}

switch ($action) {

    // --- Routes d'authentification (AuthController) ---
    // Connexion (formulaire + traitement) et création de compte visiteur.
    case 'login':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->login();
        break;
    case 'register':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->register();
        break;
    // --- Espace praticien (PracticienController) ---
    // Inscription d'un praticien (statut "en_attente" jusqu'à validation),
    // tableau de bord de l'espace et édition de son profil.
    case 'practicien_register':
        require_once __DIR__ . '/controllers/PracticienController.php';
        (new PracticienController())->register();
        break;
    case 'practicien':
        require_once __DIR__ . '/controllers/PracticienController.php';
        (new PracticienController())->index();
        break;
    case 'practicien_profil':
        require_once __DIR__ . '/controllers/PracticienController.php';
        (new PracticienController())->profil();
        break;

    // --- Back-office administrateur (AdminController) ---
    // Tableau de bord (comptes, validations) et édition d'un compte.
    case 'admin':
        require_once __DIR__ . '/controllers/AdminController.php';
        (new AdminController())->index();
        break;
    case 'admin_edit':
        require_once __DIR__ . '/controllers/AdminController.php';
        (new AdminController())->edit();
        break;
    // Déconnexion : destruction de la session (le contrôle ci-dessus exclut
    // explicitement cette action de la vérification du compte actif).
    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    // --- Gestion des visites (VisiteController) : liste, création,
    //     suppression, changement de statut et export PDF du compte-rendu. ---
    case 'visites':
        require_once __DIR__ . '/controllers/VisiteController.php';
        (new VisiteController())->index();
        break;
    case 'visite_create':
        require_once __DIR__ . '/controllers/VisiteController.php';
        (new VisiteController())->create();
        break;
    case 'visite_delete':
        require_once __DIR__ . '/controllers/VisiteController.php';
        (new VisiteController())->delete();
        break;
    case 'visite_statut':
        require_once __DIR__ . '/controllers/VisiteController.php';
        (new VisiteController())->setStatut();
        break;
    case 'visite_pdf':
        require_once __DIR__ . '/controllers/VisiteController.php';
        (new VisiteController())->pdf();
        break;

    // --- Gestion des visiteurs (VisiteurController) : liste, création
    //     du compte et modification du profil. ---
    case 'visiteurs':
        require_once __DIR__ . '/controllers/VisiteurController.php';
        (new VisiteurController())->index();
        break;
    case 'visiteur_create':
        require_once __DIR__ . '/controllers/VisiteurController.php';
        (new VisiteurController())->create();
        break;
    case 'visiteur_profil':
        require_once __DIR__ . '/controllers/VisiteurController.php';
        (new VisiteurController())->profil();
        break;

    // --- Activités complémentaires (AcController) : liste et création. ---
    case 'ac':
        require_once __DIR__ . '/controllers/AcController.php';
        (new AcController())->index();
        break;
    case 'ac_create':
        require_once __DIR__ . '/controllers/AcController.php';
        (new AcController())->create();
        break;

    // --- Administration des activités complémentaires (AdminAcController) :
    //     liste, création, modification et suppression (CRUD complet). ---
    case 'admin_ac':
        require_once __DIR__ . '/controllers/AdminAcController.php';
        (new AdminAcController())->index();
        break;
    case 'admin_ac_create':
        require_once __DIR__ . '/controllers/AdminAcController.php';
        (new AdminAcController())->create();
        break;
    case 'admin_ac_edit':
        require_once __DIR__ . '/controllers/AdminAcController.php';
        (new AdminAcController())->edit();
        break;
    case 'admin_ac_delete':
        require_once __DIR__ . '/controllers/AdminAcController.php';
        (new AdminAcController())->delete();
        break;

    // --- Route inconnue : retour par défaut à la liste des visites. ---
    default:
        header("Location: index.php?action=visites");
        exit();
}
