<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="header-action">
    <h1>Flotte des Visiteurs Médicaux</h1>
    <a href="index.php?action=visiteur_create" class="btn-primary">+ Nouveau Visiteur</a>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom & Prénom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Région d'affectation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($visiteurs as $v): ?>
                <tr>
                    <td>#<?= $v['id_vis'] ?></td>
                    <td><strong><?= htmlspecialchars($v['nom_vis'] . ' ' . $v['prenom_vis']) ?></strong></td>
                    <td><?= htmlspecialchars($v['mail_vis']) ?></td>
                    <td><?= htmlspecialchars($v['telephone_vis']) ?></td>
                    <td><?= htmlspecialchars($v['adresse_vis']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($v['reg_code'] ?? 'Non affecté') ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
