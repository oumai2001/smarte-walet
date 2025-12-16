<?php
require_once 'config/database.php';

// Si déjà connecté, rediriger
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$page_title = 'Connexion';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        // Vérifier les identifiants
        $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Connexion directe sans OTP
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];

            header('Location: index.php');
            exit;
        } else {
            $error = "Email ou mot de passe incorrect";
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
                <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Connexion</h1>
                <p style="color: var(--text-secondary);">Accédez à votre espace financier</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="votre@email.com" required value="<?= $_POST['email'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="password" placeholder="Votre mot de passe" required>
                </div>

                <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 1rem;">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary);">
                    Pas encore de compte ? 
                    <a href="register.php" style="color: var(--primary); font-weight: 500; text-decoration: none;">
                        S'inscrire
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
