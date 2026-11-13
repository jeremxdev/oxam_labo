<?php
// Tests HTTP du parcours praticien (session, gardes de role, PDF)
$base = 'http://localhost/oxam_labo/index.php';
$ok = true;

function check($label, $cond) {
    global $ok;
    echo ($cond ? "[OK]   " : "[FAIL] ") . $label . PHP_EOL;
    if (!$cond) $ok = false;
}

function request($url, $post = null, $cookie = null) {
    $ctx = ['http' => [
        'method' => $post === null ? 'GET' : 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" . ($cookie ? "Cookie: $cookie\r\n" : ''),
        'content' => $post === null ? '' : http_build_query($post),
        'follow_location' => 0,
        'ignore_errors' => true,
        'timeout' => 10,
    ]];
    $body = @file_get_contents($url, false, stream_context_create($ctx));
    $headers = $http_response_header ?? [];
    $status = 0;
    $location = null;
    $setCookie = null;
    foreach ($headers as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $status = (int) $m[1];
        if (stripos($h, 'Location:') === 0) $location = trim(substr($h, 9));
        if (stripos($h, 'Set-Cookie:') === 0) $setCookie = explode(';', substr($h, 11))[0];
    }
    return ['status' => $status, 'location' => $location, 'cookie' => $setCookie, 'body' => $body ?? ''];
}

// 0. Creation autonome d'un compte de test (nettoye en fin de script)
$emailTest = 'praticien.test@oxam-labo.fr';
// Creation via le formulaire (meme chemin que l'utilisateur)
$mk = request("$base?action=practicien_register", [
    'nom_par' => 'PratTest', 'prenom_par' => 'Essai', 'mail_par' => $emailTest,
    'password' => 'motdepasse123', 'password_confirm' => 'motdepasse123',
    'id_spe' => '2', 'nouvelle_specialite' => '', 'rue_par' => '1 Rue Test',
    'codePostal' => '49000', 'ville_par' => 'Angers',
]);
check('Creation du compte de test', str_contains($mk['body'], 'a été créé'));

// 1. Connexion praticien -> redirection espace
$login = request("$base?action=login", [
    'email' => $emailTest,
    'password' => 'motdepasse123',
]);
check('Connexion praticien -> 302', $login['status'] === 302);
check('Redirection vers action=practicien', ($login['location'] ?? '') === 'index.php?action=practicien');
$session = $login['cookie'];

// 2. Espace praticien
$espace = request("$base?action=practicien", null, $session);
check('Espace praticien accessible (200)', $espace['status'] === 200);
check('Espace: titre affiche', str_contains($espace['body'], 'Espace Praticien'));
check('Espace: profil affiche', str_contains($espace['body'], 'PratTest'));
check('Espace: aucun hash bcrypt', !str_contains($espace['body'], '$2y$'));
check('Espace: message liste vide', str_contains($espace['body'], 'Aucune visite enregistr'));
check('Espace: navigation praticien (pas de lien Visites visiteur)', !str_contains($espace['body'], 'Flotte Visiteurs'));

// 3. Praticien bloque sur les pages visiteur (doit rediriger vers l'espace)
foreach (['visites', 'visiteurs', 'ac', 'visite_create'] as $act) {
    $r = request("$base?action=$act", null, $session);
    check("Garde role: $act redirige vers l'espace praticien", ($r['location'] ?? '') === 'index.php?action=practicien');
}

// 4. Visiteur bloque sur l'espace praticien
$vLogin = request("$base?action=login", [
    'email' => 'imaquej@saintemarie-cholet.eu',
    'password' => 'oxam2025',
]);
check('Connexion visiteur -> 302 vers visites', $vLogin['status'] === 302 && ($vLogin['location'] ?? '') === 'index.php?action=visites');
$vSession = $vLogin['cookie'];
$vEspace = request("$base?action=practicien", null, $vSession);
check('Visiteur redirige hors espace praticien', ($vEspace['location'] ?? '') === 'index.php?action=visites');

// 5. Les pages visiteur restent accessibles au visiteur
$vVisites = request("$base?action=visites", null, $vSession);
check('Page visites du visiteur accessible', $vVisites['status'] === 200 && !isset($vVisites['location']));

// 6. PDF: acces au compte-rendu de la visite 1 (praticien Gasnier + visiteur proprietaire)
$praEspace = request("$base?action=practicien");
// Gasnier est le praticien de la visite 1 -> connexion Gasnier
$gLogin = request("$base?action=login", [
    'email' => 'hugo.gasnier@oxam-labo.fr',
    'password' => 'oxam2025',
]);
check('Connexion Gasnier -> espace praticien', $gLogin['status'] === 302 && ($gLogin['location'] ?? '') === 'index.php?action=practicien');
$gSession = $gLogin['cookie'];
$gEspace = request("$base?action=practicien", null, $gSession);
check('Espace Gasnier: affiche sa visite de demo', str_contains($gEspace['body'], 'Comptes Rendus') || str_contains($gEspace['body'], 'Visites réalisées'));
$gPdf = request("$base?action=visite_pdf&id=1", null, $gSession);
check('PDF accesible au praticien concerne', $gPdf['status'] === 200 && !isset($gPdf['location']));

// 7. PDF refuse a un autre praticien (PratTest n'est pas le praticien de la visite 1)
$tPdf = request("$base?action=visite_pdf&id=1", null, $session);
check('PDF refuse a un autre praticien', str_contains($tPdf['body'], 'Accès refusé'));

// 8. PDF refuse au visiteur non proprietaire de la visite (PratTest est un praticien, on teste avec le visiteur Imaque qui est proprietaire -> autorise)
$vPdf = request("$base?action=visite_pdf&id=1", null, $vSession);
check('PDF accesible au visiteur proprietaire', $vPdf['status'] === 200 && !str_contains($vPdf['body'], 'Accès refusé'));

// 9. Non connecte -> redirection connexion
$anon = request("$base?action=practicien");
check('Anonyme redirige vers connexion', ($anon['location'] ?? '') === 'index.php?action=login');

echo PHP_EOL . ($ok ? 'TOUS LES TESTS PASSENT' : 'DES TESTS ONT ECHOUE') . PHP_EOL;

// Nettoyage du compte de test via la base (les fichiers de test suppriment leurs lignes)
$dbTest = new PDO('mysql:host=localhost;dbname=oxam_labo;charset=utf8mb4', 'root', '');
$dbTest->exec("DELETE FROM Practicien WHERE mail_par = '$emailTest'");
exit($ok ? 0 : 1);
