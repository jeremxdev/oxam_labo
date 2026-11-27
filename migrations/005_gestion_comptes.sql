-- Migration 005 : ajoute l'activation des comptes visiteurs, praticiens et administrateurs (statut_compte).
-- Dépend des migrations 002, 003 et 004. Le statut par défaut est "actif".
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
