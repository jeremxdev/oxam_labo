-- Migration 006 : ajoute le statut d'acceptation des visites pour le praticien concerné.
-- Nouveau statut "en_attente" par défaut, modifiable depuis l'espace praticien.
USE oxam_labo;

ALTER TABLE Visite
    ADD COLUMN statut_vis ENUM('en_attente', 'acceptee', 'refusee')
    NOT NULL DEFAULT 'en_attente' AFTER bilan_vis;
