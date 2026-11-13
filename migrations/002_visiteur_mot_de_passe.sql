-- Ajoute un champ de mot de passe haché aux visiteurs et initialise les comptes déjà existants.
USE oxam_labo;

-- Ajout du champ de connexion (mot de passe haché avec password_hash / bcrypt)
ALTER TABLE Visiteur
    ADD COLUMN mot_de_passe_vis VARCHAR(255) NOT NULL DEFAULT '' AFTER mail_vis;

-- Mot de passe provisoire "oxam2025" pour les comptes existants (à changer après connexion)
UPDATE Visiteur
SET mot_de_passe_vis = '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW'
WHERE mot_de_passe_vis = '';
