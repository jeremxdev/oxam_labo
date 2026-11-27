<?php
/**
 * Centralise la création et la configuration de la connexion PDO à MySQL.
 *
 * Rôle : fournir, via la méthode statique getConnection(), un unique objet PDO
 * partagé par tous les modèles (models/*.php) — les contrôleurs passent
 * obligatoirement par les modèles, qui appellent cette classe.
 *
 * Base cible : "oxam_labo" (voir schema.sql), jeu de caractères utf8mb4
 * pour les accents français.
 */
class Database {
    // Identifiants de connexion (serveur Laragon en local : root sans mot de passe)
    private static $host = "localhost";
    private static $db_name = "oxam_labo";
    private static $username = "root";
    private static $password = "";
    // Instance PDO unique (patron Singleton) : évite d'ouvrir plusieurs connexions
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                // Connexion PDO : DSN MySQL avec utf8mb4 pour supporter les accents.
                self::$conn = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4", self::$username, self::$password);
                // Mode strict : toute erreur SQL lève une PDOException (attrapée
                // plus bas, puis message d'erreur affiché et script interrompu).
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Les résultats sont retournés en tableaux associatifs ['colonne' => valeur]
                // : c'est le format attendu par les contrôleurs et les vues.
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        // Réutilisation de la même connexion pour toutes les requêtes suivantes.
        return self::$conn;
    }
}
