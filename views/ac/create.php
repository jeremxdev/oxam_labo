<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="form-card">
    <h1>Organiser une Activité Complémentaire</h1>
    <form action="index.php?action=ac_create" method="POST">
        <div class="form-group">
            <label for="theme">Thème (Max 20 car.)</label>
            <input type="text" id="theme" name="theme" maxlength="20" required placeholder="Cardiologie 2026">
        </div>

        <div class="form-group">
            <label for="lieu">Lieu (Max 50 car.)</label>
            <input type="text" id="lieu" name="lieu" maxlength="50" required placeholder="Palais des Congrès, Cholet">
        </div>

        <div class="form-group">
            <label for="date_ac">Date</label>
            <input type="date" id="date_ac" name="date_ac" required value="<?= date('Y-m-d') ?>">
        </div>

        <button type="submit" class="btn-primary">Créer l'AC</button>
    </form>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
