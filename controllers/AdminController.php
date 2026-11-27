<?php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';

/**
 * Contrôleur d'administration : pilotage des comptes (visiteurs, praticiens, administrateurs).
 *
 * Actions gérées : index() (liste et actions groupées sur les comptes) et edit()
 * (consultation/modification d'un compte administrateur, réinitialisation de mot de passe).
 * S'appuie sur les modèles Admin, Practicien et Visiteur, protège les écritures
 * par un jeton CSRF et affiche les messages de succès/erreur via la query string.
 * Rend les vues views/admin/index.php et views/admin/edit.php.
 * Contrôle d'accès : chaque action publique commence par requireAdmin() (rôle admin requis).
 */
class AdminController {
    /**
     * Affiche la liste des comptes et traite les opérations administrateur (POST).
     *
     * Sans paramètre, lit $_POST['type'], $_POST['id'], $_POST['id_spe'] et $_POST['operation'] :
     *  - account_status  : active/désactive un compte ;
     *  - approval_status : approuve/refuse un compte praticien (type = practicien) ;
     *  - delete_account  : supprime un compte.
     * Chaque opération validée redirige vers la liste avec un message (redirectWithMessage()).
     * En GET, traduit $_GET['message'] / $_GET['error'] en messages affichables.
     * Rend la vue views/admin/index.php avec les comptes, les demandes en attente
     * et un jeton CSRF renouvelé (ensureCsrfToken()).
     * Contrôle d'accès : requireAdmin() (session obligatoire + rôle admin).
     */
    public function index() {
        $this->requireAdmin();
        $adminModel = new Admin();
        $practicienModel = new Practicien();
        $error = null;
        $success = null;

        // Traitement des écritures : le jeton CSRF est vérifié avant toute opération
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->validateCsrfToken();
            if ($error === null) {
                $type = $_POST['type'] ?? '';
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $idSpe = filter_var($_POST['id_spe'] ?? null, FILTER_VALIDATE_INT);
                $operation = $_POST['operation'] ?? '';

                // Paramètres obligatoires et type de compte autorisés
                if (!$id || !in_array($type, ['visiteur', 'practicien', 'admin'], true)) {
                    $error = "Le compte sélectionné est invalide.";
                } elseif ($operation === 'account_status') {
                    // Activation / désactivation du compte (le modèle refuse le dernier admin actif)
                    $result = $adminModel->setAccountStatus($type, $id, $idSpe, $_POST['statut_compte'] ?? '');
                    if ($result === 'updated' || $result === 'unchanged') {
                        $this->redirectWithMessage('status_updated');
                    }
                    $error = $this->statusError($result);
                } elseif ($operation === 'approval_status' && $type === 'practicien' && $idSpe) {
                    // Validation d'une demande de compte praticien (approuvé / refusé)
                    $status = $_POST['statut_validation'] ?? '';
                    if ($adminModel->setApprovalStatus($idSpe, $id, $status)) {
                        $this->redirectWithMessage('approval_updated');
                    }
                    $error = "Le statut de validation est invalide ou le praticien est introuvable.";
                } elseif ($operation === 'delete_account') {
                    // Suppression définitive, refusée si le compte a un historique de visites
                    $result = $adminModel->deleteAccount($type, $id, $idSpe);
                    if ($result === 'deleted') {
                        $this->redirectWithMessage('account_deleted');
                    }
                    $error = $this->deleteError($result);
                } else {
                    $error = "L'opération demandée n'est pas valide.";
                }
            }
        } else {
            // Accès en GET : traduction des codes message / error présents dans l'URL
            // (retour après redirection des opérations ci-dessus).
            $messages = [
                'approved' => "Le compte praticien a été approuvé.",
                'rejected' => "La demande de compte praticien a été refusée.",
                'status_updated' => "Le statut du compte a été mis à jour.",
                'approval_updated' => "Le statut de validation a été mis à jour.",
                'account_deleted' => "Le compte a été supprimé.",
                'account_updated' => "Les informations du compte ont été enregistrées."
            ];
            $success = $messages[$_GET['message'] ?? ''] ?? null;
            $errors = [
                'last_admin' => "Le dernier compte administrateur actif ne peut pas être désactivé ou supprimé.",
                'history' => "Ce compte est lié à des visites ou activités et ne peut pas être supprimé. Désactivez-le plutôt.",
                'not_found' => "Le compte sélectionné est introuvable.",
                'account_readonly' => "Les informations personnelles d'un visiteur ou d'un praticien ne peuvent être modifiées que par lui-même."
            ];
            $error = $errors[$_GET['error'] ?? ''] ?? null;
        }

