<?php
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Visite.php';

/**
 * Contrôleur de l'espace praticien : inscription, tableau de bord et profil.
 *
 * Actions gérées : register() (demande de compte publique), index() (espace
 * praticien avec ses visites) et profil() (modification des informations).
 * S'appuie sur Practicien, Visiteur, Visite et Admin (validation par l'administrateur).
 * Rend views/auth/register_practicien.php, views/practicien/index.php et
 * views/practicien/profil.php.
 * Contrôle d'accès : register() est public ; index() et profil() exigent le rôle
 * "practicien" et un profil toujours présent en base.
 */
class PracticienController {
    /**
     * Affiche le formulaire d'inscription praticien et enregistre la demande.
     *
     * Sans paramètre : lit $_POST en méthode POST. Contrôle la présence des champs,
     * le code postal, le choix exact d'une spécialité (existante ou nouvelle),
     * l'email, le mot de passe (8 caractères minimum et confirmation), l'unicité
     * de l'email sur toute l'application et la taille maximale des champs.
     * Crée ensuite le compte via Practicien::createPracticien() avec le statut
     * "en_attente" ; la connexion ne sera possible qu'après validation admin.
     * Rend views/auth/register_practicien.php avec $error, $success, $specialites, $regions.
     */
    public function register() {
        $error = null;
        $success = null;
        $practicienModel = new Practicien();
        $visiteurModel = new Visiteur();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom_par'] ?? '');
            $prenom = trim($_POST['prenom_par'] ?? '');
            $email = trim($_POST['mail_par'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $rue = trim($_POST['rue_par'] ?? '');
            $codePostal = trim($_POST['codePostal'] ?? '');
            $ville = trim($_POST['ville_par'] ?? '');
            $regCode = trim($_POST['reg_code'] ?? '');
            $idSpe = ($_POST['id_spe'] ?? '') === '' ? null : filter_var($_POST['id_spe'], FILTER_VALIDATE_INT);
            $nouvelleSpecialite = trim($_POST['nouvelle_specialite'] ?? '');
            // Une seule des deux options est autorisée : spécialité existante OU nouvelle spécialité.
            $aChoisiSpecialite = $idSpe !== null && $idSpe !== false && $idSpe > 0;
            $specialiteValide = ($aChoisiSpecialite && $nouvelleSpecialite === '') ||
                ($idSpe === null && $nouvelleSpecialite !== '');

            // Première passe : champs obligatoires, code postal, région et spécialité.
            if ($nom === '' || $prenom === '' || $rue === '' || $ville === '' || $regCode === '' ||
                !preg_match('/^\d{1,5}$/', $codePostal) || !$specialiteValide) {
                $error = "Veuillez vérifier les champs, le code postal, la région et le choix de spécialité.";
            }

            if ($error === null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez saisir une adresse email valide.";
            }

            if ($error === null && strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères.";
            }

            if ($error === null && $password !== $passwordConfirm) {
                $error = "Les deux mots de passe ne correspondent pas.";
            }

            // Email unique sur toute l'application (visiteurs ET praticiens ET admins).
            if ($error === null && ($practicienModel->findByMail($email) || $visiteurModel->findByMail($email) ||
                (new Admin())->isEmailReserved($email))) {
                $error = "Cette adresse email est déjà utilisée.";
            }

            if ($error === null && (
                mb_strlen($nom) > 50 || mb_strlen($prenom) > 50 ||
                mb_strlen($rue) > 50 || mb_strlen($ville) > 50 ||
                mb_strlen($nouvelleSpecialite) > 50 || mb_strlen($email) > 50 ||
                mb_strlen($regCode) > 50)) {
                $error = "Les champs texte ne doivent pas dépasser 50 caractères.";
            }

            // Toutes les validations sont passées : création de la demande de compte.
            if ($error === null && $practicienModel->createPracticien($aChoisiSpecialite ? $idSpe : null, $nouvelleSpecialite, $nom, $prenom, $rue, $codePostal, $ville, $email, $password, $regCode)) {
                $success = "Votre demande de compte praticien a été enregistrée. Vous pourrez vous connecter après validation par un administrateur.";
            } elseif ($error === null) {
                $error = "Une erreur est survenue lors de la création du compte praticien.";
            }
        }

        // Données de référence pour les listes déroulantes du formulaire.
        $specialites = $practicienModel->getAllSpecialites();
        $regions = $practicienModel->getAllRegions();
        require __DIR__ . '/../views/auth/register_practicien.php';
    }

    /**
     * Affiche l'espace du praticien connecté avec la liste de ses visites.
     *
     * Vérifie la session et le rôle "practicien" (redirection sinon), recharge
     * le profil depuis la base pour garantir qu'il existe encore et récupère
     * les comptes rendus via Visite::getVisitesByPracticien().
     * Rend views/practicien/index.php avec $profil et $visites.
     */
    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'practicien') {
            $destination = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin' : 'visites';
            header("Location: index.php?action=$destination");
            exit();
        }

