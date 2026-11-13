<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="form-card">
    <h1>Mon profil</h1>
    <p class="subtitle">Modifiez vos informations personnelles et votre mot de passe</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="index.php?action=practicien_profil" method="POST">
        <div class="form-group">
            <label for="nom_par">Nom</label>
            <input type="text" id="nom_par" name="nom_par" maxlength="50" required value="<?= htmlspecialchars($profil['nom_par']) ?>">
        </div>

        <div class="form-group">
            <label for="prenom_par">Prénom</label>
            <input type="text" id="prenom_par" name="prenom_par" maxlength="50" required value="<?= htmlspecialchars($profil['prenom_par']) ?>">
        </div>

        <div class="form-group">
            <label for="mail_par">Adresse Email (Identifiant de connexion)</label>
            <input type="email" id="mail_par" name="mail_par" maxlength="50" required value="<?= htmlspecialchars($profil['mail_par']) ?>">
        </div>

        <div class="form-group">
            <label>Spécialité</label>
            <p><span class="badge"><?= htmlspecialchars($profil['nom_spe'] ?? 'Non renseignée') ?></span></p>
            <small style="color: #64748b;">La spécialité n'est pas modifiable ici : elle est liée à l'historique de vos visites.</small>
        </div>

        <div class="form-group">
            <label for="rue_par">Adresse</label>
            <input type="text" id="rue_par" name="rue_par" maxlength="50" required value="<?= htmlspecialchars($profil['rue_par']) ?>">
        </div>

        <div class="form-group">
            <label for="codePostal">Code postal</label>
            <input type="text" id="codePostal" name="codePostal" inputmode="numeric" pattern="[0-9]{1,5}" maxlength="5" required value="<?= htmlspecialchars($profil['codePostal']) ?>">
        </div>

        <div class="form-group">
            <label for="ville_par">Ville</label>
            <input type="text" id="ville_par" name="ville_par" maxlength="50" required value="<?= htmlspecialchars($profil['ville_par']) ?>">
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">

        <div class="form-group">
            <label for="password">Nouveau mot de passe (8 caractères minimum)</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="Laissez vide pour conserver votre mot de passe actuel">
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmer le nouveau mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password" placeholder="Laissez vide pour conserver votre mot de passe actuel">
        </div>

        <button type="submit" class="btn-primary">Enregistrer les modifications</button>
        <a href="index.php?action=practicien" class="btn-secondary" style="margin-left: 0.5rem;">Retour à mon espace</a>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
