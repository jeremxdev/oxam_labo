<?php
// Liste des rapports de visite du visiteur connecté ($visites) avec recherche client-side et actions.
// Prépare la légende $statutsVisite et filtre $visitesRefusees (statut "refusée") pour l'alerte.
// Chaque ligne offre le PDF et, si le bilan est vide, la suppression (lien GET avec confirmation JS).
// Inclus par VisiteController::index() (route "visites" de index.php).
$statutsVisite = ['en_attente' => 'En attente', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée'];
$visitesRefusees = array_filter($visites, function ($v) {
    return ($v['statut_vis'] ?? 'en_attente') === 'refusee';
});
include __DIR__ . '/../layout/header.php';
?>
<div class="header-action">
    <h1>Comptes Rendus de Visites Médicales</h1>
    <a href="index.php?action=visite_create" class="btn-primary">+ Saisir une visite</a>
</div>

<?php if (isset($_GET['err']) && $_GET['err'] === 'cannot_delete'): ?>
    <div class="alert alert-danger">Suppression impossible : Une visite comportant un compte-rendu ou des échantillons offerts ne peut pas être supprimée (CDC LBC).</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Visite supprimée avec succès.</div>
<?php endif; ?>
<?php if (!empty($visitesRefusees)): ?>
    <div class="alert alert-danger">
        Une ou plusieurs visites ont été refusées par le praticien : elles n'auront pas lieu. Le détail est indiqué sur votre profil.
    </div>
<?php endif; ?>

<div class="search-box">
    <input type="text" id="searchInput" placeholder="Rechercher par praticien, spécialité ou motif..." onkeyup="filterVisits()">
</div>

<div class="table-responsive">
    <table class="data-table" id="visitsTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Practicien</th>
                <th>Spécialité</th>
                <th>Motif</th>
                <th>Statut</th>
                <th>Bilan / Compte Rendu</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($visites as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['date_vis']) ?></td>
                    <td><strong><?= htmlspecialchars($v['nom_par'] . ' ' . $v['prenom_par']) ?></strong></td>
                    <td><span class="badge"><?= htmlspecialchars($v['nom_spe']) ?></span></td>
                    <td><?= htmlspecialchars($v['motif_vis']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($v['statut_vis'] ?? 'en_attente') ?>"><?= $statutsVisite[$v['statut_vis'] ?? 'en_attente'] ?></span></td>
                    <td><?= htmlspecialchars($v['bilan_vis'] ?? 'Non rédigé') ?></td>
                    <td class="actions-cell">
                        <a href="index.php?action=visite_pdf&id=<?= $v['id_vis'] ?>" target="_blank" class="btn-pdf">PDF</a>
                        <?php if (empty($v['bilan_vis'])): ?>
                            <a href="index.php?action=visite_delete&id=<?= $v['id_vis'] ?>" class="btn-delete" onclick="return confirm('Confirmer la suppression ?')">Suppr.</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
