-- Ajoute le statut d'acceptation des visites pour le praticien concerné.
USE oxam_labo;

ALTER TABLE Visite
    ADD COLUMN statut_vis ENUM('en_attente', 'acceptee', 'refusee')
    NOT NULL DEFAULT 'en_attente' AFTER bilan_vis;
