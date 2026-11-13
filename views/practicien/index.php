<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="header-action">
    <h1>Espace Praticien</h1>
    <a href="index.php?action=practicien_profil" class="btn-primary">Modifier mon profil</a>
</div>

<div class="form-card">
    <h2>Mon profil</h2>
    <p class="subtitle">Dr. <?= htmlspecialchars($profil['prenom_par'] . ' ' . $profil['nom_par']) ?></p>

    <div class="form-group">
        <label>Spécialité</label>
        <p><span class="badge"><?= htmlspecialchars($profil['nom_spe'] ?? 'Non renseignée') ?></span></p>
    </div>
    <div class="form-group">
        <label>Adresse email</label>
        <p><?= htmlspecialchars($profil['mail_par']) ?></p>
    </div>
    <div class="form-group">
        <label>Adresse</label>
        <p><?= htmlspecialchars($profil['rue_par'] . ', ' . $profil['codePostal'] . ' ' . $profil['ville_par']) ?></p>
    </div>
    <?php if (!empty($profil['note_par'])): ?>
        <div class="form-group">
            <label>Note</label>
            <p><?= htmlspecialchars($profil['note_par']) ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="header-action">
    <h2>Visites réalisées par mes visiteurs</h2>
</div>

<?php if (empty($visites)): ?>
    <div class="alert alert-success">Aucune visite enregistrée pour le moment.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Visiteur</th>
                    <th>Spécialité</th>
                    <th>Motif</th>
                    <th>Bilan / Compte Rendu</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visites as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['date_vis']) ?></td>
                        <td><strong><?= htmlspecialchars($v['nom_vis'] . ' ' . $v['prenom_vis']) ?></strong></td>
                        <td><span class="badge"><?= htmlspecialchars($v['nom_spe']) ?></span></td>
                        <td><?= htmlspecialchars($v['motif_vis']) ?></td>
                        <td><?= htmlspecialchars($v['bilan_vis'] ?? 'Non rédigé') ?></td>
                        <td class="actions-cell">
                            <a href="index.php?action=visite_pdf&id=<?= $v['id_vis'] ?>" target="_blank" class="btn-pdf">PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
