<?php
// Partial de formulaire partagé par la création et la modification d'une AC.
// Variables attendues : $form (theme, lieu, date_ac, organisateurs), $visiteurs,
// $error, $action (route POST), $buttonLabel, $title et $id (null à la création).
// Garde : ce partial n'est jamais appelé directement via une URL.
// On vérifie l'existence des variables (et non isset()) car $error vaut null en GET.
$attendu = ['form', 'visiteurs', 'error', 'action', 'buttonLabel', 'title'];
$variables = get_defined_vars();
foreach ($attendu as $nom) {
    if (!array_key_exists($nom, $variables)) {
        http_response_code(404);
        exit();
    }
}
unset($attendu, $variables, $nom);
$id = $id ?? null;
?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<div class="form-card">
    <h1><?= htmlspecialchars($title) ?></h1>
    <form action="index.php?action=<?= htmlspecialchars($action) ?>" method="POST">
        <!-- Jeton CSRF vérifié par AdminAcController avant toute écriture. -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <?php if ($id !== null): ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="theme">Thème (20 caractères maximum)</label>
            <input type="text" id="theme" name="theme" maxlength="20" required
                   value="<?= htmlspecialchars($form['theme']) ?>">
        </div>

        <div class="form-group">
            <label for="lieu">Lieu (50 caractères maximum)</label>
            <input type="text" id="lieu" name="lieu" maxlength="50" required
                   value="<?= htmlspecialchars($form['lieu']) ?>">
        </div>

        <div class="form-group">
            <label for="date_ac">Date</label>
            <input type="date" id="date_ac" name="date_ac" required
                   value="<?= htmlspecialchars($form['date_ac']) ?>">
        </div>

        <!-- Cochés : les identifiants de visiteurs envoyés dans organisateurs[]. -->
        <div class="form-group">
            <label>Organisateurs (visiteurs)</label>
            <?php if (!$visiteurs): ?>
                <p>Aucun visiteur enregistré.</p>
            <?php else: ?>
                <div class="samples-grid">
                    <?php foreach ($visiteurs as $visiteur): ?>
                        <?php $idVis = (int) $visiteur['id_vis']; ?>
                        <label>
                            <input type="checkbox" name="organisateurs[]" value="<?= $idVis ?>"
                                   <?= in_array($idVis, $form['organisateurs'], true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($visiteur['prenom_vis'] . ' ' . $visiteur['nom_vis']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-primary"><?= htmlspecialchars($buttonLabel) ?></button>
        <a href="index.php?action=admin_ac" class="btn-secondary">Annuler</a>
    </form>
</div>
