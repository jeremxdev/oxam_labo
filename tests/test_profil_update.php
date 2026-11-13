<?php
// Script de test : modification des profils visiteur et praticien (couche modèle)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';

$vis = new Visiteur();
$pra = new Practicien();
$db = Database::getConnection();
$ok = true;

function check($label, $cond) {
    global $ok;
    echo ($cond ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$cond) $ok = false;
}

// Comptes de test dedies (crees puis supprimes en fin de script)
$emailV = 'test.update.vis@oxam-labo.fr';
$emailV2 = 'test.update.vis2@oxam-labo.fr';
$emailP = 'test.update.pra@oxam-labo.fr';
$emailP2 = 'test.update.pra2@oxam-labo.fr';
$specialite = (int) $db->query("SELECT id_spe FROM Specialite ORDER BY id_spe LIMIT 1")->fetchColumn();

// Regions reelles de la base (le contenu varie selon les installations)
$regions = array_column($db->query("SELECT reg_code FROM Region ORDER BY reg_code")->fetchAll(), 'reg_code');
$regionA = $regions[0] ?? null;
$regionB = $regions[1] ?? $regionA;
check('Au moins deux regions disponibles', count($regions) >= 2);

// Nettoyage prealable
foreach ([$emailV, $emailV2, $emailP, $emailP2] as $e) {
    $db->exec("DELETE FROM Visiteur WHERE mail_vis = '$e'");
    $db->exec("DELETE FROM Practicien WHERE mail_par = '$e'");
}

// Creation des comptes de test
check('Creation visiteur de test', $vis->createVisiteur('Avant', 'Visiteur', $emailV, '0600000010', '1 Rue Avant, Cholet', $regionA, 'anciennepass1'));
check('Creation visiteur 2 (pour test email)', $vis->createVisiteur('Deuxieme', 'Visiteur', $emailV2, '0600000011', '2 Rue, Cholet', $regionA, 'anciennepass1'));
check('Creation praticien de test', $pra->createPracticien($specialite, null, 'Avant', 'Praticien', '3 Rue Avant', '49000', 'Cholet', $emailP, 'anciennepass1'));
check('Creation praticien 2 (pour test email)', $pra->createPracticien($specialite, null, 'Deuxieme', 'Praticien', '4 Rue', '49000', 'Cholet', $emailP2, 'anciennepass1'));

$v = $vis->findByMail($emailV);
$p = $pra->findByMail($emailP);
check('Comptes de test trouves', (bool) $v && (bool) $p);
$idV = (int) $v['id_vis'];
$idP = (int) $p['Id_pra'];

// ===== VISITEUR =====

// 1. Modification sans mot de passe -> conserve l'ancien
check('Update visiteur sans mdp', $vis->updateVisiteur($idV, 'Apres', 'Visiteur', $emailV, '0611111111', '2 Rue Apres, Angers', $regionA, null));
$v2 = $vis->getVisiteurById($idV);
check('Noms mis a jour', $v2['nom_vis'] === 'Apres' && $v2['prenom_vis'] === 'Visiteur');
check('Adresse et tel mis a jour', $v2['adresse_vis'] === '2 Rue Apres, Angers' && $v2['telephone_vis'] === '0611111111');
check('Ancien mot de passe conserve', $vis->verifyPassword(
    $db->query("SELECT mot_de_passe_vis FROM Visiteur WHERE id_vis = $idV")->fetchColumn(),
    'anciennepass1'
));

// 2. Changement de region -> nouvelle affectation Travailler
$nbAffect = (int) $db->query("SELECT COUNT(*) FROM Travailler WHERE id_vis = $idV")->fetchColumn();
check('Update avec changement de region', $vis->updateVisiteur($idV, 'Apres', 'Visiteur', $emailV, '0611111111', '2 Rue Apres, Angers', $regionB, null));
$v3 = $vis->getVisiteurById($idV);
check('Region courante = ' . $regionB, $v3['reg_code'] === $regionB);
$nbAffect2 = (int) $db->query("SELECT COUNT(*) FROM Travailler WHERE id_vis = $idV AND reg_code = " . $db->quote($regionB))->fetchColumn();
check('Nouvelle affectation enregistree', $nbAffect2 >= 1);

