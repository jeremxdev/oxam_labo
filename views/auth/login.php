<?php
// Formulaire de connexion commun aux visiteurs et aux praticiens.
include __DIR__ . '/../layout/header.php';
?>
<div class="auth-card">
    <h2>Portail OXAM LABO - Connexion</h2>
    <p class="subtitle">Saisissez votre adresse email et votre mot de passe (visiteur ou praticien)</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="index.php?action=login" method="POST">
        <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" required placeholder="jean.dupont@oxam-labo.fr">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required minlength="8" placeholder="Votre mot de passe">
        </div>
        <button type="submit" class="btn-primary">Se Connecter</button>
    </form>
    
    <div style="margin-top: 1.5rem; text-align: center;">
        <p>Vous n'avez pas de compte ?</p>
        <a href="index.php?action=register" class="btn-secondary" style="display:inline-block; margin-top: 0.5rem;">Créer un compte visiteur</a>
        <p style="margin-top: 1rem;">Vous êtes praticien ?</p>
        <a href="index.php?action=practicien_register" class="btn-secondary" style="display:inline-block; margin-top: 0.5rem;">Créer un compte praticien</a>
    </div>

    <div class="demo-accounts">
        <h4>Comptes de démonstration (mot de passe : <strong>oxam2025</strong>) :</h4>
        <ul>
            <li><strong>Visiteur</strong> : jean.dupont@oxam-labo.fr (Région OUEST)</li>
            <li><strong>Visiteur</strong> : sophie.martin@oxam-labo.fr (Région IDF-CENTRE)</li>
            <li><strong>Praticien</strong> : hugo.gasnier@oxam-labo.fr</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
