<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OXAM LABO - Gestion des Visites LBC</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="index.php?action=visites" class="brand">OXAM <span>LABO</span></a>
            <?php if (isset($_SESSION['user'])): ?>
                <nav class="nav-links">
                    <span class="user-info">
                        <strong><?= htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']) ?></strong> 
                        <small>(Région: <?= htmlspecialchars($_SESSION['user']['reg_code']) ?>)</small>
                    </span>
                    <a href="index.php?action=visites" class="btn-link">Visites</a>
                    <a href="index.php?action=ac" class="btn-link">Activités Comp. (AC)</a>
                    <a href="index.php?action=visiteurs" class="btn-link">Flotte Visiteurs</a>
                    <a href="index.php?action=visite_create" class="btn-primary-sm">+ Nouvelle Visite</a>
                    <a href="index.php?action=logout" class="btn-logout">Déconnexion</a>
                </nav>
            <?php else: ?>
                <nav class="nav-links">
                    <a href="index.php?action=login" class="btn-link">Connexion</a>
                    <a href="index.php?action=register" class="btn-primary-sm">Créer un Compte</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
