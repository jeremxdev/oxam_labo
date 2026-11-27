<?php
// Formulaire de création d'une AC par l'administrateur.
// Reçoit $form (valeurs saisies), $visiteurs et $error de AdminAcController::create().
// Définit les paramètres du partial _form.php puis inclut le layout commun.
include __DIR__ . '/../../layout/header.php';
$title = "Nouvelle activité complémentaire";
$action = 'admin_ac_create';
$buttonLabel = "Créer l'AC";
$id = null;
include __DIR__ . '/_form.php';
include __DIR__ . '/../../layout/footer.php';
