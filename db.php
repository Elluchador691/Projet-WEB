<?php
/* ============================================================
   config/db.php — Connexion à la base de données MySQL
   Modifier les constantes selon ton environnement XAMPP/WAMP
   ============================================================ */
 
define('DB_HOST', 'localhost');
define('DB_NAME', 'ideastage');
define('DB_USER', 'root');       // Utilisateur par défaut XAMPP/WAMP
define('DB_PASS', '');           // Mot de passe vide par défaut en local
define('DB_CHARSET', 'utf8mb4');
 
function getDB(): PDO {
    static $pdo = null;
 
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Connexion base de données échouée : ' . $e->getMessage()]));
        }
    }
 
    return $pdo;
}




























