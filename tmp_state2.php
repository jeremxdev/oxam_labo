<?php
/**
 * Script temporaire de diagnostic de l'état de la base après les migrations.
 *
 * Affiche : la base courante, tous les praticiens avec l'état de leur mot de
 * passe (vide / oxam2025 / autre), tous les visiteurs, puis recherche les
 * occurrences de "gasnier" dans les colonnes nom/prénom/email de toutes les tables.
 * En lecture seule : php tmp_state2.php.
 */
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

echo "Base courante : " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n\n";

echo "== Practicien (tous) ==\n";
foreach ($pdo->query("SELECT Id_pra, id_spe, nom_par, prenom_par, mail_par, mot_de_passe_par FROM Practicien ORDER BY Id_pra") as $r) {
    $mdp = (string) ($r['mot_de_passe_par'] ?? '');
    $etat = $mdp === '' ? 'VIDE' : (password_verify('oxam2025', $mdp) ? 'oxam2025' : 'autre');
    echo "{$r['Id_pra']}: {$r['prenom_par']} {$r['nom_par']} | {$r['mail_par']} | spe={$r['id_spe']} | mdp=$etat\n";
}

echo "\n== Visiteur (tous) ==\n";
foreach ($pdo->query("SELECT id_vis, nom_vis, prenom_vis, mail_vis FROM Visiteur ORDER BY id_vis") as $r) {
    echo "{$r['id_vis']}: {$r['prenom_vis']} {$r['nom_vis']} | {$r['mail_vis']}\n";
}

echo "\n== Recherche 'gasnier' (toutes tables) ==\n";
foreach ($pdo->query("SHOW TABLES") as $t) {
    $table = array_values($t)[0];
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    $textCols = array_values(array_filter($cols, function ($c) {
        return stripos($c, 'mail') !== false || stripos($c, 'nom') !== false || stripos($c, 'prenom') !== false;
    }));
    foreach ($textCols as $col) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` LIKE '%gasnier%'");
        $stmt->execute();
        $n = (int) $stmt->fetchColumn();
        if ($n > 0) {
            echo "  $table.$col : $n ligne(s)\n";
        }
    }
}
