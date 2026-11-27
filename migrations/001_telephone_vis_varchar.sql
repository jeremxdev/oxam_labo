-- Migration 001 : adapte le téléphone du visiteur à un champ texte pour conserver les indicatifs et zéros initiaux.
-- À appliquer dans l'ordre sur une base issue de l'ancien schéma (le schema.sql actuel inclut déjà ces changements).
USE oxam_labo;

ALTER TABLE Visiteur
MODIFY telephone_vis VARCHAR(20);
