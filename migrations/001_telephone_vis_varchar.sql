-- Adapte le téléphone du visiteur à un champ texte pour conserver les indicatifs et zéros initiaux.
USE oxam_labo;

ALTER TABLE Visiteur
MODIFY telephone_vis VARCHAR(20);