        $practicienModel = new Practicien();
        $visiteModel = new Visite();

        // Un profil supprimé entre-temps invalide la session en cours.
        $profil = $practicienModel->getPracticienById($_SESSION['user']['id_spe'], $_SESSION['user']['id_pra']);
        if (!$profil) {
            session_destroy();
            header("Location: index.php?action=login");
            exit();
        }

        $visites = $visiteModel->getVisitesByPracticien($_SESSION['user']['id_spe'], $_SESSION['user']['id_pra']);
        require __DIR__ . '/../views/practicien/index.php';
    }

    /**
     * Affiche et met à jour le profil du praticien connecté.
     *
     * Contrôle d'accès : session et rôle "practicien" obligatoires.
     * En POST : valide les champs (obligatoires, longueurs, code postal, email),
     * le mot de passe facultatif (8 caractères minimum s'il est fourni),
     * l'unicité de l'email hors son propre compte, puis met à jour la base via
     * Practicien::updatePracticien(). La session est actualisée en cas de succès.
     * Rend views/practicien/profil.php avec $profil, $error, $success, $specialites.
     */
    public function profil() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'practicien') {
            $destination = ($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin' : 'visites';
            header("Location: index.php?action=$destination");
            exit();
        }

        $practicienModel = new Practicien();
        $visiteurModel = new Visiteur();
        $idSpe = (int) $_SESSION['user']['id_spe'];
        $idPra = (int) $_SESSION['user']['id_pra'];

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom_par'] ?? '');
            $prenom = trim($_POST['prenom_par'] ?? '');
            $email = trim($_POST['mail_par'] ?? '');
            $rue = trim($_POST['rue_par'] ?? '');
            $codePostal = trim($_POST['codePostal'] ?? '');
            $ville = trim($_POST['ville_par'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($nom === '' || $prenom === '' || $email === '' || $rue === '' || $codePostal === '' || $ville === '') {
                $error = "Tous les champs sont obligatoires.";
            } elseif (!preg_match('/^\d{1,5}$/', $codePostal)) {
                $error = "Veuillez vérifier le code postal (1 à 5 chiffres).";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez saisir une adresse email valide.";
            } elseif (mb_strlen($nom) > 50 || mb_strlen($prenom) > 50 || mb_strlen($email) > 50 ||
                      mb_strlen($rue) > 50 || mb_strlen($ville) > 50) {
                $error = "Les champs texte ne doivent pas dépasser 50 caractères.";
            } elseif ($password !== '' && strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères (laissez vide pour le conserver).";
            } elseif ($password !== $passwordConfirm) {
                $error = "Les deux mots de passe ne correspondent pas.";
            } else {
                // Email unique sur toute l'application, hors son propre compte
                $practicienExistant = $practicienModel->findByMail($email);
                $visiteurExistant = $visiteurModel->findByMail($email);
                $adminExistant = (new Admin())->isEmailReserved($email);
                $emailOccupe = ($practicienExistant && (int) $practicienExistant['Id_pra'] !== $idPra) || $visiteurExistant || $adminExistant;

                // Mot de passe laissé vide = conservé (null transmis au modèle).
                if ($emailOccupe) {
                    $error = "Cette adresse email est déjà utilisée par un autre compte.";
                } elseif ($practicienModel->updatePracticien($idPra, $nom, $prenom, $email, $rue, $codePostal, $ville, $password !== '' ? $password : null)) {
                    $success = "Vos informations ont été mises à jour avec succès.";
                    $_SESSION['user']['nom'] = $nom;
                    $_SESSION['user']['prenom'] = $prenom;
                    $_SESSION['user']['email'] = $email;
                } else {
                    $error = "Erreur lors de la mise à jour de votre profil.";
                }
            }
        }

        // Relecture du profil après mise à jour (ou pour l'affichage initial).
        $profil = $practicienModel->getPracticienById($idSpe, $idPra);
        if (!$profil) {
            session_destroy();
            header("Location: index.php?action=login");
            exit();
        }
        $specialites = $practicienModel->getAllSpecialites();
        require __DIR__ . '/../views/practicien/profil.php';
    }
}
