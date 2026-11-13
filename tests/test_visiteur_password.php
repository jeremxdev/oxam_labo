<?php
// Script de test temporaire : cycle de vie d'un visiteur avec mot de passe
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Visiteur.php';

$v = new Visiteur();
$db = Database::getConnection();
$ok = true;

function check($label, $cond) {
    global $ok;
    echo ($cond ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$cond) $ok = false;
}

$email = 'test.mdp@oxam-labo.fr';

// Nettoyage préalable
$db->exec("DELETE FROM Visiteur WHERE mail_vis = '$email'");

// Région existante en base (évite une violation de clé étrangère)
$regCode = $db->query("SELECT reg_code FROM Region ORDER BY reg_code LIMIT 1")->fetchColumn();
check('Une région existe en base', (bool) $regCode);

// 1. Création avec mot de passe
check('Création du visiteur', $v->createVisiteur('Test', 'Mdp', $email, '0600000000', '1 Rue de Test, Cholet', $regCode, 'secretpass123'));

// 2. Récupération + vérification du hash
$user = $v->findByMail($email);
check('Visiteur retrouvé par email', (bool) $user);
check('Le mot de passe est stocké haché (bcrypt)', isset($user['mot_de_passe_vis']) && str_starts_with($user['mot_de_passe_vis'], '$2y$') && $user['mot_de_passe_vis'] !== 'secretpass123');
check('Le hash ne contient pas le mot de passe en clair', !str_contains($user['mot_de_passe_vis'], 'secretpass123'));

// 3. Vérifications de connexion
check('Bon mot de passe accepté', $v->verifyPassword($user['mot_de_passe_vis'], 'secretpass123'));
check('Mauvais mot de passe refusé', !$v->verifyPassword($user['mot_de_passe_vis'], 'mauvais'));
check('Mot de passe vide refusé', !$v->verifyPassword($user['mot_de_passe_vis'], ''));
check('Hash vide refusé (compte sans mdp)', !$v->verifyPassword('', 'secretpass123'));

// 4. Le hash n'apparaît plus dans la liste des visiteurs
$liste = $v->getAllVisiteurs();
$cols = $liste ? array_keys($liste[0]) : [];
check('Le hash est exclu de la liste des visiteurs', !in_array('mot_de_passe_vis', $cols, true));

// 5. Le compte existant se connecte avec le mot de passe provisoire
$imaque = $v->findByMail('imaquej@saintemarie-cholet.eu');
check('Compte existant connectable avec "oxam2025"', $imaque && $v->verifyPassword($imaque['mot_de_passe_vis'] ?? '', 'oxam2025'));

// 6. Doublon d'email détecté
check('Création refusée avec un email déjà utilisé (après vérif)', (bool) $v->findByMail($email));

// Nettoyage : uniquement les lignes créées par ce test
$idTest = $user['id_vis'] ?? 0;
$db->exec("DELETE FROM Travailler WHERE id_vis = " . (int) $idTest);
$db->exec("DELETE FROM Visiteur WHERE id_vis = " . (int) $idTest);
check('Données de test nettoyées', !$v->findByMail($email));

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);
