<?php
/**
 * Test de la gestion des comptes par l'administrateur (couche modèle).
 *
 * Rôle : vérifier, en base réelle, les opérations exposées au back-office par
 * Admin + Visiteur : activation/désactivation, réinitialisation de mot de passe,
 * suppression (avec protection contre la suppression d'un compte « à historique »
 * et contre la désactivation du dernier admin actif).
 *
 * Lancement : php tests/test_admin_accounts.php (uniquement en CLI — le script
 * renvoie un 404 s'il est appelé depuis un navigateur).
 * Interprétation : chaque ligne affiche [OK] ou [FAIL] ; le script se termine
 * par « TOUS LES TESTS PASSENT » (code retour 0) ou « DES TESTS ONT ECHOUE »
 * (code retour 1). Il crée puis supprime ses propres comptes de test.
 */
if (PHP_SAPI !== 'cli') {
    // Garde de sécurité : ce script écrit en base, il n'est jamais exposé via HTTP.
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Visiteur.php';

$db = Database::getConnection();
$admin = new Admin();
$visiteur = new Visiteur();
$ok = true;
$suffix = bin2hex(random_bytes(5));
$visitorEmail = "visitor.admin.$suffix@oxam-labo.fr";
$adminEmail = "admin.test.$suffix@oxam-labo.fr";

// Helper d'affichage : imprime [OK]/[FAIL] devant chaque vérification et
// mémorise l'échec global pour le code de retour du script.
function check($label, $condition) {
    global $ok;
    echo ($condition ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$condition) {
        $ok = false;
    }
}

// --- Parcours « visiteur » : création du compte de test puis activation ---
// Un compte créé doit être actif par défaut ; l'admin doit pouvoir le
// désactiver (isAccountActive passe à faux → déconnexion forcée côté web),
// le réactiver et réinitialiser son mot de passe.
$region = $db->query("SELECT reg_code FROM Region ORDER BY reg_code LIMIT 1")->fetchColumn();
check('Région disponible pour le compte de test', (bool) $region);
check('Création du compte visiteur de test', $visiteur->createVisiteur(
    'Visiteur', 'TestAdmin', $visitorEmail, '0600000000', 'Adresse test', $region, 'oldpassword1'
));
$visitor = $visiteur->findByMail($visitorEmail);
check('Visiteur actif par défaut', ($visitor['statut_compte'] ?? '') === 'actif');
check('Désactivation du visiteur', $admin->setAccountStatus('visiteur', (int) $visitor['id_vis'], null, 'inactif') === 'updated');
check('Visiteur inactif bloqué', !$admin->isAccountActive('visiteur', (int) $visitor['id_vis']));
check('Réactivation du visiteur', $admin->setAccountStatus('visiteur', (int) $visitor['id_vis'], null, 'actif') === 'updated');
check('Réinitialisation du mot de passe visiteur', $admin->resetPassword('visiteur', (int) $visitor['id_vis'], null, 'newpassword1'));
$visitor = $visiteur->findByMail($visitorEmail);
check('Nouveau mot de passe visiteur vérifié', $visiteur->verifyPassword($visitor['mot_de_passe_vis'] ?? '', 'newpassword1'));

// --- Protection des suppressions : « has_history » vs « deleted » ---
// On rattache le visiteur à une activité complémentaire (tables AC + Organiser) :
// deleteAccount doit refuser (retour 'has_history') tant que le compte a un
// historique, puis accepter ('deleted') une fois les liens retirés.
$insertActivity = $db->prepare("INSERT INTO AC (theme, lieu, date_ac) VALUES ('TestAdmin', 'Cholet', CURDATE())");
$insertActivity->execute();
$activityId = (int) $db->lastInsertId();
$organiser = $db->prepare("INSERT INTO Organiser (id_vis, id_ac) VALUES (:id_vis, :id_ac)");
$organiser->execute(['id_vis' => $visitor['id_vis'], 'id_ac' => $activityId]);
check('Suppression bloquée pour un visiteur lié à une activité', $admin->deleteAccount('visiteur', (int) $visitor['id_vis']) === 'has_history');
check('Le visiteur lié à une activité est conservé', (bool) $visiteur->findByMail($visitorEmail));
$db->prepare("DELETE FROM Organiser WHERE id_vis = :id_vis AND id_ac = :id_ac")
    ->execute(['id_vis' => $visitor['id_vis'], 'id_ac' => $activityId]);
$db->prepare("DELETE FROM AC WHERE id_ac = :id_ac")->execute(['id_ac' => $activityId]);
check('Suppression du visiteur sans historique', $admin->deleteAccount('visiteur', (int) $visitor['id_vis']) === 'deleted');

$adminCount = (int) $db->query("SELECT COUNT(*) FROM Administrateur WHERE statut_compte = 'actif'")->fetchColumn();
if ($adminCount === 1) {
    $lastAdminId = (int) $db->query("SELECT id_admin FROM Administrateur WHERE statut_compte = 'actif' LIMIT 1")->fetchColumn();
    check('Le dernier administrateur actif ne peut pas être désactivé',
        $admin->setAccountStatus('admin', $lastAdminId, null, 'inactif') === 'last_admin');
}

$insertAdmin = $db->prepare(
    "INSERT INTO Administrateur (mail_admin, mot_de_passe_admin) VALUES (:email, :password)"
);
$insertAdmin->execute(['email' => $adminEmail, 'password' => password_hash('oldpassword1', PASSWORD_DEFAULT)]);
$adminAccount = $admin->findByMail($adminEmail);
$updatedAdminEmail = "admin.updated.$suffix@oxam-labo.fr";
check('Modification de l’adresse administrateur', $admin->updateAdminEmail((int) $adminAccount['id_admin'], $updatedAdminEmail));
$adminEmail = $updatedAdminEmail;
check('Désactivation de l’administrateur', $admin->setAccountStatus('admin', (int) $adminAccount['id_admin'], null, 'inactif') === 'updated');
check('Administrateur inactif bloqué', !$admin->isAccountActive('admin', (int) $adminAccount['id_admin']));
check('Réactivation de l’administrateur', $admin->setAccountStatus('admin', (int) $adminAccount['id_admin'], null, 'actif') === 'updated');
check('Réinitialisation du mot de passe administrateur', $admin->resetPassword('admin', (int) $adminAccount['id_admin'], null, 'newpassword1'));
$adminAccount = $admin->findByMail($adminEmail);
check('Nouveau mot de passe administrateur vérifié', password_verify('newpassword1', $adminAccount['mot_de_passe_admin'] ?? ''));
check('Suppression de l’administrateur de test', $admin->deleteAccount('admin', (int) $adminAccount['id_admin']) === 'deleted');

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);
