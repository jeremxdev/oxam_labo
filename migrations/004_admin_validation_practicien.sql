-- Migration 004 : ajoute l'approbation des comptes praticiens et crée la table des administrateurs.
-- Dépend de la migration 003 (colonne mot_de_passe_par). Les praticiens déjà présents restent approuvés.
USE oxam_labo;

-- Les praticiens déjà enregistrés restent actifs ; les nouvelles inscriptions
-- sont explicitement créées avec le statut "en_attente" par l'application.
ALTER TABLE Practicien
    ADD COLUMN statut_validation ENUM('en_attente', 'approuve', 'refuse')
    NOT NULL DEFAULT 'approuve' AFTER mot_de_passe_par;

CREATE TABLE Administrateur(
   id_admin INT AUTO_INCREMENT,
   mail_admin VARCHAR(254) NOT NULL,
   mot_de_passe_admin VARCHAR(255) NOT NULL,
   PRIMARY KEY(id_admin),
   UNIQUE KEY uq_administrateur_mail (mail_admin)
) ENGINE=InnoDB;
