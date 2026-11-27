<?php
require_once __DIR__ . '/../models/Visiteur.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Admin.php';

/**
 * Contrôleur d'authentification : traite la connexion, l'inscription et la déconnexion des comptes.
 *
 * Actions gérées : login(), register() et logout().
 * S'appuie sur les modèles Visiteur, Practicien et Admin pour vérifier les identifiants,
 * contrôler les statuts de compte et créer les nouveaux comptes visiteurs.
 * Rend les vues views/auth/login.php et views/auth/register.php.
 * À la connexion réussie, ouvre la session ($_SESSION['user']) avec le rôle correspondant
 * (admin, visiteur ou praticien) et redirige vers l'espace dédié.
 */
class AuthController {
    /**
     * Affiche le formulaire de connexion et traite l'authentification de l'utilisateur.
     *
     * Sans paramètre : lit $_POST (POST) ou $_GET['err'] (GET).
     * En POST, teste successivement le compte admin, puis visiteur, puis praticien ;
     * en cas de succès, ouvre la session et redirige vers l'espace du rôle concerné
     * (action=admin, action=visites ou action=practicien).
     * En cas d'échec ou en GET, réaffiche la vue views/auth/login.php avec éventuellement $error.
     * Contrôle d'accès : aucun prérequis de session (page publique), mais les comptes
     * inactifs / non validés sont explicitement refusés.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

            $visiteurModel = new Visiteur();
            $practicienModel = new Practicien();
            $adminModel = new Admin();

            // Connexion admin : vérifie d'abord qu'un compte administrateur correspond à cet email.
            // Un compte désactivé doit être signalé avant toute tentative d'authentification.
            $adminAccount = $adminModel->findByMail($email);
            if ($adminAccount && $adminAccount['statut_compte'] === 'inactif' &&
                password_verify($password, $adminAccount['mot_de_passe_admin'])) {
                $error = "Ce compte administrateur est désactivé. Contactez un autre administrateur.";
                require __DIR__ . '/../views/auth/login.php';
                return;
            }

            // Authentification admin réussie : ouverture de session avec le rôle "admin"
            // puis redirection vers le tableau de bord administrateur.
            if ($adminModel->authenticate($email, $password)) {
                $admin = $adminAccount ?: $adminModel->findByMail($email);
                $_SESSION['user'] = [
                    'role' => 'admin',
                    'id_admin' => $admin['id_admin'],
                    'nom' => '',
                    'prenom' => 'Administrateur',
                    'email' => $email
                ];
                header("Location: index.php?action=admin");
                exit();
            }

            // Email unique sur toute l'application : un seul compte correspond
            $user = $visiteurModel->findByMail($email);

            // Connexion visiteur : mot de passe vérifié puis statut du compte contrôlé.
            if ($user && $visiteurModel->verifyPassword($user['mot_de_passe_vis'] ?? '', $password)) {
                // Un compte visiteur désactivé ne doit pas ouvrir de session
                if (($user['statut_compte'] ?? 'actif') !== 'actif') {
                    $error = "Ce compte visiteur est désactivé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                // Session visiteur ouverte puis redirection vers la liste des visites
                $_SESSION['user'] = [
                    'role' => 'visiteur',
                    'id_vis' => $user['id_vis'],
                    'nom' => $user['nom_vis'],
                    'prenom' => $user['prenom_vis'],
                    'email' => $user['mail_vis'],
                    'reg_code' => $user['reg_code'] ?? 'Non affecté'
                ];
                header("Location: index.php?action=visites");
                exit();
            }

            // Connexion praticien : contrôle successivement le statut de validation
            // (approuvé par un administrateur) puis le statut du compte.
            $practicien = $practicienModel->findByMail($email);

            if ($practicien && $practicienModel->verifyPassword($practicien['mot_de_passe_par'] ?? '', $password)) {
                // Demande encore en attente ou refusée : connexion bloquée avec message explicite
                if (($practicien['statut_validation'] ?? 'approuve') !== 'approuve') {
                    $error = ($practicien['statut_validation'] ?? '') === 'en_attente'
                        ? "Votre compte praticien est en attente de validation par un administrateur."
                        : "Votre compte praticien n'a pas été approuvé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                // Compte praticien désactivé : connexion refusée
                if (($practicien['statut_compte'] ?? 'actif') !== 'actif') {
                    $error = "Ce compte praticien est désactivé. Contactez un administrateur.";
                    require __DIR__ . '/../views/auth/login.php';
                    return;
                }
                // Session praticien ouverte puis redirection vers l'espace praticien
                $_SESSION['user'] = [
                    'role' => 'practicien',
                    'id_spe' => $practicien['id_spe'],
                    'id_pra' => $practicien['Id_pra'],
                    'nom' => $practicien['nom_par'],
                    'prenom' => $practicien['prenom_par'],
                    'email' => $practicien['mail_par'],
                    'specialite' => $practicien['nom_spe'] ?? 'Non renseignée'
                ];
                header("Location: index.php?action=practicien");
                exit();
            }

            // Aucun compte ne correspond à ces identifiants : message générique
            // (ne pas révéler si l'email existe ou non).
            $error = "Adresse email ou mot de passe incorrect. N'hésitez pas à créer votre profil via le formulaire d'inscription.";
            require __DIR__ . '/../views/auth/login.php';
        } else {
            // Accès en GET : affiche le formulaire, éventuellement avec un message
            // si le compte a été désactivé ou supprimé par un administrateur.
            $error = ($_GET['err'] ?? '') === 'inactive'
                ? "Votre compte a été désactivé ou supprimé. Contactez un administrateur."
                : null;
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    /**
     * Affiche le formulaire d'inscription et crée un compte visiteur.
     *
     * Sans paramètre : lit les champs du formulaire ($_POST) en méthode POST.
     * Valide le mot de passe (8 caractères minimum, confirmation), l'adresse email
     * et son unicité sur les trois tables (visiteur, praticien, administrateur),
     * puis crée le compte via le modèle Visiteur (createVisiteur()).
     * Rend la vue views/auth/register.php avec $error, $success et la liste $regions.
     * Contrôle d'accès : action publique, aucune session requise.
     */
    public function register() {
        $error = null;
        $success = null;

        $visiteurModel = new Visiteur();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom_vis']);
            $prenom = trim($_POST['prenom_vis']);
            $email = trim($_POST['mail_vis']);
            $telephone = $_POST['telephone_vis'];
            $adresse = trim($_POST['adresse_vis']);
            $regCode = $_POST['reg_code'];
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            // Chaîne de validations : complexité du mot de passe, format de l'email,
            // puis unicité de l'adresse sur toute l'application.
            if (strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($password !== $passwordConfirm) {
                $error = "Les deux mots de passe ne correspondent pas.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez saisir une adresse email valide.";
            } elseif ($visiteurModel->findByMail($email) || (new Practicien())->findByMail($email) ||
                      (new Admin())->isEmailReserved($email)) {
                $error = "Cette adresse email est déjà enregistrée.";
            } else {
                // Création effective du compte visiteur (le modèle hache le mot de passe)
                if ($visiteurModel->createVisiteur($nom, $prenom, $email, $telephone, $adresse, $regCode, $password)) {
                    $success = "Compte utilisateur créé avec succès ! Vous pouvez maintenant vous connecter avec votre adresse email ($email) et votre mot de passe.";
                } else {
                    $error = "Erreur lors de la création du compte.";
                }
            }
        }

        // Liste des régions nécessaire au champ « région » du formulaire
        $regions = $visiteurModel->getAllRegions();
        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Déconnecte l'utilisateur en cours.
     *
     * Sans paramètre : détruit la session ($_SESSION) puis redirige vers la page
     * d'accueil (index.php). Contrôle d'accès : aucune vérification de rôle
     * nécessaire, la destruction de session est inoffensive pour un visiteur
     * déjà déconnecté.
     */
    public function logout() {
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