// 3. Meme region enregistree deux fois le meme jour -> pas de doublon
$avantDoublon = (int) $db->query("SELECT COUNT(*) FROM Travailler WHERE id_vis = $idV AND reg_code = " . $db->quote($regionB) . " AND dateaffect = CURDATE()")->fetchColumn();
$vis->updateVisiteur($idV, 'Apres', 'Visiteur', $emailV, '0611111111', '2 Rue Apres, Angers', $regionB, null);
$apresDoublon = (int) $db->query("SELECT COUNT(*) FROM Travailler WHERE id_vis = $idV AND reg_code = " . $db->quote($regionB) . " AND dateaffect = CURDATE()")->fetchColumn();
check('Aucun doublon damemoire le meme jour', $avantDoublon === $apresDoublon);

// 4. Changement de mot de passe
check('Update avec nouveau mdp', $vis->updateVisiteur($idV, 'Apres', 'Visiteur', $emailV, '0611111111', '2 Rue Apres, Angers', $regionB, 'nouveaupass1'));
$hash = $db->query("SELECT mot_de_passe_vis FROM Visiteur WHERE id_vis = $idV")->fetchColumn();
check('Nouveau mdp accepte', $vis->verifyPassword($hash, 'nouveaupass1'));
check('Ancien mdp rejete', !$vis->verifyPassword($hash, 'anciennepass1'));
check('Hash en bcrypt', str_starts_with($hash, '$2y$'));

// 5. Email deja pris par un AUTRE visiteur (le deuxieme) -> le modele n'empeche pas la contrainte,
//    c'est au controleur de verifier ; on verifie ici que findByMail le detecte
check('findByMail detecte le compte deja existant', (bool) $vis->findByMail($emailV2));
check('findByMail sur email libre renvoie faux', !$vis->findByMail('libre@oxam-labo.fr'));

// ===== PRACTICIEN =====

// 6. Modification sans mot de passe
check('Update praticien sans mdp', $pra->updatePracticien($idP, 'Apres', 'Praticien', $emailP, '5 Rue Apres', '49100', 'Angers', null));
$p2 = $pra->getPracticienById($specialite, $idP);
check('Noms et adresse mis a jour', $p2['nom_par'] === 'Apres' && $p2['rue_par'] === '5 Rue Apres' && $p2['ville_par'] === 'Angers');
check('Specialite inchangee', (int) $p2['id_spe'] === $specialite);
check('Ancien mdp praticien conserve', $pra->verifyPassword(
    $db->query("SELECT mot_de_passe_par FROM Practicien WHERE Id_pra = $idP")->fetchColumn(),
    'anciennepass1'
));

// 7. Changement de mot de passe praticien
check('Update avec nouveau mdp praticien', $pra->updatePracticien($idP, 'Apres', 'Praticien', $emailP, '5 Rue Apres', '49100', 'Angers', 'nouveaupass1'));
$hashP = $db->query("SELECT mot_de_passe_par FROM Practicien WHERE Id_pra = $idP")->fetchColumn();
check('Nouveau mdp praticien accepte', $pra->verifyPassword($hashP, 'nouveaupass1'));
check('Ancien mdp praticien rejete', !$pra->verifyPassword($hashP, 'anciennepass1'));

// 8. Email du praticien 2 bien detecte pour interdiction de doublon
check('findByMail praticien detecte le deuxieme compte', (bool) $pra->findByMail($emailP2));

// 9. Profil n expose jamais le hash
$profil = $pra->getPracticienById($specialite, $idP);
check('Profil praticien sans hash', !array_key_exists('mot_de_passe_par', $profil));
$profilV = $vis->getVisiteurById($idV);
check('Profil visiteur sans hash', !array_key_exists('mot_de_passe_vis', $profilV));

// Nettoyage
foreach ([$emailV, $emailV2] as $e) {
    $row = $db->query("SELECT id_vis FROM Visiteur WHERE mail_vis = '$e'")->fetch();
    if ($row) {
        $db->exec("DELETE FROM Travailler WHERE id_vis = " . (int) $row['id_vis']);
        $db->exec("DELETE FROM Visiteur WHERE id_vis = " . (int) $row['id_vis']);
    }
}
foreach ([$emailP, $emailP2] as $e) {
    $row = $db->query("SELECT Id_pra FROM Practicien WHERE mail_par = '$e'")->fetch();
    if ($row) {
        $db->exec("DELETE FROM Practicien WHERE Id_pra = " . (int) $row['Id_pra']);
    }
}
check('Comptes de test nettoyes', !$vis->findByMail($emailV) && !$pra->findByMail($emailP));

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;
exit($ok ? 0 : 1);
