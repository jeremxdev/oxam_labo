<?php
// Formulaire de modification d'une AC par l'administrateur.
// Reçoit $activite (ligne AC + ids des organisateurs), $form (valeurs affichées),
// $visiteurs et $error de AdminAcController::edit().
// Définit les paramètres du partial _form.php puis inclut le layout commun.
include __DIR__ . '/../../layout/header.php';
$title = "Modifier l'activité complémentaire";
$action = 'admin_ac_edit';
$buttonLabel = "Enregistrer les modifications";
$id = (int) $activite['id_ac'];
include __DIR__ . '/_form.php';
include __DIR__ . '/../../layout/footer.php';
