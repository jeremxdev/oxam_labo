<?php
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

foreach (['visite', 'region', 'specialite', 'medicament', 'ac', 'travailler', 'portefeuille', 'affecter', 'participe', 'organiser', 'presenter', 'offrir'] as $t) {
    echo "== $t ==\n";
    $n = 0;
    foreach ($pdo->query("SELECT * FROM `$t`") as $r) {
        echo '  ' . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
        if (++$n >= 15) { echo "  ...\n"; break; }
    }
    if ($n === 0) echo "  (vide)\n";
}
