<?php
require_once 'config/database.php';
require_login();

$page_title = 'Mes Cartes';

// Gestion de l'ajout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $card_name = clean_input($_POST['card_name']);
    $bank_name = clean_input($_POST['bank_name']);
    
    if (empty($card_name) || empty($bank_name)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, bank_name, balance, is_primary) VALUES (?, ?, ?, 0, 0)");
        if ($stmt->execute([get_user_id(), $card_name, $bank_name])) {
            $success = "Carte ajoutée avec succès !";
        } else {
            $error = "Erreur lors de l'ajout";
        }
    }
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier que ce n'est pas la carte principale
    $stmt = $pdo->prepare("SELECT is_primary FROM cards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, get_user_id()]);
    $card = $stmt->fetch();
    
    if ($card && $card['is_primary'] == 1) {
        $error = "Impossible de supprimer la carte principale";
    } else {
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, get_user_id()])) {
            $success = "Carte supprimée avec succès !";
        }
    }
}

// Définir comme carte principale
if (isset($_GET['set_primary'])) {
    $id = (int)$_GET['set_primary'];
    
    // Retirer le statut principal de toutes les cartes
    $stmt = $pdo->prepare("UPDATE cards SET is_primary = 0 WHERE user_id = ?");
    $stmt->execute([get_user_id()]);
    
    // Définir la nouvelle carte principale
    $stmt = $pdo->prepare("UPDATE cards SET is_primary = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, get_user_id()])) {
        $success = "Carte principale définie !";
    }
}

// Récupérer les cartes
$cards = get_user_cards(get_user_id(), $pdo);

// Calculer le solde réel de chaque carte
foreach ($cards as &$card) {
    // Revenus
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE card_id = ?");
    $stmt->execute([$card['id']]);
    $incomes = $stmt->fetch()['total'];
    
    // Dépenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE card_id = ?");
    $stmt->execute([$card['id']]);
    $expenses = $stmt->fetch()['total'];
    
    $card['real_balance'] = $incomes - $expenses;
    
    // Mettre à jour le solde dans la BDD
    $stmt = $pdo->prepare("UPDATE cards SET balance = ? WHERE id = ?");
    $stmt->execute([$card['real_balance'], $card['id']]);
}

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-credit-card"></i> Gestion des Cartes Bancaires</h2>
    
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

    <form method="POST" style="background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i> Ajouter une Carte</h3>
        
        <div class="form-group">
            <label>Nom de la carte</label>
            <input type="text" name="card_name" placeholder="Ex: Carte Salaire" required>
        </div>
        
        <div class="form-group">
            <label>Nom de la banque</label>
            <input type="text" name="bank_name" placeholder="Ex: Banque Populaire" required>
        </div>
        
        <button type="submit" name="add" class="btn btn-success">
            <i class="fas fa-save"></i> Ajouter
        </button>
    </form>

    <!-- Liste des cartes -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php foreach ($cards as $card): ?>
            <div style="background: linear-gradient(135deg, <?= $card['is_primary'] ? '#6366f1' : '#64748b' ?> 0%, <?= $card['is_primary'] ? '#8b5cf6' : '#475569' ?> 100%); padding: 1.5rem; border-radius: 1rem; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative;">
                
                <?php if ($card['is_primary']): ?>
                    <div style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.3); padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">
                        <i class="fas fa-star"></i> PRINCIPALE
                    </div>
                <?php endif; ?>
                
                <div style="margin-bottom: 2rem;">
                    <div style="font-size: 0.9rem; opacity: 0.9;">
                        <i class="fas fa-university"></i> <?= htmlspecialchars($card['bank_name']) ?>
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 600; margin-top: 0.5rem;">
                        <?= htmlspecialchars($card['card_name']) ?>
                    </div>
                </div>
                
                <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <div style="font-size: 0.8rem; opacity: 0.9; margin-bottom: 0.25rem;">Solde actuel</div>
                    <div style="font-size: 1.75rem; font-weight: 700;">
                        <?= number_format($card['real_balance'], 2) ?> DH
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if (!$card['is_primary']): ?>
                        <a href="?set_primary=<?= $card['id'] ?>" class="btn btn-small" style="background: rgba(255,255,255,0.3); color: white; flex: 1;">
                            <i class="fas fa-star"></i> Définir principale
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!$card['is_primary']): ?>
                        <a href="?delete=<?= $card['id'] ?>" class="btn btn-danger btn-small" style="flex: 1;" onclick="return confirm('Supprimer cette carte ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($cards)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fas fa-credit-card" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>Aucune carte ajoutée</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>