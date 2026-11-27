<?php
require_once __DIR__ . '/../models/AC.php';
require_once __DIR__ . '/../models/Visiteur.php';

/**
 * Contrôleur d'administration des activités complémentaires (AC).
 *
 * Actions gérées : index() (liste de toutes les AC), create(), edit() et delete().
 * Complète AcController (réservé aux visiteurs) en donnant à l'administrateur
 * le CRUD complet sur les tables AC et Organiser via ACModel.
 * La liste des visiteurs organisateurs est chargée avec Visiteur::getAllVisiteurs().
 * Rend les vues views/ac/admin/index.php, create.php et edit.php.
 *
 * Contrôle d'accès : session obligatoire et rôle "admin" (vérifiés dans le
 * constructeur). Toutes les écritures POST sont protégées par un jeton CSRF,
 * y compris la suppression (aucune suppression par simple lien GET).
 * Les messages de succès/erreur sont transmis par la query string puis traduits
 * dans la vue de liste.
 */
class AdminAcController {
    /** Modèle des activités complémentaires (lecture/écriture). */
    private $acModel;
    /** Modèle des visiteurs, utilisé pour choisir les organisateurs. */
    private $visiteurModel;

    /**
     * Vérifie la session et le rôle admin, puis instancie les modèles.
     * Sans session ou avec un autre rôle : redirection vers la connexion.
     * Garantit également qu'un jeton CSRF est disponible pour les formulaires.
     */
    public function __construct() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header("Location: index.php?action=login");
            exit();
        }
        $this->acModel = new ACModel();
        $this->visiteurModel = new Visiteur();
        $this->ensureCsrfToken();
    }

    /**
     * Génère le jeton CSRF de session s'il n'existe pas encore.
     * Le même jeton que celui utilisé par AdminController est réutilisé.
     */
    private function ensureCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Vérifie le jeton CSRF reçu en POST.
     * @return string|null Message d'erreur, ou null si le jeton est valide.
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
     * Redirige vers la liste des AC avec un code de message dans l'URL.
     * @param string $code Code traduit ensuite par queryMessages().
     */
    private function redirectWithMessage($code) {
        header("Location: index.php?action=admin_ac&$code");
        exit();
    }

    /**
     * Traduit les codes de message de la query string en textes affichables.
     * @return array [message de succès ou null, message d'erreur ou null]
     */
    private function queryMessages() {
        $successCodes = [
            'created' => "L'activité complémentaire a été créée.",
            'updated' => "L'activité complémentaire a été modifiée.",
            'deleted' => "L'activité complémentaire a été supprimée.",
        ];
        $errorCodes = [
            'not_found' => "Cette activité complémentaire est introuvable.",
            'invalid' => "La demande a expiré. Rechargez la page et réessayez.",
            'delete_failed' => "La suppression de l'activité a échoué.",
        ];
        $success = null;
        $error = null;
        foreach ($successCodes as $code => $text) {
            if (isset($_GET[$code])) {
                $success = $text;
                break;
            }
        }
        foreach ($errorCodes as $code => $text) {
            if (isset($_GET[$code])) {
                $error = $text;
                break;
            }
        }
        return [$success, $error];
    }

    /**
     * Lit et nettoie un champ texte de $_POST.
     * @param string $key Nom du champ.
     * @return string Chaîne vide si le champ est absent ou n'est pas une chaîne.
     */
    private function postText($key) {
        return trim(is_string($_POST[$key] ?? null) ? $_POST[$key] : '');
    }

    /**
     * Lit les organisateurs cochés dans le formulaire.
     * Filtre les valeurs non entières et les doublons pour éviter des lignes
     * orphelines dans Organiser.
     * @return array Identifiants de visiteurs valides (peut être vide).
     */
    private function postOrganisateurs() {
        $ids = $_POST['organisateurs'] ?? [];
        if (!is_array($ids)) {
            return [];
        }
        $valid = [];
        foreach ($ids as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $valid[] = (int) $id;
            }
        }
        return array_values(array_unique($valid));
    }

    /**
     * Valide les champs communs aux formulaires de création et de modification.
     * @return string|null Message d'erreur, ou null si les champs sont valides.
     */
    private function validateFields($theme, $lieu, $date) {
        if ($theme === '' || $lieu === '' || $date === '') {
            return "Le thème, le lieu et la date sont obligatoires.";
        }
        if (mb_strlen($theme) > 20 || mb_strlen($lieu) > 50) {
            return "Le thème ne doit pas dépasser 20 caractères et le lieu 50 caractères.";
        }
        $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            return "La date saisie est invalide (format attendu : AAAA-MM-JJ).";
        }
        return null;
    }

    /**
     * Affiche toutes les activités complémentaires avec leurs organisateurs.
     * Traduit les éventuels codes de message renvoyés par les redirections puis
     * rend views/ac/admin/index.php avec $activites, $success et $error.
     */
    public function index() {
        $activites = $this->acModel->getAllAC();
        [$success, $error] = $this->queryMessages();
        require __DIR__ . '/../views/ac/admin/index.php';
    }

    /**
     * Affiche le formulaire de création d'une AC et traite sa soumission.
     *
     * En POST : vérifie le jeton CSRF, valide les champs, filtre les organisateurs
     * choisis et crée l'activité via ACModel::createAC() avant de rediriger vers
     * la liste avec le code "created".
     * En GET (ou après échec) : charge la liste des visiteurs et rend
     * views/ac/admin/create.php avec $form et $error.
     */
    public function create() {
        $error = null;
        $form = [
            'theme' => '',
            'lieu' => '',
            'date_ac' => date('Y-m-d'),
            'organisateurs' => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $theme = $this->postText('theme');
            $lieu = $this->postText('lieu');
            $date = $this->postText('date_ac');
            $organisateurs = $this->postOrganisateurs();
            // Les valeurs saisies sont conservées pour réafficher le formulaire en cas d'erreur.
            $form = [
                'theme' => $theme,
                'lieu' => $lieu,
                'date_ac' => $date,
                'organisateurs' => $organisateurs,
            ];

            $error = $this->validateCsrfToken();
            if ($error === null) {
                $error = $this->validateFields($theme, $lieu, $date);
            }
            if ($error === null) {
                if ($this->acModel->createAC($theme, $lieu, $date, $organisateurs)) {
                    $this->redirectWithMessage('created');
                }
                $error = "L'activité complémentaire n'a pas pu être créée.";
            }
        }

        $visiteurs = $this->visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/ac/admin/create.php';
    }

    /**
     * Affiche le formulaire de modification d'une AC et enregistre les changements.
     *
     * L'activité est identifiée par $_GET['id'] ou $_POST['id'] ; une activité
     * introuvable provoque une redirection avec le code "not_found".
     * En POST : vérifie le jeton CSRF, valide les champs, remplace les
     * organisateurs via ACModel::updateAC() puis redirige avec le code "updated".
     * Rend views/ac/admin/edit.php avec $activite, $form, $visiteurs et $error.
     */
    public function edit() {
        $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $activite = $id ? $this->acModel->getACById($id) : false;
        if (!$activite) {
            $this->redirectWithMessage('not_found');
        }

        // GROUP_CONCAT renvoie une chaîne d'ids séparés par des virgules (ou null).
        $organisateursActuels = [];
        if (!empty($activite['organisateur_ids'])) {
            $organisateursActuels = array_map('intval', explode(',', $activite['organisateur_ids']));
        }
        $form = [
            'theme' => $activite['theme'],
            'lieu' => $activite['lieu'],
            'date_ac' => $activite['date_ac'],
            'organisateurs' => $organisateursActuels,
        ];

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $theme = $this->postText('theme');
            $lieu = $this->postText('lieu');
            $date = $this->postText('date_ac');
            $organisateurs = $this->postOrganisateurs();
            $form = [
                'theme' => $theme,
                'lieu' => $lieu,
                'date_ac' => $date,
                'organisateurs' => $organisateurs,
            ];

            $error = $this->validateCsrfToken();
            if ($error === null) {
                $error = $this->validateFields($theme, $lieu, $date);
            }
            if ($error === null) {
                if ($this->acModel->updateAC((int) $activite['id_ac'], $theme, $lieu, $date, $organisateurs)) {
                    $this->redirectWithMessage('updated');
                }
                $error = "L'activité complémentaire n'a pas pu être modifiée.";
            }
        }

        $visiteurs = $this->visiteurModel->getAllVisiteurs();
        require __DIR__ . '/../views/ac/admin/edit.php';
    }

    /**
     * Supprime une activité complémentaire après vérification du jeton CSRF.
     *
     * La suppression passe obligatoirement par POST : aucun lien GET ne peut
     * déclencher cette action. Redirige vers la liste avec "deleted" en cas de
     * succès, "delete_failed" ou "invalid" sinon (aucune vue dédiée).
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithMessage('invalid');
        }
        $error = $this->validateCsrfToken();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($error === null && $id && $this->acModel->deleteAC($id)) {
            $this->redirectWithMessage('deleted');
        }
        $this->redirectWithMessage($error !== null ? 'invalid' : 'delete_failed');
    }
}
