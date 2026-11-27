<?php
// Liste d'administration des activités complémentaires : affiche $activites (thème, lieu, date,
// organisateurs agrégés par ACModel::getAllAC()) et les messages $success / $error issus de l'URL.
// Chaque ligne propose une modification (lien GET) et une suppression (formulaire POST protégé CSRF).
// Inclus par AdminAcController::index() (route "admin_ac" de index.php).
include __DIR__ . '/../../layout/header.php';
?>
<section>
    <div class="header-action">
        <h1>Gestion des activités complémentaires</h1>
        <a href="index.php?action=admin_ac_create" class="btn-primary">+ Nouvelle AC</a>
    </div>
    <p class="subtitle">Créez, modifiez ou supprimez les activités complémentaires et choisissez leurs organisateurs.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!$activites): ?>
        <p>Aucune activité complémentaire enregistrée.</p>
    <?php else: ?>
        <div class="table-responsive" style="margin-top: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Thème</th>
                        <th>Lieu</th>
                        <th>Organisateurs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activites as $ac): ?>
                        <?php $idAc = (int) $ac['id_ac']; ?>
                        <tr>
                            <td><?= htmlspecialchars($ac['date_ac']) ?></td>
                            <td><strong><?= htmlspecialchars($ac['theme']) ?></strong></td>
                            <td><?= htmlspecialchars($ac['lieu']) ?></td>
                            <td><?= htmlspecialchars($ac['organisateurs'] ?? 'Aucun') ?></td>
                            <td>
                                <a href="index.php?action=admin_ac_edit&amp;id=<?= $idAc ?>" class="btn-primary-sm">Modifier</a>
                                <!-- Suppression en POST : le jeton CSRF est exigé par le contrôleur. -->
                                <form action="index.php?action=admin_ac_delete" method="POST" class="inline-form"
                                      onsubmit="return confirm('Supprimer cette activité complémentaire ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="id" value="<?= $idAc ?>">
                                    <button type="submit" class="btn-delete">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../../layout/footer.php'; ?>
