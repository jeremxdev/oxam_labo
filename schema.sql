-- Schéma initial de la base oxam_labo : tables métier, relations et données de démonstration.
-- Base de données Merise exacte (oxam_labo)
CREATE DATABASE IF NOT EXISTS oxam_labo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oxam_labo;

CREATE TABLE Visiteur(
   id_vis INT AUTO_INCREMENT,
   telephone_vis VARCHAR(20),
   nom_vis VARCHAR(25) ,
   prenom_vis VARCHAR(20) ,
   mail_vis VARCHAR(50) ,
   mot_de_passe_vis VARCHAR(255) NOT NULL DEFAULT '' ,
   adresse_vis VARCHAR(100) ,
   PRIMARY KEY(id_vis)
) ENGINE=InnoDB;

CREATE TABLE AC(
   id_ac INT AUTO_INCREMENT,
   theme VARCHAR(20) ,
   lieu VARCHAR(50) ,
   date_ac DATE,
   PRIMARY KEY(id_ac)
) ENGINE=InnoDB;

CREATE TABLE Medicament(
   id_med INT AUTO_INCREMENT,
   nomCommercial_med VARCHAR(50) ,
   PRIMARY KEY(id_med)
) ENGINE=InnoDB;

CREATE TABLE Specialite(
   id_spe INT AUTO_INCREMENT,
   nom_spe VARCHAR(50) ,
   PRIMARY KEY(id_spe)
) ENGINE=InnoDB;

CREATE TABLE Region(
   reg_code VARCHAR(50) ,
   PRIMARY KEY(reg_code)
) ENGINE=InnoDB;

CREATE TABLE Practicien(
   id_spe INT,
   Id_pra INT AUTO_INCREMENT,
   note_par VARCHAR(50),
   nom_par VARCHAR(50),
   prenom_par VARCHAR(50),
   mail_par VARCHAR(50) ,
   mot_de_passe_par VARCHAR(255) ,
   rue_par VARCHAR(50),
   codePostal INT,
   ville_par VARCHAR(50),
   longitude_par VARCHAR(50),
   latitude VARCHAR(50),
   PRIMARY KEY(id_spe, Id_pra),
   KEY (Id_pra),
   FOREIGN KEY(id_spe) REFERENCES Specialite(id_spe)
) ENGINE=InnoDB;

CREATE TABLE Visite(
   id_vis INT AUTO_INCREMENT,
   date_vis DATE,
   motif_vis VARCHAR(15) ,
   bilan_vis VARCHAR(200) ,
   id_spe INT NOT NULL,
   Id_pra INT NOT NULL,
   id_vis_1 INT NOT NULL,
   PRIMARY KEY(id_vis),
   FOREIGN KEY(id_spe, Id_pra) REFERENCES Practicien(id_spe, Id_pra),
   FOREIGN KEY(id_vis_1) REFERENCES Visiteur(id_vis)
) ENGINE=InnoDB;

CREATE TABLE Presenter(
   id_vis INT,
   id_med INT,
   PRIMARY KEY(id_vis, id_med),
   FOREIGN KEY(id_vis) REFERENCES Visite(id_vis),
   FOREIGN KEY(id_med) REFERENCES Medicament(id_med)
) ENGINE=InnoDB;

CREATE TABLE Offrir(
   id_vis INT,
   id_med INT,
   qteEchantillon_med VARCHAR(50) ,
   PRIMARY KEY(id_vis, id_med),
   FOREIGN KEY(id_vis) REFERENCES Visite(id_vis),
   FOREIGN KEY(id_med) REFERENCES Medicament(id_med)
) ENGINE=InnoDB;

CREATE TABLE Organiser(
   id_vis INT,
   id_ac INT,
   PRIMARY KEY(id_vis, id_ac),
   FOREIGN KEY(id_vis) REFERENCES Visiteur(id_vis),
   FOREIGN KEY(id_ac) REFERENCES AC(id_ac)
) ENGINE=InnoDB;

