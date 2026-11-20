<?php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Practicien.php';
require_once __DIR__ . '/../models/Visiteur.php';

/**
 * Affiche la gestion des comptes et traite les opérations administrateur.
 */
class AdminController {
    public function index() {
        $this->requireAdmin();
        $adminModel = new Admin();
        $practicienModel = new Practicien();
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->validateCsrfToken();
            if ($error === null) {
                $type = $_POST['type'] ?? '';
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $idSpe = filter_var($_POST['id_spe'] ?? null, FILTER_VALIDATE_INT);
                $operation = $_POST['operation'] ?? '';

                if (!$id || !in_array($type, ['visiteur', 'practicien', 'admin'], true)) {
                    $error = "Le compte sélectionné est invalide.";
                } elseif ($operation === 'account_status') {
                    $result = $adminModel->setAccountStatus($type, $id, $idSpe, $_POST['statut_compte'] ?? '');
                    if ($result === 'updated' || $result === 'unchanged') {
                        $this->redirectWithMessage('status_updated');
                    }
                    $error = $this->statusError($result);
                } elseif ($operation === 'approval_status' && $type === 'practicien' && $idSpe) {
                    $status = $_POST['statut_validation'] ?? '';
                    if ($adminModel->setApprovalStatus($idSpe, $id, $status)) {
                        $this->redirectWithMessage('approval_updated');
                    }
                    $error = "Le statut de validation est invalide ou le praticien est introuvable.";
                } elseif ($operation === 'delete_account') {
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

        $this->ensureCsrfToken();
        $pendingPracticiens = $practicienModel->getPendingForApproval();
        $accounts = $adminModel->getAllAccounts();
        require __DIR__ . '/../views/admin/index.php';
    }

    public function edit() {
        $this->requireAdmin();
        $adminModel = new Admin();
        $type = $_GET['type'] ?? $_POST['type'] ?? '';
        $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $idSpe = filter_var($_GET['id_spe'] ?? $_POST['id_spe'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || !in_array($type, ['visiteur', 'practicien', 'admin'], true)) {
            header("Location: index.php?action=admin&error=not_found");
            exit();
        }
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->validateCsrfToken();
            if ($error === null) {
                $operation = $_POST['operation'] ?? '';
                if ($operation === 'reset_password') {
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
                    $error = $this->saveAccount($adminModel, $type, $id, $idSpe);
                    if ($error === null) {
                        $this->redirectWithMessage('account_updated');
                    }
                } else {
                    $error = "L'opération demandée n'est pas valide.";
                }
            }
            $account = $adminModel->getAccountForEdit($type, $id, $idSpe);
        }

        $this->ensureCsrfToken();
        require __DIR__ . '/../views/admin/edit.php';
    }

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

    private function requireAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header("Location: index.php?action=login");
            exit();
        }
    }

    private function ensureCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function postText($key) {
        return trim(is_string($_POST[$key] ?? null) ? $_POST[$key] : '');
    }

    private function validateCsrfToken() {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)) {
            return "La demande a expiré. Rechargez la page et réessayez.";
        }
        return null;
    }

    private function redirectWithMessage($message) {
        header("Location: index.php?action=admin&message=$message");
        exit();
    }

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
