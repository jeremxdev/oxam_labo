<?php
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

$email = 'hugo.gasnier@oxam-labo.fr';

$stmt = $pdo->prepare("SELECT p.Id_pra, p.id_spe, p.nom_par, p.prenom_par, p.mail_par, p.mot_de_passe_par, s.nom_spe
                       FROM Practicien p LEFT JOIN Specialite s ON p.id_spe = s.id_spe
                       WHERE p.mail_par = ? LIMIT 1");
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row) {
    echo "ABSENT de la table Practicien\n";
    exit(0);
}

echo "PRESENT : {$row['prenom_par']} {$row['nom_par']} (id {$row['Id_pra']}, spécialité " . ($row['nom_spe'] ?? 'aucune') . ")\n";

$hash = (string) $row['mot_de_passe_par'];
if ($hash === '' || $hash === '0') {
    echo "Mot de passe : VIDE (connexion impossible)\n";
} elseif (password_verify('oxam2025', $hash)) {
    echo "Mot de passe : déjà oxam2025\n";
} else {
    echo "Mot de passe : différent de oxam2025 (longueur empreinte " . strlen($hash) . ")\n";
}

// Présence éventuelle en tant que visiteur (email unique sur toute l'application)
$stmt = $pdo->prepare("SELECT id_vis FROM Visiteur WHERE mail_vis = ? LIMIT 1");
$stmt->execute([$email]);
echo "En tant que visiteur : " . ($stmt->fetchColumn() !== false ? "OUI (conflit)" : "non") . "\n";
