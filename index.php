<?php
session_start();

$action = $_GET['action'] ?? 'visites';

switch ($action) {
    case 'login':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->login();
        break;
    case 'register':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->register();
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
