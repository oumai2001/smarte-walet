<?php
require_once 'config/database.php';
require_once 'config/email_config.php';

if (!isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Vérification OTP';
$user_email = $_SESSION['temp_user_email'] ?? '';
$user_name = $_SESSION['temp_user_name'] ?? '';

// Gestion de la vérification OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp_input = clean_input($_POST['otp']);
    $user_id = $_SESSION['temp_user_id'];

    if (empty($otp_input)) {
        $error = "Veuillez entrer le code OTP";
    } elseif (!preg_match('/^\d{6}$/', $otp_input)) {
        $error = "Le code OTP doit contenir 6 chiffres";
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM otp_codes
            WHERE user_id = ? AND code = ? AND is_used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $otp_input]);
        $otp = $stmt->fetch();

        if ($otp) {
            // Marquer le code comme utilisé
            $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
            $stmt->execute([$otp['id']]);

            // Connexion automatique
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user_name;

            // Supprimer les variables temporaires
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_email']);
            unset($_SESSION['temp_user_name']);

            $_SESSION['success_message'] = "Bienvenue ! Votre compte a été vérifié avec succès.";
            header('Location: index.php');
            exit;
        } else {
            $error = "Code OTP invalide ou expiré";
        }
    }
}

// Gestion du renvoi d'OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp'])) {
    $user_id = $_SESSION['temp_user_id'];
    
    // Vérifier qu'on ne spam pas (max 1 renvoi par minute)
    $stmt = $pdo->prepare("
        SELECT created_at FROM otp_codes
        WHERE user_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $last_otp = $stmt->fetch();
    
    if ($last_otp && strtotime($last_otp['created_at']) > strtotime('-1 minute')) {
        $error = "Veuillez attendre avant de demander un nouveau code";
    } else {
        // Générer un nouveau OTP
        $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $otp_code, $expires_at]);

        // Envoyer le nouveau code
        if (send_otp_email($user_email, $user_name, $otp_code)) {
            $success = "Un nouveau code a été envoyé à votre email";
        } else {
            $error = "Erreur lors de l'envoi du code";
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
    <style>
        .otp-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .info-box {
            background: #f0f7ff;
            border-left: 4px solid var(--primary);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .resend-link {
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
        }
        .resend-link:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); width: 100%; max-width: 450px;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin-bottom: 1rem;">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Vérifiez votre email</h1>
                <p style="color: var(--text-secondary);">
                    Nous avons envoyé un code à<br>
                    <strong><?= htmlspecialchars($user_email) ?></strong>
                </p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-info-circle"></i> 
                <strong>Entrez le code à 6 chiffres</strong> reçu par email. Il expire dans 10 minutes.
            </div>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="otp" class="otp-input" placeholder="000000" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code">
                    <small style="color: var(--text-secondary); display: block; text-align: center; margin-top: 0.5rem;">
                        Code à 6 chiffres
                    </small>
                </div>

                <button type="submit" name="verify_otp" class="btn" style="width: 100%; justify-content: center; margin-top: 1rem;">
                    <i class="fas fa-check"></i> Vérifier le code
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                    Vous n'avez pas reçu le code ?
                </p>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="resend_otp" class="resend-link" style="background: none; border: none; padding: 0; font: inherit;">
                        <i class="fas fa-redo"></i> Renvoyer le code
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="login.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit quand 6 chiffres sont entrés
        const otpInput = document.querySelector('input[name="otp"]');
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '');
            if (this.value.length === 6) {
                // Auto-submit après une courte pause
                setTimeout(() => {
                    this.form.querySelector('button[name="verify_otp"]').click();
                }, 300);
            }
        });
    </script>
</body>
</html>