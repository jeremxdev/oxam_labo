<?php // Présente un compte-rendu de visite en page autonome pour impression ou enregistrement PDF. ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte Rendu de Visite #<?= $visite['id_vis'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 30px; }
        .header h1 { color: #1e3a8a; margin: 0; }
        .section { margin-bottom: 20px; }
        .label { font-weight: bold; color: #1e3a8a; }
        .box { background: #f8fafc; padding: 15px; border-left: 4px solid #0d9488; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
        .btn-print { margin-bottom: 20px; padding: 10px 20px; background: #1e3a8a; color: white; border: none; cursor: pointer; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    <div class="header">
        <h1>LABORATOIRE LBC - COMPTE RENDU DE VISITE</h1>
        <p>Document officiel OXAM LABO</p>
    </div>

    <div class="section">
        <p><span class="label">Date de la visite :</span> <?= htmlspecialchars($visite['date_vis']) ?></p>
        <p><span class="label">Visiteur Médical :</span> <?= htmlspecialchars($visite['prenom_vis'] . ' ' . $visite['nom_vis']) ?></p>
        <p><span class="label">Practicien Visité :</span> Dr. <?= htmlspecialchars($visite['prenom_par'] . ' ' . $visite['nom_par']) ?> (<?= htmlspecialchars($visite['nom_spe']) ?>)</p>
        <p><span class="label">Adresse :</span> <?= htmlspecialchars($visite['rue_par'] . ', ' . $visite['codePostal'] . ' ' . $visite['ville_par']) ?></p>
    </div>

    <div class="section">
        <span class="label">Motif :</span>
        <div class="box"><?= htmlspecialchars($visite['motif_vis']) ?></div>
    </div>

    <div class="section">
        <span class="label">Bilan :</span>
        <div class="box"><?= nl2br(htmlspecialchars($visite['bilan_vis'] ?? 'Aucun bilan rédigé.')) ?></div>
    </div>

    <div class="section">
        <span class="label">Médicaments présentés :</span>
        <?php if (!empty($visite['medicaments_presentes'])): ?>
            <ul>
                <?php foreach ($visite['medicaments_presentes'] as $mp): ?>
                    <li><?= htmlspecialchars($mp['nomCommercial_med']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun produit présenté.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <span class="label">Échantillons offerts :</span>
        <?php if (!empty($visite['echantillons'])): ?>
            <table>
                <thead>
                    <tr><th>Médicament</th><th>Quantité</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($visite['echantillons'] as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['nomCommercial_med']) ?></td>
                            <td><?= htmlspecialchars($e['qteEchantillon_med']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun échantillon offert.</p>
        <?php endif; ?>
    </div>
</body>
</html>
