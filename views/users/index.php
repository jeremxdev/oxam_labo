<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="header-action">
    <h1>Gestion de la Flotte & du Personnel (RH)</h1>
    <a href="index.php?action=user_create" class="btn-primary">+ Créer un profil</a>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom & Prénom</th>
                <th>Email</th>
                <th>Profil / Rôle</th>
                <th>Région d'affectation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge role-badge"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><?= htmlspecialchars($u['region_nom'] ?? 'Siège / Global') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