CREATE TABLE participe(
   id_ac INT,
   id_spe INT,
   Id_pra INT,
   PRIMARY KEY(id_ac, id_spe, Id_pra),
   FOREIGN KEY(id_ac) REFERENCES AC(id_ac),
   FOREIGN KEY(id_spe, Id_pra) REFERENCES Practicien(id_spe, Id_pra)
) ENGINE=InnoDB;

CREATE TABLE affecter(
   id_spe INT,
   Id_pra INT,
   reg_code VARCHAR(50) ,
   dateArrivee DATE,
   PRIMARY KEY(id_spe, Id_pra, reg_code, dateArrivee),
   FOREIGN KEY(id_spe, Id_pra) REFERENCES Practicien(id_spe, Id_pra),
   FOREIGN KEY(reg_code) REFERENCES Region(reg_code)
) ENGINE=InnoDB;

CREATE TABLE Travailler(
   id_vis INT,
   reg_code VARCHAR(50) ,
   dateaffect DATE,
   PRIMARY KEY(id_vis, reg_code, dateaffect),
   FOREIGN KEY(id_vis) REFERENCES Visiteur(id_vis),
   FOREIGN KEY(reg_code) REFERENCES Region(reg_code)
) ENGINE=InnoDB;

CREATE TABLE portefeuille(
   id_vis INT,
   id_spe INT,
   Id_pra INT,
   PRIMARY KEY(id_vis, id_spe, Id_pra),
   FOREIGN KEY(id_vis) REFERENCES Visiteur(id_vis),
   FOREIGN KEY(id_spe, Id_pra) REFERENCES Practicien(id_spe, Id_pra)
) ENGINE=InnoDB;

-- Données initiales
INSERT INTO Region (reg_code) VALUES ('OUEST'), ('IDF-CENTRE'), ('SUD');

-- Mot de passe de démonstration des comptes ci-dessous : oxam2025 (haché en bcrypt)
INSERT INTO Visiteur (telephone_vis, nom_vis, prenom_vis, mail_vis, mot_de_passe_vis, adresse_vis) VALUES
(612345678, 'Dupont', 'Jean', 'jean.dupont@oxam-labo.fr', '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW', '12 Rue des Fleurs, Cholet'),
(698765432, 'Martin', 'Sophie', 'sophie.martin@oxam-labo.fr', '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW', '45 Avenue de la République, Paris');

INSERT INTO Travailler (id_vis, reg_code, dateaffect) VALUES
(1, 'OUEST', '2025-01-01'),
(2, 'IDF-CENTRE', '2025-01-01');

INSERT INTO Specialite (nom_spe) VALUES
('Cardiologie'),
('Médecine Générale'),
('Dermatologie'),
('Endocrinologie'),
('Gastro-entérologie'),
('Gynécologie'),
('Neurologie'),
('Oncologie'),
('Ophtalmologie'),
('Pédiatrie'),
('Pneumologie'),
('Rhumatologie');

-- Email et mot de passe de démonstration des praticiens ci-dessous : oxam2025 (haché en bcrypt)
INSERT INTO Practicien (id_spe, note_par, nom_par, prenom_par, mail_par, mot_de_passe_par, rue_par, codePostal, ville_par, longitude_par, latitude) VALUES
(1, 'Très réceptif', 'Bernard', 'Luc', 'luc.bernard@oxam-labo.fr', '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW', '15 Rue de la Paix', 49300, 'Cholet', '-0.88', '47.06'),
(2, 'À relancer', 'Moreau', 'Claire', 'claire.moreau@oxam-labo.fr', '$2y$12$CX/lQSqAG0fRghkt7Jzd7./HKdJynBW1k5zTi6kth9qm6W/.S/jgW', '8 Place du Ralliement', 49000, 'Angers', '-0.55', '47.47');

INSERT INTO portefeuille (id_vis, id_spe, Id_pra) VALUES
(1, 1, 1),
(1, 2, 2);

INSERT INTO Medicament (nomCommercial_med) VALUES
('Oxam-Cardio 50mg'),
('Bio-Relax 100mg');
