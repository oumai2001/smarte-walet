<?php
require_once 'config/database.php';
require_login(); // ⚠️ NOUVEAU - Protection de la page

$page_title = 'Modifier Dépense';

if (!isset($_GET['id'])) {
    header('Location: expenses.php');
    exit;
}

$id = (int)$_GET['id'];

// ⚠️ MODIFIÉ - Récupérer la dépense (vérifier qu'elle appartient à l'utilisateur)
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
$stmt->execute([$id, get_user_id()]);
$expense = $stmt->fetch();

if (!$expense) {
    header('Location: expenses.php');
    exit;
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = clean_input($_POST['description']);
    $amount = clean_input($_POST['amount']);
    $date = clean_input($_POST['expense_date']);
    $category_id = !empty($_POST['category_id']) ? clean_input($_POST['category_id']) : null;
    
    if (empty($description) || empty($amount) || empty($date)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être un nombre positif";
    } elseif (!validate_date($date)) {
        $error = "Date invalide";
    } else {
        // ⚠️ MODIFIÉ - Vérifier que c'est bien la dépense de l'utilisateur
        $stmt = $pdo->prepare("UPDATE expenses SET description = ?, amount = ?, expense_date = ?, category_id = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$description, $amount, $date, $category_id, $id, get_user_id()])) {
            header('Location: expenses.php');
            exit;
        } else {
            $error = "Erreur lors de la modification";
        }
    }
}

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'expense' ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-edit"></i> Modifier la Dépense</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius);">
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="<?= htmlspecialchars($expense['description']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Montant (DH)</label>
            <input type="number" step="0.01" name="amount" value="<?= $expense['amount'] ?>" required>
        </div>
        
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="expense_date" value="<?= $expense['expense_date'] ?>" required>
        </div>
        
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id">
                <option value="">-- Aucune --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $expense['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <a href="expenses.php" class="btn">
            <i class="fas fa-arrow-left"></i> Annuler
        </a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>