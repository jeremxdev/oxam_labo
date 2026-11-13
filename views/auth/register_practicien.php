<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="auth-card">
    <h2>Créer un compte praticien</h2>
    <p class="subtitle">Votre profil sera proposé lors de la saisie des visites médicales et vos identifiants vous permettront de vous connecter à l'espace praticien</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="index.php?action=login" class="btn-primary">Retour à la connexion</a>
        </div>
    <?php else: ?>
        <form action="index.php?action=practicien_register" method="POST">
            <div class="form-group">
                <label for="nom_par">Nom</label>
                <input type="text" id="nom_par" name="nom_par" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="prenom_par">Prénom</label>
                <input type="text" id="prenom_par" name="prenom_par" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="mail_par">Adresse Email (Identifiant de connexion)</label>
                <input type="email" id="mail_par" name="mail_par" maxlength="50" required placeholder="luc.bernard@oxam-labo.fr">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe (8 caractères minimum)</label>
                <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="id_spe">Spécialité existante (facultatif si vous en ajoutez une)</label>
                <select id="id_spe" name="id_spe">
                    <option value="">-- Choisir une spécialité --</option>
                    <?php foreach ($specialites as $specialite): ?>
                        <option value="<?= htmlspecialchars($specialite['id_spe']) ?>"><?= htmlspecialchars($specialite['nom_spe']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nouvelle_specialite">Ou ajouter une spécialité</label>
                <input type="text" id="nouvelle_specialite" name="nouvelle_specialite" maxlength="50">
            </div>

            <div class="form-group">
                <label for="rue_par">Adresse</label>
                <input type="text" id="rue_par" name="rue_par" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="codePostal">Code postal</label>
                <input type="text" id="codePostal" name="codePostal" inputmode="numeric" pattern="[0-9]{1,5}" maxlength="5" required>
            </div>

            <div class="form-group">
                <label for="ville_par">Ville</label>
                <input type="text" id="ville_par" name="ville_par" maxlength="50" required>
            </div>

            <button type="submit" class="btn-primary">Créer mon compte praticien</button>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
