<?php
// Formulaire d'édition d'un compte administrateur (email) et réinitialisation de son mot de passe.
// Affiche $account (valeurs à modifier), $type / $id / $idSpe (identifiants du compte), $regions
// pour la liste déroulante, et les messages $error / $success. POST protégé par CSRF.
// Inclus par AdminController::edit() (route "admin_edit" de index.php) — accès réservé à l'admin.
include __DIR__ . '/../layout/header.php';
$typeLabels = ['visiteur' => 'visiteur', 'practicien' => 'praticien', 'admin' => 'administrateur'];
$nomMaxLength = $type === 'visiteur' ? 25 : 50;
$prenomMaxLength = $type === 'visiteur' ? 20 : 50;
?>
<div class="form-card">
    <h1>Modifier un compte <?= htmlspecialchars($typeLabels[$type]) ?></h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="index.php?action=admin_edit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="operation" value="save">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="id_spe" value="<?= (int) $idSpe ?>">

        <?php if ($type !== 'admin'): ?>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" maxlength="<?= $nomMaxLength ?>" required value="<?= htmlspecialchars($account['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" maxlength="<?= $prenomMaxLength ?>" required value="<?= htmlspecialchars($account['prenom'] ?? '') ?>">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" maxlength="<?= $type === 'admin' ? 254 : 50 ?>" required value="<?= htmlspecialchars($account['email']) ?>">
        </div>

        <?php if ($type === 'visiteur'): ?>
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" maxlength="20" required value="<?= htmlspecialchars($account['telephone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" maxlength="100" required value="<?= htmlspecialchars($account['adresse'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="reg_code">Région</label>
                <select id="reg_code" name="reg_code" required>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= htmlspecialchars($region['reg_code']) ?>" <?= $region['reg_code'] === ($account['reg_code'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($region['reg_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ($type === 'practicien'): ?>
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" maxlength="50" required value="<?= htmlspecialchars($account['adresse'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="code_postal">Code postal</label>
                <input type="text" id="code_postal" name="code_postal" inputmode="numeric" pattern="[0-9]{1,5}" maxlength="5" required value="<?= htmlspecialchars($account['code_postal'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville" maxlength="50" required value="<?= htmlspecialchars($account['ville'] ?? '') ?>">
            </div>
        <?php endif; ?>

        <button type="submit" class="btn-primary">Enregistrer</button>
        <a href="index.php?action=admin" class="btn-secondary">Retour à la gestion</a>
    </form>

    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">
    <h2>Réinitialiser le mot de passe</h2>
    <form action="index.php?action=admin_edit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="operation" value="reset_password">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="id_spe" value="<?= (int) $idSpe ?>">
        <div class="form-group">
            <label for="password">Nouveau mot de passe (8 caractères minimum)</label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary">Réinitialiser</button>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
