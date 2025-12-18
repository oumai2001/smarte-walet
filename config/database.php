<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smarte_walet');

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour nettoyer les données
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Fonction pour valider un montant
function validate_amount($amount) {
    return is_numeric($amount) && $amount > 0;
}

// Fonction pour valider une date
function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ======= FONCTIONS D'AUTHENTIFICATION =======
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_name() {
    return $_SESSION['user_name'] ?? 'Utilisateur';
}