        // Renouvellement du jeton CSRF puis chargement des données affichées
        $this->ensureCsrfToken();
        $pendingPracticiens = $practicienModel->getPendingForApproval();
        $accounts = $adminModel->getAllAccounts();
        require __DIR__ . '/../views/admin/index.php';
    }

    /**
     * Affiche et traite l'édition d'un compte administrateur (email, mot de passe).
     *
     * Paramètres lus dans $_GET puis éventuellement $_POST : type (doit valoir "admin"),
     * id et id_spe. Les comptes visiteurs/praticiens sont refusés (accès en lecture seule,
     * redirection vers error=account_readonly).
     * Opérations POST possibles : reset_password (réinitialisation du mot de passe,
     * 8 caractères minimum + confirmation) et save (mise à jour de l'email, via saveAccount()).
     * Succès et erreurs affichés sur place ou par redirection (redirectWithMessage()).
     * Rend la vue views/admin/edit.php. Contrôle d'accès : requireAdmin().
     */
    public function edit() {
        $this->requireAdmin();
        $adminModel = new Admin();
        $type = $_GET['type'] ?? $_POST['type'] ?? '';
        $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $idSpe = filter_var($_GET['id_spe'] ?? $_POST['id_spe'] ?? null, FILTER_VALIDATE_INT);

        // Paramètres invalides : retour à la liste des comptes avec message d'erreur
        if (!$id || !in_array($type, ['visiteur', 'practicien', 'admin'], true)) {
            header("Location: index.php?action=admin&error=not_found");
            exit();
        }
        // Seuls les comptes administrateurs sont éditables ici
        if ($type !== 'admin') {
            header("Location: index.php?action=admin&error=account_readonly");
            exit();
        }

        $account = $adminModel->getAccountForEdit($type, $id, $idSpe);
        if (!$account) {
            header("Location: index.php?action=admin&error=not_found");
            exit();
        }

        $error = null;
        $success = null;

        // Écritures protégées par le jeton CSRF
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->validateCsrfToken();
            if ($error === null) {
                $operation = $_POST['operation'] ?? '';
                if ($operation === 'reset_password') {
                    // Réinitialisation : contrôle de longueur, de confirmation,
                    // puis application par le modèle Admin
                    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                    $confirmation = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
                    if (strlen($password) < 8) {
                        $error = "Le mot de passe doit contenir au moins 8 caractères.";
                    } elseif ($password !== $confirmation) {
                        $error = "Les deux mots de passe ne correspondent pas.";
                    } elseif (!$adminModel->resetPassword($type, $id, $idSpe, $password)) {
                        $error = "Le mot de passe n'a pas pu être réinitialisé.";
                    } else {
                        $success = "Le mot de passe a été réinitialisé.";
                    }
                } elseif ($operation === 'save') {
                    // Mise à jour des informations (uniquement l'email pour un admin)
                    $error = $this->saveAccount($adminModel, $type, $id, $idSpe);
                    if ($error === null) {
                        $this->redirectWithMessage('account_updated');
                    }
                } else {
                    $error = "L'opération demandée n'est pas valide.";
                }
            }
            // Rechargement du compte après échec pour réafficher l'état courant
            $account = $adminModel->getAccountForEdit($type, $id, $idSpe);
        }

        // Jeton CSRF régénéré pour le prochain envoi du formulaire
        $this->ensureCsrfToken();
        require __DIR__ . '/../views/admin/edit.php';
    }

    /**
     * Valide les champs d'un compte soumis depuis le formulaire d'édition.
     *
     * Paramètres : $adminModel (modèle Admin), $type, $id et $idSpe du compte ciblé.
     * Vérifie le format et la longueur de l'email, son unicité (hors le compte édité)
     * et que le compte soit bien un administrateur.
     * Retourne null si tout est valide, sinon le message d'erreur à afficher.
     */
    private function saveAccount($adminModel, $type, $id, $idSpe) {
        $email = $this->postText('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Veuillez saisir une adresse email valide.";
        }
        $maxEmail = 254;
        if (mb_strlen($email) > $maxEmail) {
            return "L'adresse email dépasse la longueur autorisée.";
        }
        if ($adminModel->emailInUse($email, $type, $id)) {
            return "Cette adresse email est déjà utilisée par un autre compte.";
        }

        if ($type !== 'admin') {
            return "Les informations de ce compte ne peuvent pas être modifiées ici.";
        }

        return $adminModel->updateAdminEmail($id, $email)
            ? null
            : "Le compte administrateur n'a pas pu être modifié.";
    }

    /**
     * Vérifie que l'utilisateur connecté possède le rôle admin.
     *
     * Redirige vers la connexion (action=login) si la session est absente
     * ou si le rôle n'est pas "admin", puis interrompt l'exécution.
     */
    private function requireAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header("Location: index.php?action=login");
            exit();
        }
    }

    /**
     * Génère le jeton CSRF de session s'il n'existe pas encore.
     *
     * Appelé avant chaque affichage de formulaire pour garantir qu'un token
     * est disponible à la validation (validateCsrfToken()).
     */
    private function ensureCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Lit et nettoie un champ de formulaire ($_POST).
     *
     * Paramètre : $key (nom du champ). Retourne la valeur trimmée si elle est
     * une chaîne, sinon une chaîne vide (évite les tableaux/objets injectés).
     */
    private function postText($key) {
        return trim(is_string($_POST[$key] ?? null) ? $_POST[$key] : '');
    }

    /**
     * Valide le jeton CSRF transmis lors d'une soumission en POST.
     *
     * Compare $_POST['csrf_token'] au jeton de session avec hash_equals().
     * Retourne null si le jeton est valide, sinon le message d'erreur à afficher.
     */
    private function validateCsrfToken() {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)) {
            return "La demande a expiré. Rechargez la page et réessayez.";
        }
        return null;
    }

    /**
     * Redirige vers la liste des comptes avec un message de succès traduit en GET.
     *
     * Paramètre : $message (code traduit en texte lisible par AdminController::index()
     * puis affiché dans la vue).
     */
    private function redirectWithMessage($message) {
        header("Location: index.php?action=admin&message=$message");
        exit();
    }

    /**
     * Convertit le code d'erreur retourné par setAccountStatus() en message lisible.
     *
     * Paramètre : $result (code du modèle). Retourne le message correspondant
     * ou un message générique si le code est inconnu.
     */
    private function statusError($result) {
        $errors = [
            'last_admin' => "Le dernier compte administrateur actif ne peut pas être désactivé.",
            'not_found' => "Le compte sélectionné est introuvable.",
            'invalid' => "Le statut demandé n'est pas valide."
        ];
        return $errors[$result] ?? "Le statut du compte n'a pas pu être modifié.";
    }

    private function deleteError($result) {
        $errors = [
            'last_admin' => "Le dernier compte administrateur actif ne peut pas être supprimé.",
            'has_history' => "Ce compte est lié à des visites ou activités et ne peut pas être supprimé. Désactivez-le plutôt.",
            'not_found' => "Le compte sélectionné est introuvable."
        ];
        return $errors[$result] ?? "Le compte n'a pas pu être supprimé.";
    }
}
