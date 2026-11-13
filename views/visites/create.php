<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="form-card">
    <h1>Saisie d'un Rapport de Visite</h1>
    <form action="index.php?action=visite_create" method="POST" id="visitForm">
        <div class="form-group">
            <label for="practicien">Praticien visité</label>
            <select name="practicien" id="practicien" required>
                <option value="">-- Choisir un praticien --</option>
                <?php foreach ($practiciens as $p): ?>
                    <option value="<?= $p['id_spe'] . '-' . $p['Id_pra'] ?>"><?= htmlspecialchars($p['nom_par'] . ' ' . $p['prenom_par'] . ' (' . $p['nom_spe'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="date_vis">Date de la Visite</label>
            <input type="date" id="date_vis" name="date_vis" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="motif_vis">Motif de la Visite (Max 15 car.)</label>
            <input type="text" id="motif_vis" name="motif_vis" maxlength="15" required placeholder="Ex: Nouveautés">
        </div>

        <div class="form-group">
            <label for="bilan_vis">Bilan / Compte Rendu (Max 200 car.)</label>
            <textarea id="bilan_vis" name="bilan_vis" rows="3" maxlength="200" placeholder="Avis du médecin, niveau de conviction..."></textarea>
        </div>

        <h3>Médicaments Présentés (Presenter)</h3>
        <div class="samples-grid">
            <?php foreach ($medicaments as $m): ?>
                <label class="sample-item">
                    <input type="checkbox" name="meds_presentes[]" value="<?= $m['id_med'] ?>">
                    <span><?= htmlspecialchars($m['nomCommercial_med']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top:1.5rem;">Échantillons Offerts (Offrir)</h3>
        <div class="samples-grid">
            <?php foreach ($medicaments as $m): ?>
                <div class="sample-item">
                    <span><?= htmlspecialchars($m['nomCommercial_med']) ?></span>
                    <input type="number" name="echantillons[<?= $m['id_med'] ?>]" min="0" value="0">
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:1.5rem;">Enregistrer la Visite</button>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
