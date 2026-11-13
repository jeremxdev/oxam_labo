<?php
// Formulaire de modification du profil du visiteur connecté.
include __DIR__ . '/../layout/header.php';
?>
<div class="form-card">
    <h1>Mon profil</h1>
    <p class="subtitle">Modifiez vos informations personnelles et votre mot de passe</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="index.php?action=visiteur_profil" method="POST">
        <div class="form-group">
            <label for="nom_vis">Nom</label>
            <input type="text" id="nom_vis" name="nom_vis" maxlength="25" required value="<?= htmlspecialchars($profil['nom_vis']) ?>">
        </div>

        <div class="form-group">
            <label for="prenom_vis">Prénom</label>
            <input type="text" id="prenom_vis" name="prenom_vis" maxlength="20" required value="<?= htmlspecialchars($profil['prenom_vis']) ?>">
        </div>

        <div class="form-group">
            <label for="mail_vis">Adresse Email (Identifiant de connexion)</label>
            <input type="email" id="mail_vis" name="mail_vis" maxlength="50" required value="<?= htmlspecialchars($profil['mail_vis']) ?>">
        </div>

        <div class="form-group">
            <label for="telephone_vis">Téléphone</label>
            <input type="tel" id="telephone_vis" name="telephone_vis" maxlength="20" required value="<?= htmlspecialchars($profil['telephone_vis']) ?>">
        </div>

        <div class="form-group">
            <label for="adresse_vis">Adresse</label>
            <input type="text" id="adresse_vis" name="adresse_vis" maxlength="100" required value="<?= htmlspecialchars($profil['adresse_vis']) ?>">
        </div>

        <div class="form-group">
            <label for="reg_code">Région d'affectation</label>
            <select name="reg_code" id="reg_code" required>
                <?php foreach ($regions as $r): ?>
                    <option value="<?= htmlspecialchars($r['reg_code']) ?>" <?= $r['reg_code'] === $profil['reg_code'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['reg_code']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        <a href="index.php?action=visites" class="btn-secondary" style="margin-left: 0.5rem;">Retour aux visites</a>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
