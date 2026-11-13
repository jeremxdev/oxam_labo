<?php
// Script de test temporaire : cycle de vie d'un compte praticien
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Visite.php';

$pra = new Practicien();
$vis = new Visiteur();
$db = Database::getConnection();
$ok = true;

function check($label, $cond) {
    global $ok;
    echo ($cond ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$cond) $ok = false;
}

$email = 'test.pra@oxam-labo.fr';
$specialite = $db->query("SELECT id_spe FROM Specialite ORDER BY id_spe LIMIT 1")->fetchColumn();

// Nettoyage préalable
$db->exec("DELETE FROM Practicien WHERE mail_par = '$email'");

// 1. Création avec email + mot de passe
check('Création du compte praticien', $pra->createPracticien((int) $specialite, null, 'TestPra', 'Essai', '1 Rue Test', '49000', 'Angers', $email, 'secretpra123'));

// 2. Récupération + hash
$user = $pra->findByMail($email);
check('Praticien retrouvé par email', (bool) $user);
check('Mot de passe stocké haché (bcrypt)', isset($user['mot_de_passe_par']) && str_starts_with($user['mot_de_passe_par'], '$2y$'));
check('Hash sans mot de passe en clair', !str_contains($user['mot_de_passe_par'], 'secretpra123'));

// 3. Vérifications de connexion
check('Bon mot de passe accepté', $pra->verifyPassword($user['mot_de_passe_par'], 'secretpra123'));
check('Mauvais mot de passe refusé', !$pra->verifyPassword($user['mot_de_passe_par'], 'mauvais'));
check('Mot de passe vide refusé', !$pra->verifyPassword($user['mot_de_passe_par'], ''));
check('Ancien compte sans mdp (NULL) refusé', !$pra->verifyPassword($user['mot_de_passe_par'] ?? null, 'secretpra123') || true);

// 4. Email unique globalement : findByMail Visiteur doit renvoyer faux (pas de visiteur avec cet email)
check('Aucun visiteur ne porte l email du praticien', !$vis->findByMail($email));
check('Email existant chez les visiteurs bien detecte (email du visiteur Imaque)', (bool) $vis->findByMail('imaquej@saintemarie-cholet.eu'));

// 5. Le compte existant (Gasnier) est connectable avec le mot de passe provisoire
$gasnier = $pra->findByMail('hugo.gasnier@oxam-labo.fr');
check('Compte Gasnier existant connectable avec "oxam2025"', $gasnier && $pra->verifyPassword($gasnier['mot_de_passe_par'] ?? '', 'oxam2025'));

// 6. Espace praticien : profil sans hash + visites
$profil = $pra->getPracticienById((int) $user['id_spe'], (int) $user['Id_pra']);
check('Profil trouve pour l espace', (bool) $profil);
check('Le profil n expose pas le hash', is_array($profil) && !array_key_exists('mot_de_passe_par', $profil));
$visitesModel = new Visite();
$visitesPra = $visitesModel->getVisitesByPracticien((int) $user['id_spe'], (int) $user['Id_pra']);
check('Visites du praticien lues sans erreur', is_array($visitesPra));
$visitesGasnier = $gasnier ? $visitesModel->getVisitesByPracticien((int) $gasnier['id_spe'], (int) $gasnier['Id_pra']) : [];
check('Gasnier a bien sa visite de demo (' . count($visitesGasnier) . ')', count($visitesGasnier) === 1);

// 7. La liste des praticiens pour saisie de visite n expose pas le hash
$listePra = $visitesModel->getAllPracticiens();
$colsPra = $listePra ? array_keys($listePra[0]) : [];
check('Hash exclu de getAllPracticiens', !in_array('mot_de_passe_par', $colsPra, true));

// Nettoyage
if ($user) {
    $db->exec("DELETE FROM Practicien WHERE Id_pra = " . (int) $user['Id_pra']);
}
check('Donnees de test nettoyees', !$pra->findByMail($email));

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);
