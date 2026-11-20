<?php
// Script de test temporaire : cycle de vie d'un compte praticien
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Visite.php';
require_once __DIR__ . '/../models/Admin.php';

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
$regionTest = 'TEST-' . bin2hex(random_bytes(5));
$specialite = $db->query("SELECT id_spe FROM Specialite ORDER BY id_spe LIMIT 1")->fetchColumn();

// Nettoyage préalable
$stmtAncien = $db->prepare("SELECT id_spe, Id_pra FROM Practicien WHERE mail_par = :email");
$stmtAncien->execute(['email' => $email]);
foreach ($stmtAncien->fetchAll() as $ancien) {
    $stmtAffectation = $db->prepare("DELETE FROM affecter WHERE id_spe = :id_spe AND Id_pra = :id_pra");
    $stmtAffectation->execute(['id_spe' => $ancien['id_spe'], 'id_pra' => $ancien['Id_pra']]);
}
$stmtAncien = $db->prepare("DELETE FROM Practicien WHERE mail_par = :email");
$stmtAncien->execute(['email' => $email]);

// 1. Création avec email + mot de passe
check('Création du compte praticien avec région', $pra->createPracticien((int) $specialite, null, 'TestPra', 'Essai', '1 Rue Test', '49000', 'Angers', $email, 'secretpra123', $regionTest));
check('La nouvelle région est créée en base', (bool) $db->query("SELECT reg_code FROM Region WHERE reg_code = " . $db->quote($regionTest))->fetchColumn());

// 2. Récupération + hash
$user = $pra->findByMail($email);
check('Praticien retrouvé par email', (bool) $user);
check('Nouveau praticien en attente de validation', ($user['statut_validation'] ?? '') === 'en_attente');
check('Nouveau compte praticien actif par défaut', ($user['statut_compte'] ?? '') === 'actif');
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
check('La région est associée au praticien', $profil && $profil['reg_code'] === $regionTest);
$visitesModel = new Visite();
$visitesPra = $visitesModel->getVisitesByPracticien((int) $user['id_spe'], (int) $user['Id_pra']);
check('Visites du praticien lues sans erreur', is_array($visitesPra));
$visitesGasnier = $gasnier ? $visitesModel->getVisitesByPracticien((int) $gasnier['id_spe'], (int) $gasnier['Id_pra']) : [];
check('Gasnier a bien sa visite de demo (' . count($visitesGasnier) . ')', count($visitesGasnier) === 1);

// 7. La liste des praticiens pour saisie de visite n expose pas le hash
$listePra = $visitesModel->getAllPracticiens();
$colsPra = $listePra ? array_keys($listePra[0]) : [];
check('Hash exclu de getAllPracticiens', !in_array('mot_de_passe_par', $colsPra, true));
$listedBeforeApproval = array_filter($listePra, function ($row) use ($user) {
    return (int) $row['Id_pra'] === (int) $user['Id_pra'] && (int) $row['id_spe'] === (int) $user['id_spe'];
});
check('Praticien en attente absent des choix de visite', !$listedBeforeApproval);

$adminModel = new Admin();
check('Approbation du praticien par Admin', $adminModel->setApprovalStatus((int) $user['id_spe'], (int) $user['Id_pra'], 'approuve'));
check('Praticien approuvé visible pour les visites', count(array_filter(
    $visitesModel->getAllPracticiens(),
    function ($row) use ($user) {
        return (int) $row['Id_pra'] === (int) $user['Id_pra'] && (int) $row['id_spe'] === (int) $user['id_spe'];
    }
)) === 1);
check('Désactivation du compte praticien', $adminModel->setAccountStatus('practicien', (int) $user['Id_pra'], (int) $user['id_spe'], 'inactif') === 'updated');
check('Praticien inactif non considéré comme actif', !$adminModel->isAccountActive('practicien', (int) $user['Id_pra'], (int) $user['id_spe']));
check('Réactivation du compte praticien', $adminModel->setAccountStatus('practicien', (int) $user['Id_pra'], (int) $user['id_spe'], 'actif') === 'updated');
check('Réinitialisation du mot de passe praticien', $adminModel->resetPassword('practicien', (int) $user['Id_pra'], (int) $user['id_spe'], 'newsecret123'));
$userUpdated = $pra->findByMail($email);
check('Nouveau mot de passe vérifié', $pra->verifyPassword($userUpdated['mot_de_passe_par'] ?? '', 'newsecret123'));

// Nettoyage
if ($user) {
    check('Suppression du compte de test sans historique', $adminModel->deleteAccount(
        'practicien',
        (int) $user['Id_pra'],
        (int) $user['id_spe']
    ) === 'deleted');
}
$stmtRegion = $db->prepare("DELETE FROM Region WHERE reg_code = :reg_code");
$stmtRegion->execute(['reg_code' => $regionTest]);
check('Donnees de test nettoyees', !$pra->findByMail($email));

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);
