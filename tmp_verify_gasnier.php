<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Practicien.php';
require_once __DIR__ . '/models/Visiteur.php';
require_once __DIR__ . '/models/Visite.php';

$ok = true;
function check($label, $cond) {
    global $ok;
    echo ($cond ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$cond) $ok = false;
}

echo "=== 1. Connexion réelle (logique de l'application) ===\n";
$pra = new Practicien();
$vis = new Visiteur();

$g = $pra->findByMail('hugo.gasnier@oxam-labo.fr');
check('Praticien retrouvé par email', (bool) $g);
check('Mot de passe oxam2025 accepté', $g && $pra->verifyPassword($g['mot_de_passe_par'] ?? '', 'oxam2025'));
check('Mauvais mot de passe refusé', !$pra->verifyPassword($g['mot_de_passe_par'] ?? '', 'mauvais'));
check('Empreinte bcrypt', isset($g['mot_de_passe_par']) && str_starts_with($g['mot_de_passe_par'], '$2y$'));
check('Spécialité Cardiologie', ($g['nom_spe'] ?? '') === 'Cardiologie');
check('Email non utilisé par un visiteur', !$vis->findByMail('hugo.gasnier@oxam-labo.fr'));
check('Liste des praticiens présente', in_array('hugo.gasnier@oxam-labo.fr', array_column((new VisiteSafe())->getAllPracticiens(), 'mail_par'), true));

foreach (['jean.dupont@oxam-labo.fr', 'sophie.martin@oxam-labo.fr', 'luc.bernard@oxam-labo.fr', 'claire.moreau@oxam-labo.fr'] as $email) {
    $u = $vis->findByMail($email);
    if ($u) {
        check("Toujours connectable : $email", $vis->verifyPassword($u['mot_de_passe_vis'] ?? '', 'oxam2025'));
    } else {
        $p = $pra->findByMail($email);
        check("Toujours connectable : $email", $p && $pra->verifyPassword($p['mot_de_passe_par'] ?? '', 'oxam2025'));
    }
}

echo "\n=== 2. schema.sql importé dans une base temporaire ===\n";
$pdo = Database::getConnection();
$pdo->exec("DROP DATABASE IF EXISTS oxam_labo_tmpcheck");
$pdo->exec("CREATE DATABASE oxam_labo_tmpcheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$sql = file_get_contents(__DIR__ . '/schema.sql');
// On neutralise la création/use de la base cible pour tout importer dans la base temporaire.
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS oxam_labo[^;]*;/i', '', $sql);
$sql = preg_replace('/USE oxam_labo\s*;/i', '', $sql);

$import = new PDO('mysql:host=localhost;dbname=oxam_labo_tmpcheck;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
try {
    $import->exec($sql);
    check('Import de schema.sql sans erreur', true);
} catch (PDOException $e) {
    check('Import de schema.sql sans erreur (' . $e->getMessage() . ')', false);
}

$rows = $import->query("SELECT Id_pra, id_spe, nom_par, prenom_par, mail_par FROM Practicien ORDER BY Id_pra")->fetchAll();
check('3 praticiens de démonstration', count($rows) === 3);
$g2 = null;
foreach ($rows as $r) {
    if ($r['mail_par'] === 'hugo.gasnier@oxam-labo.fr') $g2 = $r;
}
check('Hugo Gasnier présent après import', (bool) $g2);
check('Hugo Gasnier rattaché à la Cardiologie (id_spe=1)', $g2 && (int) $g2['id_spe'] === 1);
$hashImport = $import->query("SELECT mot_de_passe_par FROM Practicien WHERE mail_par = 'hugo.gasnier@oxam-labo.fr'")->fetchColumn();
check('Mot de passe oxam2025 après import', password_verify('oxam2025', (string) $hashImport));
check('Portefeuille de Jean Dupont : 3 praticiens', (int) $import->query("SELECT COUNT(*) FROM portefeuille WHERE id_vis = 1")->fetchColumn() === 3);

$import = null;
$pdo->exec("DROP DATABASE IF EXISTS oxam_labo_tmpcheck");
echo "Base temporaire supprimée\n";

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);

// Petit adaptateur pour appeler getAllPracticiens sans dépendre du reste
class VisiteSafe {
    public function getAllPracticiens() {
        require_once __DIR__ . '/models/Visite.php';
        $v = new Visite();
        return $v->getAllPracticiens();
    }
}
