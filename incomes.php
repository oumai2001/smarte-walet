<?php
require_once 'config/database.php';
require_login(); // ⚠️ NOUVEAU - Protection de la page

$page_title = 'Revenus';

// Gestion de l'ajout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $description = clean_input($_POST['description']);
    $amount = clean_input($_POST['amount']);
    $date = clean_input($_POST['income_date']);
    $category_id = !empty($_POST['category_id']) ? clean_input($_POST['category_id']) : null;
    
    if (empty($description) || empty($amount) || empty($date)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être un nombre positif";
    } elseif (!validate_date($date)) {
        $error = "Date invalide";
    } else {
        // ⚠️ MODIFIÉ - Ajout du user_id
        $stmt = $pdo->prepare("INSERT INTO incomes (description, amount, income_date, category_id, user_id) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$description, $amount, $date, $category_id, get_user_id()])) {
            $success = "Revenu ajouté avec succès !";
        } else {
            $error = "Erreur lors de l'ajout";
        }
    }
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // ⚠️ MODIFIÉ - Vérifier que c'est bien le revenu de l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM incomes WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, get_user_id()])) {
        $success = "Revenu supprimé avec succès !";
    }
}

// Filtres
$where = "user_id = ?"; // ⚠️ NOUVEAU - Filtrer par utilisateur
$params = [get_user_id()];

if (!empty($_GET['category'])) {
    $where .= " AND category_id = ?";
    $params[] = $_GET['category'];
}

if (!empty($_GET['month'])) {
    $where .= " AND DATE_FORMAT(income_date, '%Y-%m') = ?";
    $params[] = $_GET['month'];
}

// ⚠️ MODIFIÉ - Récupérer les revenus avec filtres par utilisateur
$sql = "SELECT i.*, c.name as category_name FROM incomes i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE $where ORDER BY income_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incomes = $stmt->fetchAll();

// Récupérer les catégories de revenus
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'income' ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-arrow-up"></i> Gestion des Revenus</h2>
    
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
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i> Ajouter un Revenu</h3>
        
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
            <input type="date" name="income_date" value="<?= date('Y-m-d') ?>" required>
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
        
        <button type="submit" name="add" class="btn btn-success">
            <i class="fas fa-save"></i> Ajouter
        </button>
    </form>

    <!-- Filtres -->
    <form method="GET" class="filters">
        <select name="category" onchange="this.form.submit()">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="month" name="month" value="<?= $_GET['month'] ?? '' ?>" onchange="this.form.submit()">
        
        <?php if (!empty($_GET['category']) || !empty($_GET['month'])): ?>
            <a href="incomes.php" class="btn btn-small">
                <i class="fas fa-redo"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th><i class="fas fa-file-alt"></i> Description</th>
                <th><i class="fas fa-tag"></i> Catégorie</th>
                <th><i class="fas fa-money-bill-wave"></i> Montant</th>
                <th><i class="fas fa-calendar"></i> Date</th>
                <th><i class="fas fa-cog"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incomes as $income): ?>
                <tr>
                    <td><?= htmlspecialchars($income['description']) ?></td>
                    <td><?= $income['category_name'] ? htmlspecialchars($income['category_name']) : '-' ?></td>
                    <td style="color: var(--success); font-weight: bold;">
                        <?= number_format($income['amount'], 2) ?> DH
                    </td>
                    <td><?= date('d/m/Y', strtotime($income['income_date'])) ?></td>
                    <td>
                        <a href="edit_income.php?id=<?= $income['id'] ?>" class="btn btn-small">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="?delete=<?= $income['id'] ?>" 
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