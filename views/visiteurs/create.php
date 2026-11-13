<?php
// Formulaire de création d'un visiteur et de son affectation régionale.
include __DIR__ . '/../layout/header.php';
?>
<div class="form-card">
    <h1>Créer un profil Visiteur</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="index.php?action=visiteur_create" method="POST">
        <div class="form-group">
            <label for="nom_vis">Nom</label>
            <input type="text" id="nom_vis" name="nom_vis" maxlength="25" required placeholder="Dupont">
        </div>

        <div class="form-group">
            <label for="prenom_vis">Prénom</label>
            <input type="text" id="prenom_vis" name="prenom_vis" maxlength="20" required placeholder="Jean">
        </div>

        <div class="form-group">
            <label for="mail_vis">Adresse Email (Identifiant de connexion)</label>
            <input type="email" id="mail_vis" name="mail_vis" maxlength="50" required placeholder="jean.dupont@oxam-labo.fr">
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
            <label for="telephone_vis">Téléphone</label>
            <input type="tel" id="telephone_vis" name="telephone_vis" maxlength="20" required placeholder="0612345678">
        </div>

        <div class="form-group">
            <label for="adresse_vis">Adresse</label>
            <input type="text" id="adresse_vis" name="adresse_vis" maxlength="100" required placeholder="10 Rue de Paris, Angers">
        </div>

        <div class="form-group">
            <label for="reg_code">Région d'affectation</label>
            <select name="reg_code" id="reg_code" required>
                <?php foreach ($regions as $r): ?>
                    <option value="<?= htmlspecialchars($r['reg_code']) ?>"><?= htmlspecialchars($r['reg_code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-primary">Créer le Visiteur</button>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
