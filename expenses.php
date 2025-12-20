<?php
require_once 'config/database.php';
require_login();

$page_title = 'Dépenses';

// Gestion de l'ajout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $description = clean_input($_POST['description']);
    $amount = clean_input($_POST['amount']);
    $date = clean_input($_POST['expense_date']);
    $category_id = !empty($_POST['category_id']) ? clean_input($_POST['category_id']) : null;
    $card_id = !empty($_POST['card_id']) ? clean_input($_POST['card_id']) : null;
    
    if (empty($description) || empty($amount) || empty($date)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être un nombre positif";
    } elseif (!validate_date($date)) {
        $error = "Date invalide";
    } elseif ($category_id && check_limit_exceeded(get_user_id(), $category_id, $amount, $pdo)) {
        // Vérifier la limite
        $limit = get_category_limit(get_user_id(), $category_id, $pdo);
        $spent = get_monthly_spent(get_user_id(), $category_id, $pdo);
        $error = "Limite mensuelle dépassée ! Dépensé: " . number_format($spent, 2) . " DH / Limite: " . number_format($limit, 2) . " DH";
    } else {
        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, expense_date, category_id, card_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$description, $amount, $date, $category_id, $card_id, get_user_id()])) {
            // Mettre à jour le solde de la carte
            if ($card_id) {
                update_card_balance($card_id, $amount, 'subtract', $pdo);
            }
            $success = "Dépense ajoutée avec succès !";
        } else {
            $error = "Erreur lors de l'ajout";
        }
    }
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, get_user_id()])) {
        $success = "Dépense supprimée avec succès !";
    }
}

// Récupérer les dépenses par utilisateur
$sql = "SELECT e.*, c.name AS category_name, ca.card_name
        FROM expenses e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN cards ca ON e.card_id = ca.id AND ca.user_id = e.user_id
        WHERE e.user_id = ?
        ORDER BY e.expense_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([get_user_id()]);
$expenses = $stmt->fetchAll();

// Récupérer les catégories de dépenses
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'expense' ORDER BY name")->fetchAll();

// Récupérer les cartes de l'utilisateur
$cards = get_user_cards(get_user_id(), $pdo);

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-arrow-down"></i> Gestion des Dépenses</h2>
    
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
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i> Ajouter une Dépense</h3>
        
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" required>
        </div>
        
        <div class="form-group">
            <label>Montant (DH)</label>
            <input type="number" step="0.01" name="amount" required>
        </div>
        
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id">
                <option value="">-- Aucune --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Carte</label>
            <select name="card_id">
                <option value="">-- Aucune --</option>
                <?php foreach ($cards as $card): ?>
                    <option value="<?= $card['id'] ?>">
                        <?= htmlspecialchars($card['card_name']) ?> (<?= number_format($card['balance'], 2) ?> DH)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" name="add" class="btn btn-success">
            <i class="fas fa-save"></i> Ajouter
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th><i class="fas fa-file-alt"></i> Description</th>
                <th><i class="fas fa-tag"></i> Catégorie</th>
                <th><i class="fas fa-money-bill-wave"></i> Montant</th>
                <th><i class="fas fa-calendar"></i> Date</th>
                <th><i class="fas fa-credit-card"></i> Carte</th>
                <th><i class="fas fa-cog"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['description']) ?></td>
                    <td><?= $expense['category_name'] ? htmlspecialchars($expense['category_name']) : '-' ?></td>
                    <td style="color: var(--danger); font-weight: bold;">
                        <?= number_format($expense['amount'], 2) ?> DH
                    </td>
                    <td><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
                    <td><?= $expense['card_name'] ? htmlspecialchars($expense['card_name']) : '-' ?></td>
                    <td>
                        <a href="edit_expense.php?id=<?= $expense['id'] ?>" class="btn btn-small">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="?delete=<?= $expense['id'] ?>" 
                           class="btn btn-danger btn-small" 
                           onclick="return confirm('Confirmer la suppression ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>