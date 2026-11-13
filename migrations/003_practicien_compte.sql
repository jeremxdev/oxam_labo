USE oxam_labo;

-- Compte de connexion du praticien : email (identifiant) et mot de passe haché
ALTER TABLE Practicien
    ADD COLUMN mail_par VARCHAR(50) NULL AFTER prenom_par,
    ADD COLUMN mot_de_passe_par VARCHAR(255) NULL AFTER mail_par;

-- Email provisoire pour les praticiens déjà présents (à corriger si besoin)
UPDATE Practicien
SET mail_par = CONCAT(LOWER(REPLACE(CONCAT(prenom_par, '.', nom_par), ' ', '')), '@oxam-labo.fr')
WHERE mail_par IS NULL;

-- Mot de passe provisoire "oxam2025" pour les praticiens déjà présents (à changer après connexion)
UPDATE Practicien
SET mot_de_passe_par = '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW'
WHERE mot_de_passe_par IS NULL;
