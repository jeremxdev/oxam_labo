<?php
// Liste les activités complémentaires et leurs organisateurs.
include __DIR__ . '/../layout/header.php';
?>
<div class="header-action">
    <h1>Activités Complémentaires (AC)</h1>
    <a href="index.php?action=ac_create" class="btn-primary">+ Organiser une AC</a>
</div>

<div class="table-responsive" style="margin-top:20px;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Thème</th>
                <th>Lieu</th>
                <th>Organisateurs (Visiteurs)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activites as $ac): ?>
                <tr>
                    <td><?= htmlspecialchars($ac['date_ac']) ?></td>
                    <td><strong><?= htmlspecialchars($ac['theme']) ?></strong></td>
                    <td><?= htmlspecialchars($ac['lieu']) ?></td>
                    <td><?= htmlspecialchars($ac['organisateurs'] ?? 'Aucun') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
