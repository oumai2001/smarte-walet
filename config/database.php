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

// ======= FONCTIONS CARTES =======
function get_user_cards($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_primary DESC, card_name");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_primary_card($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? AND is_primary = 1 LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function update_card_balance($card_id, $amount, $type, $pdo) {
    // $type = 'add' ou 'subtract'
    $operator = ($type == 'add') ? '+' : '-';
    $stmt = $pdo->prepare("UPDATE cards SET balance = balance $operator ? WHERE id = ?");
    return $stmt->execute([$amount, $card_id]);
}

// ======= FONCTIONS LIMITES =======
function get_monthly_spent($user_id, $category_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE user_id = ? 
        AND category_id = ? 
        AND MONTH(expense_date) = MONTH(CURDATE())
        AND YEAR(expense_date) = YEAR(CURDATE())
    ");
    $stmt->execute([$user_id, $category_id]);
    return $stmt->fetch()['total'];
}

function get_category_limit($user_id, $category_id, $pdo) {
    $stmt = $pdo->prepare("SELECT monthly_limit FROM category_limits WHERE user_id = ? AND category_id = ?");
    $stmt->execute([$user_id, $category_id]);
    $result = $stmt->fetch();
    return $result ? $result['monthly_limit'] : null;
}

function check_limit_exceeded($user_id, $category_id, $new_amount, $pdo) {
    $limit = get_category_limit($user_id, $category_id, $pdo);
    if ($limit === null) return false; // Pas de limite
    
    $spent = get_monthly_spent($user_id, $category_id, $pdo);
    return ($spent + $new_amount) > $limit;
}