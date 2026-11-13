<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Accède aux comptes du personnel, aux régions et aux secteurs géographiques.
 */
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT u.*, r.nom AS region_nom, s.nom AS secteur_nom 
                                    FROM utilisateur u 
                                    LEFT JOIN region r ON u.id_region = r.id 
                                    LEFT JOIN secteur s ON r.id_secteur = s.id 
                                    WHERE u.email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function getAllUsers() {
        $stmt = $this->db->query("SELECT u.*, r.nom AS region_nom 
                                  FROM utilisateur u 
                                  LEFT JOIN region r ON u.id_region = r.id 
                                  ORDER BY u.nom, u.prenom");
        return $stmt->fetchAll();
    }

    public function getAllRegions() {
        $stmt = $this->db->query("SELECT r.*, s.nom AS secteur_nom FROM region r JOIN secteur s ON r.id_secteur = s.id ORDER BY r.nom ASC");
        return $stmt->fetchAll();
    }

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
