<?php
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Visite.php';

/**
 * Gère l'inscription, l'espace personnel et la mise à jour du profil des praticiens.
 */
class PracticienController {
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
            $idSpe = ($_POST['id_spe'] ?? '') === '' ? null : filter_var($_POST['id_spe'], FILTER_VALIDATE_INT);
            $nouvelleSpecialite = trim($_POST['nouvelle_specialite'] ?? '');
            $aChoisiSpecialite = $idSpe !== null && $idSpe !== false && $idSpe > 0;
            $specialiteValide = ($aChoisiSpecialite && $nouvelleSpecialite === '') ||
                ($idSpe === null && $nouvelleSpecialite !== '');

            if ($nom === '' || $prenom === '' || $rue === '' || $ville === '' ||
                !preg_match('/^\d{1,5}$/', $codePostal) || !$specialiteValide) {
                $error = "Veuillez vérifier les champs, le code postal et le choix de spécialité.";
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

            // Email unique sur toute l'application (visiteurs ET praticiens)
            if ($error === null && ($practicienModel->findByMail($email) || $visiteurModel->findByMail($email))) {
                $error = "Cette adresse email est déjà utilisée.";
            }

            if ($error === null && (
                mb_strlen($nom) > 50 || mb_strlen($prenom) > 50 ||
                mb_strlen($rue) > 50 || mb_strlen($ville) > 50 ||
                mb_strlen($nouvelleSpecialite) > 50 || mb_strlen($email) > 50)) {
                $error = "Les champs texte ne doivent pas dépasser 50 caractères.";
            }

            if ($error === null && $practicienModel->createPracticien($aChoisiSpecialite ? $idSpe : null, $nouvelleSpecialite, $nom, $prenom, $rue, $codePostal, $ville, $email, $password)) {
                $success = "Votre compte praticien a été créé. Vous pouvez maintenant vous connecter avec votre adresse email ($email) et votre mot de passe.";
            } elseif ($error === null) {
                $error = "Une erreur est survenue lors de la création du compte praticien.";
            }
        }

        $specialites = $practicienModel->getAllSpecialites();
        require __DIR__ . '/../views/auth/register_practicien.php';
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'practicien') {
            header("Location: index.php?action=visites");
            exit();
        }

        $practicienModel = new Practicien();
        $visiteModel = new Visite();

        $profil = $practicienModel->getPracticienById($_SESSION['user']['id_spe'], $_SESSION['user']['id_pra']);
        if (!$profil) {
            session_destroy();
            header("Location: index.php?action=login");
            exit();
        }

        $visites = $visiteModel->getVisitesByPracticien($_SESSION['user']['id_spe'], $_SESSION['user']['id_pra']);
        require __DIR__ . '/../views/practicien/index.php';
    }

    public function profil() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit();
        }
        if (($_SESSION['user']['role'] ?? 'visiteur') !== 'practicien') {
            header("Location: index.php?action=visites");
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
                $emailOccupe = ($practicienExistant && (int) $practicienExistant['Id_pra'] !== $idPra) || $visiteurExistant;

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
