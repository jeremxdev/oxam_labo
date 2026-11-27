<?php
// Layout commun : ferme la balise <main> ouverte dans header.php et affiche le pied de page
// du portail (copyright dynamique avec l'année courante via date('Y')), puis charge le JS global.
// Inclus à la fin de chaque vue, juste après son contenu.
?>
    </main>
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Laboratoire Bio Company (OXAM Labo) - Portail Visiteurs Médicaux.</p>
    </footer>
    <script src="public/js/main.js"></script>
</body>
</html>
