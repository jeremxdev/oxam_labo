<?php
// Tableau de bord administrateur : gestion des comptes (validation, activation, suppression).
// Affiche $pendingPracticiens (demandes de praticiens à approuver), $accounts (tous les comptes),
// les messages $error / $success et deux tableaux HTML avec boucles foreach.
// Chaque action est un formulaire POST protégé par le jeton CSRF de la session.
// Inclus par AdminController::index() (route "admin" de index.php).
include __DIR__ . '/../layout/header.php';
$typeLabels = ['visiteur' => 'Visiteur', 'practicien' => 'Praticien', 'admin' => 'Administrateur'];
$approvalLabels = ['en_attente' => 'En attente', 'approuve' => 'Approuvé', 'refuse' => 'Refusé'];
?>
<section>
    <h1>Gestion des comptes</h1>
    <p class="subtitle">Modifiez les comptes, changez leur statut, réinitialisez leur mot de passe ou supprimez-les.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <h2>Demandes praticien en attente (<?= count($pendingPracticiens) ?>)</h2>
    <?php if (!$pendingPracticiens): ?>
        <p>Aucune demande en attente.</p>
    <?php else: ?>
        <div class="table-responsive" style="margin: 1rem 0 2rem;">
            <table class="data-table">
                <thead>
                    <tr><th>Nom</th><th>Email</th><th>Spécialité</th><th>Décision</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPracticiens as $practicien): ?>
                        <tr>
                            <td><?= htmlspecialchars($practicien['prenom_par'] . ' ' . $practicien['nom_par']) ?></td>
                            <td><?= htmlspecialchars($practicien['mail_par']) ?></td>
                            <td><?= htmlspecialchars($practicien['nom_spe']) ?></td>
                            <td>
                                <?php foreach (['approuve' => 'Valider', 'refuse' => 'Refuser'] as $status => $label): ?>
                                    <form action="index.php?action=admin" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="operation" value="approval_status">
                                        <input type="hidden" name="type" value="practicien">
                                        <input type="hidden" name="id" value="<?= (int) $practicien['Id_pra'] ?>">
                                        <input type="hidden" name="id_spe" value="<?= (int) $practicien['id_spe'] ?>">
                                        <input type="hidden" name="statut_validation" value="<?= htmlspecialchars($status) ?>">
                                        <button type="submit" class="<?= $status === 'approuve' ? 'btn-primary-sm' : 'btn-delete' ?>"><?= htmlspecialchars($label) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Comptes existants (<?= count($accounts) ?>)</h2>
    <div class="table-responsive" style="margin-top: 1rem;">
        <table class="data-table">
            <thead>
                <tr><th>Type</th><th>Nom</th><th>Email</th><th>Statut</th><th>Gestion</th></tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <?php
                    $type = $account['type'];
                    $id = (int) $account['id'];
                    $idSpe = (int) ($account['id_spe'] ?? 0);
                    $accountStatus = $account['statut_compte'] === 'actif' ? 'Actif' : 'Inactif';
                    $statusText = $type === 'practicien'
                        ? ($approvalLabels[$account['statut_validation']] ?? 'Inconnu') . ' · ' . $accountStatus
                        : $accountStatus;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($typeLabels[$type] ?? $type) ?></td>
                        <td><?= htmlspecialchars(trim(($account['prenom'] ?? '') . ' ' . ($account['nom'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($account['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($statusText) ?></td>
                        <td>
                            <?php if ($type === 'admin'): ?>
                                <a class="btn-secondary" href="index.php?action=admin_edit&amp;type=<?= urlencode($type) ?>&amp;id=<?= $id ?>&amp;id_spe=<?= $idSpe ?>">Modifier</a>
                            <?php endif; ?>

                            <?php if ($type === 'practicien'): ?>
                                <form action="index.php?action=admin" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="operation" value="approval_status">
                                    <input type="hidden" name="type" value="practicien">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="id_spe" value="<?= $idSpe ?>">
                                    <select name="statut_validation" aria-label="Statut de validation">
                                        <?php foreach ($approvalLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $account['statut_validation'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-primary-sm">Appliquer</button>
                                </form>
                            <?php endif; ?>

                            <form action="index.php?action=admin" method="POST" style="display:inline-block;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="operation" value="account_status">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="id_spe" value="<?= $idSpe ?>">
                                <select name="statut_compte" aria-label="Statut du compte">
                                    <option value="actif" <?= $account['statut_compte'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= $account['statut_compte'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                </select>
                                <button type="submit" class="btn-primary-sm">Changer</button>
                            </form>

                            <form action="index.php?action=admin" method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer ce compte ? Les comptes liés à des visites ou activités ne peuvent pas être supprimés.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="operation" value="delete_account">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="id_spe" value="<?= $idSpe ?>">
                                <button type="submit" class="btn-delete">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>
