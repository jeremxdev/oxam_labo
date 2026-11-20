-- Ajoute l'activation des comptes visiteurs, praticiens et administrateurs.
USE oxam_labo;

ALTER TABLE Visiteur
    ADD COLUMN statut_compte ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif'
    AFTER mot_de_passe_vis;

ALTER TABLE Practicien
    ADD COLUMN statut_compte ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif'
    AFTER statut_validation;

ALTER TABLE Administrateur
    ADD COLUMN statut_compte ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif'
    AFTER mot_de_passe_admin;
