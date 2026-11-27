<?php
/**
 * Script temporaire de réparation de données : ajoute le praticien de démonstration
 * hugo.gasnier@oxam-labo.fr (mot de passe oxam2025) dans la base réelle.
 *
 * Étapes : refus si l'email appartient déjà à un visiteur, recherche/création de
 * la spécialité Cardiologie, création ou mise à jour du mot de passe du praticien,
 * puis ajout au portefeuille de Jean Dupont.
 * Idempotent : relançable sans créer de doublon.
 *
 * À n'exécuter qu'en local : php tmp_add_gasnier.php (modifie la base oxam_labo).
 */
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();
$email = 'hugo.gasnier@oxam-labo.fr';
$password = 'oxam2025';

// Empreinte de démonstration utilisée par schema.sql (vérifiée ci-dessous).
$schemaHash = '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW';
$hash = password_verify($password, $schemaHash) ? $schemaHash : password_hash($password, PASSWORD_DEFAULT);

// Email unique sur toute l'application
$stmt = $pdo->prepare("SELECT 'Visiteur' FROM Visiteur WHERE mail_vis = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetchColumn() !== false) {
    echo "ERREUR : cet email est déjà utilisé par un visiteur.\n";
    exit(1);
}

// Spécialité recherchée par nom (les id_spe varient selon les bases)
$stmt = $pdo->prepare("SELECT id_spe FROM Specialite WHERE nom_spe = 'Cardiologie' LIMIT 1");
$stmt->execute();
$idSpe = $stmt->fetchColumn();
if ($idSpe === false) {
    $pdo->exec("INSERT INTO Specialite (nom_spe) VALUES ('Cardiologie')");
    $idSpe = $pdo->lastInsertId();
    echo "Spécialité Cardiologie créée (id $idSpe)\n";
}

$stmt = $pdo->prepare("SELECT Id_pra, id_spe, mot_de_passe_par FROM Practicien WHERE mail_par = ? LIMIT 1");
$stmt->execute([$email]);
$existant = $stmt->fetch();

if ($existant) {
    echo "Compte déjà présent (id {$existant['Id_pra']}, spécialité {$existant['id_spe']})\n";
    if (!password_verify($password, (string) $existant['mot_de_passe_par'])) {
        $pdo->prepare("UPDATE Practicien SET mot_de_passe_par = ? WHERE mail_par = ?")->execute([$hash, $email]);
        echo "Mot de passe réinitialisé à oxam2025\n";
    }
} else {
    $pdo->prepare(
        "INSERT INTO Practicien (id_spe, nom_par, prenom_par, mail_par, mot_de_passe_par, note_par, rue_par, codePostal, ville_par, longitude_par, latitude)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([$idSpe, 'Gasnier', 'Hugo', $email, $hash, 'À suivre', '22 Avenue de la Gare', 49300, 'Cholet', '-0.88', '47.06']);
    $idPra = $pdo->lastInsertId();
    echo "Praticien créé : Hugo Gasnier <$email> (Cardiologie, id $idPra)\n";

    // Portefeuille de Jean Dupont, comme les autres comptes de démonstration
    $stmt = $pdo->prepare("SELECT id_vis FROM Visiteur WHERE mail_vis = 'jean.dupont@oxam-labo.fr' LIMIT 1");
    $stmt->execute();
    $idJean = $stmt->fetchColumn();
    if ($idJean !== false) {
        $pdo->prepare("INSERT INTO portefeuille (id_vis, id_spe, Id_pra) VALUES (?, ?, ?)")
            ->execute([$idJean, $idSpe, $idPra]);
        echo "Ajouté au portefeuille de Jean Dupont\n";
    }
}
