<?php
require_once 'config/database.php';
require_login();

$page_title = 'Transferts';

// Gestion du transfert
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send'])) {
    $recipient = clean_input($_POST['recipient']); // Email ou unique_id
    $amount = clean_input($_POST['amount']);
    $description = clean_input($_POST['description']);
    
    if (empty($recipient) || empty($amount)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être un nombre positif";
    } else {
        // Trouver le destinataire
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR unique_id = ?");
        $stmt->execute([$recipient, $recipient]);
        $receiver = $stmt->fetch();
        
        if (!$receiver) {
            $error = "Utilisateur introuvable";
        } elseif ($receiver['id'] == get_user_id()) {
            $error = "Vous ne pouvez pas vous envoyer de l'argent à vous-même";
        } else {
            // Récupérer la carte principale de l'expéditeur
            $sender_card = get_primary_card(get_user_id(), $pdo);
            
            if (!$sender_card) {
                $error = "Vous n'avez pas de carte principale";
            } elseif ($sender_card['balance'] < $amount) {
                $error = "Solde insuffisant (Solde: " . number_format($sender_card['balance'], 2) . " DH)";
            } else {
                // Récupérer la carte principale du destinataire
                $receiver_card = get_primary_card($receiver['id'], $pdo);
                
                if (!$receiver_card) {
                    $error = "Le destinataire n'a pas de carte principale";
                } else {
                    // Effectuer le transfert
                    try {
                        $pdo->beginTransaction();
                        
                        // Déduire de l'expéditeur
                        update_card_balance($sender_card['id'], $amount, 'subtract', $pdo);
                        
                        // Ajouter au destinataire
                        update_card_balance($receiver_card['id'], $amount, 'add', $pdo);
                        
                        // Enregistrer le transfert
                        $stmt = $pdo->prepare("INSERT INTO transfers (sender_id, receiver_id, amount, description) VALUES (?, ?, ?, ?)");
                        $stmt->execute([get_user_id(), $receiver['id'], $amount, $description]);
                        
                        $pdo->commit();
                        $success = "Transfert effectué avec succès !";
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Erreur lors du transfert";
                    }
                }
            }
        }
    }
}

// Récupérer l'historique des transferts
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        sender.full_name as sender_name,
        sender.unique_id as sender_uid,
        receiver.full_name as receiver_name,
        receiver.unique_id as receiver_uid
    FROM transfers t
    JOIN users sender ON t.sender_id = sender.id
    JOIN users receiver ON t.receiver_id = receiver.id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.transfer_date DESC
");
$stmt->execute([get_user_id(), get_user_id()]);
$transfers = $stmt->fetchAll();

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("SELECT unique_id FROM users WHERE id = ?");
$stmt->execute([get_user_id()]);
$my_unique_id = $stmt->fetch()['unique_id'];

// Carte principale
$primary_card = get_primary_card(get_user_id(), $pdo);

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-exchange-alt"></i> Transferts d'Argent</h2>
    
    <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 1.5rem; border-radius: var(--radius); color: white; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Votre ID unique</div>
                <div style="font-size: 1.5rem; font-weight: 600; margin-top: 0.25rem;">
                    <?= $my_unique_id ?>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 0.5rem;">
                    Partagez cet ID pour recevoir des transferts
                </div>
            </div>
            <?php if ($primary_card): ?>
                <div style="text-align: right;">
                    <div style="font-size: 0.9rem; opacity: 0.9;">Carte principale</div>
                    <div style="font-size: 1.25rem; font-weight: 600; margin-top: 0.25rem;">
                        <?= htmlspecialchars($primary_card['card_name']) ?>
                    </div>
                    <div style="font-size: 1rem; opacity: 0.9; margin-top: 0.25rem;">
                        Solde: <?= number_format($primary_card['balance'], 2) ?> DH
                    </div>
                </div>
            <?php endif; ?>
        </div>
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

    <?php if (!$primary_card): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> 
            Vous devez avoir une carte principale pour envoyer/recevoir des transferts. 
            <a href="cards.php" style="color: var(--danger); font-weight: 600; text-decoration: underline;">Gérer mes cartes</a>
        </div>
    <?php endif; ?>

    <form method="POST" style="background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem; <?= !$primary_card ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-paper-plane"></i> Envoyer de l'Argent</h3>
        
        <div class="form-group">
            <label>Email ou ID du destinataire</label>
            <input type="text" name="recipient" placeholder="Ex: user@email.com ou SW000002" required>
        </div>
        
        <div class="form-group">
            <label>Montant (DH)</label>
            <input type="number" step="0.01" name="amount" required>
        </div>
        
        <div class="form-group">
            <label>Description (optionnelle)</label>
            <input type="text" name="description" placeholder="Ex: Remboursement">
        </div>
        
        <button type="submit" name="send" class="btn btn-success">
            <i class="fas fa-paper-plane"></i> Envoyer
        </button>
    </form>

    <!-- Historique des transferts -->
    <h3 style="margin-bottom: 1rem;"><i class="fas fa-history"></i> Historique</h3>
    
    <table>
        <thead>
            <tr>
                <th><i class="fas fa-exchange-alt"></i> Type</th>
                <th><i class="fas fa-user"></i> De/Vers</th>
                <th><i class="fas fa-money-bill-wave"></i> Montant</th>
                <th><i class="fas fa-comment"></i> Description</th>
                <th><i class="fas fa-calendar"></i> Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transfers as $transfer): ?>
                <?php 
                    $is_sender = ($transfer['sender_id'] == get_user_id());
                    $other_user = $is_sender ? $transfer['receiver_name'] : $transfer['sender_name'];
                    $other_uid = $is_sender ? $transfer['receiver_uid'] : $transfer['sender_uid'];
                ?>
                <tr>
                    <td>
                        <?php if ($is_sender): ?>
                            <span style="color: var(--danger);"><i class="fas fa-arrow-up"></i> Envoyé</span>
                        <?php else: ?>
                            <span style="color: var(--success);"><i class="fas fa-arrow-down"></i> Reçu</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($other_user) ?></strong><br>
                        <small style="color: var(--text-secondary);"><?= $other_uid ?></small>
                    </td>
                    <td style="font-weight: bold; color: <?= $is_sender ? 'var(--danger)' : 'var(--success)' ?>">
                        <?= $is_sender ? '-' : '+' ?><?= number_format($transfer['amount'], 2) ?> DH
                    </td>
                    <td><?= $transfer['description'] ? htmlspecialchars($transfer['description']) : '-' ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($transfer['transfer_date'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($transfers)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>Aucun transfert pour le moment</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>