<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle du personnel : couvre les tables SQL « utilisateur », « region » et « secteur ».
 * Propose la recherche d'un utilisateur par e-mail (avec nom de région et de secteur),
 * la liste de tous les utilisateurs, la liste des régions rattachées à leur secteur
 * et la création d'un compte utilisateur avec mot de passe haché (password_hash).
 * Toutes les lectures sont sans effet de bord ; seule createUser écrit en base.
 */
class User {
    private $db;

    /**
     * Initialise la connexion à la base de données utilisée par les méthodes du modèle.
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Recherche un utilisateur du personnel par adresse e-mail.
     * Jointures LEFT JOIN : le nom de région et de secteur peuvent être nuls
     * si l'utilisateur n'est rattaché à aucune région.
     * @param string $email Adresse e-mail recherchée.
     * @return array|false Ligne complète de l'utilisateur ou false si absent.
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT u.*, r.nom AS region_nom, s.nom AS secteur_nom 
                                    FROM utilisateur u 
                                    LEFT JOIN region r ON u.id_region = r.id 
                                    LEFT JOIN secteur s ON r.id_secteur = s.id 
                                    WHERE u.email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Liste tous les utilisateurs du personnel avec le nom de leur région, triés par nom puis prénom.
     * Jointure LEFT JOIN : un utilisateur sans région apparaît quand même (region_nom null).
     * @return array Liste complète des comptes utilisateur.
     */
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT u.*, r.nom AS region_nom 
                                  FROM utilisateur u 
                                  LEFT JOIN region r ON u.id_region = r.id 
                                  ORDER BY u.nom, u.prenom");
        return $stmt->fetchAll();
    }

    /**
     * Liste les régions avec le nom de leur secteur géographique, triées par nom de région.
     * @return array Liste des régions enrichie de « secteur_nom » (JOIN sur secteur).
     */
    public function getAllRegions() {
        $stmt = $this->db->query("SELECT r.*, s.nom AS secteur_nom FROM region r JOIN secteur s ON r.id_secteur = s.id ORDER BY r.nom ASC");
        return $stmt->fetchAll();
    }

    /**
     * Crée un compte utilisateur du personnel avec son mot de passe haché.
     * @param string $nom Nom de l'utilisateur.
     * @param string $prenom Prénom de l'utilisateur.
     * @param string $email Adresse e-mail (unique en base).
     * @param string $password Mot de passe en clair, haché avant enregistrement.
     * @param string $role Rôle attribué au compte.
     * @param int $idRegion Identifiant de la région de rattachement.
     * @return bool Résultat de l'exécution de l'INSERT (true en cas de succès).
     * Effet de bord : écriture d'une ligne dans la table utilisateur.
     */
    public function createUser($nom, $prenom, $email, $password, $role, $idRegion) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, id_region) VALUES (:nom, :prenom, :email, :password, :role, :id_region)");
        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'id_region' => $idRegion
        ]);
    }
}
