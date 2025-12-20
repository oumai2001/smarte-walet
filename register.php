<?php
require_once 'config/database.php';
require_once 'config/email_config.php';

// Si l'utilisateur est déjà connecté, rediriger vers le dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$page_title = 'Inscription';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé";
        } else {
            // Créer le compte utilisateur
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$full_name, $email, $hashed_password])) {
                $user_id = $pdo->lastInsertId();

                // Générer un OTP
                $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
               // Générer unique_id
$unique_id = 'SW' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
$stmt = $pdo->prepare("UPDATE users SET unique_id = ? WHERE id = ?");
$stmt->execute([$unique_id, $user_id]);

// Créer une carte par défaut
$stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, bank_name, is_primary) VALUES (?, 'Carte Principale', 'Ma Banque', 1)");
$stmt->execute([$user_id]);
                $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $otp_code, $expires_at]);

                // Envoyer l'OTP par email
                if (send_otp_email($email, $full_name, $otp_code)) {
                    // Stocker temporairement les infos de l'utilisateur avant validation
                    $_SESSION['temp_user_id'] = $user_id;
                    $_SESSION['temp_user_email'] = $email;
                    $_SESSION['temp_user_name'] = $full_name;
                    
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $error = "Erreur lors de l'envoi du code de vérification. Veuillez réessayer.";
                    // Supprimer l'utilisateur créé car l'email n'a pas pu être envoyé
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                }
            } else {
                $error = "Erreur lors de la création du compte";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Smarte Walet</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); width: 100%; max-width: 450px;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: var(--radius); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin-bottom: 1rem;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Créer un compte</h1>
                <p style="color: var(--text-secondary);">Rejoignez Smarte Walet</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nom Complet</label>
                    <input type="text" name="full_name" placeholder="Ex: Ahmed Bennani" required value="<?= $_POST['full_name'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="votre@email.com" required value="<?= $_POST['email'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="password" placeholder="Minimum 6 caractères" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirmer mot de passe</label>
                    <input type="password" name="confirm_password" placeholder="Retapez votre mot de passe" required>
                </div>

                <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary);">
                    Vous avez déjà un compte ? 
                    <a href="login.php" style="color: var(--primary); font-weight: 500; text-decoration: none;">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>