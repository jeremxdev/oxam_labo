<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="auth-card">
    <h2>Portail OXAM LABO - Connexion</h2>
    <p class="subtitle">Saisissez votre adresse email de visiteur médical</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="index.php?action=login" method="POST">
        <div class="form-group">
            <label for="email">Adresse Email Visiteur</label>
            <input type="email" id="email" name="email" required placeholder="jean.dupont@oxam-labo.fr">
        </div>
        <button type="submit" class="btn-primary">Se Connecter</button>
    </form>
    
    <div style="margin-top: 1.5rem; text-align: center;">
        <p>Vous n'avez pas de compte ?</p>
        <a href="index.php?action=register" class="btn-secondary" style="display:inline-block; margin-top: 0.5rem;">Créer un profil utilisateur</a>
    </div>

    <div class="demo-accounts">
        <h4>Comptes existants dans la BDD (`oxam_labo`) :</h4>
        <ul>
            <li>jean.dupont@oxam-labo.fr (Région OUEST)</li>
            <li>sophie.martin@oxam-labo.fr (Région IDF-CENTRE)</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
