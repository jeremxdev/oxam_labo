<?php
// Point d'entrée HTTP : démarre la session et distribue les actions vers leurs contrôleurs.
session_start();

$action = $_GET['action'] ?? 'visites';

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

if (!isset($_GET['action']) && (($_SESSION['user']['role'] ?? '') === 'admin')) {
    header("Location: index.php?action=admin");
    exit();
}

switch ($action) {
    case 'login':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->login();
        break;
    case 'register':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->register();
        break;
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
    case 'admin':
        require_once __DIR__ . '/controllers/AdminController.php';
        (new AdminController())->index();
        break;
    case 'admin_edit':
        require_once __DIR__ . '/controllers/AdminController.php';
        (new AdminController())->edit();
        break;
    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->logout();
        break;
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
    case 'ac':
        require_once __DIR__ . '/controllers/AcController.php';
        (new AcController())->index();
        break;
    case 'ac_create':
        require_once __DIR__ . '/controllers/AcController.php';
        (new AcController())->create();
        break;
    default:
        header("Location: index.php?action=visites");
        exit();
}
