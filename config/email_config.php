<?php
/**
 * Configuration Email pour l'envoi d'OTP
 * Créez ce fichier et ajoutez vos vraies credentials
 */
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'oumaimaeddahani0@gmail.com'); 
define('SMTP_PASSWORD', 'gbsyrudiidwrwjiq');   
define('SMTP_FROM_EMAIL', 'oumaimaeddahani0@gmail.com');
define('SMTP_FROM_NAME', 'Smarte Walet');

// Fonction pour envoyer l'OTP
function send_otp_email($recipient_email, $recipient_name, $otp_code) {
    require 'vendor/autoload.php';
    

    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Expéditeur et destinataire
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Code de vérification - Smarte Walet';
        $mail->Body = get_otp_email_template($recipient_name, $otp_code);
        $mail->AltBody = "Votre code de vérification est : $otp_code. Il expire dans 10 minutes.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur envoi email OTP : " . $mail->ErrorInfo);
        return false;
    }
}

// Template HTML professionnel pour l'email
function get_otp_email_template($name, $otp_code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
            .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Code de Vérification</h1>
            </div>
            <div class='content'>
                <p>Bonjour <strong>$name</strong>,</p>
                <p>Vous avez demandé à vous connecter à votre compte <strong>Smarte Walet</strong>.</p>
                <p>Voici votre code de vérification :</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>$otp_code</div>
                    <p style='margin-top: 10px; color: #666;'>Ce code expire dans <strong>10 minutes</strong></p>
                </div>
                
                <p><strong>⚠️ Important :</strong></p>
                <ul>
                    <li>Ne partagez jamais ce code avec qui que ce soit</li>
                    <li>Notre équipe ne vous demandera jamais ce code</li>
                    <li>Si vous n'avez pas demandé ce code, ignorez cet email</li>
                </ul>
                
                <div class='footer'>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    <p>&copy; " . date('Y') . " Smarte Walet - Tous droits réservés</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}