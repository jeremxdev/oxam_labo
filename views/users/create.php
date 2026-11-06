<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="form-card">
    <h1>Créer un profil Utilisateur (LBC)</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="index.php?action=user_create" method="POST">
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required placeholder="Ex: Dupont">
        </div>

        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" required placeholder="Ex: Jean">
        </div>

        <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" required placeholder="jean.dupont@oxam.fr">
        </div>

        <div class="form-group">
            <label for="password">Mot de passe d'accès</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="role">Profil Hiérarchique (CDC LBC)</label>
            <select name="role" id="role" required>
                <option value="VISITEUR">Visiteur Médical (Terrain)</option>
                <option value="DELEGUE_REGIONAL">Délégué Régional (Intermédiaire / 3/4 visite)</option>
                <option value="RESPONSABLE_SECTEUR">Responsable de Secteur (Encadrement)</option>
                <option value="GESTIONNAIRE">Gestionnaire du Laboratoire (RH / Admin)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="id_region">Région géographique</label>
            <select name="id_region" id="id_region" required>
                <option value="">-- Choisir la région --</option>
                <?php foreach ($regions as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom'] . ' (' . $r['secteur_nom'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-primary">Enregistrer le profil</button>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
