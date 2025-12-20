<?php
require_once 'config/database.php';
require_login();

$page_title = 'Transactions Récurrentes';

// Gestion de l'ajout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $type = clean_input($_POST['type']);
    $description = clean_input($_POST['description']);
    $amount = clean_input($_POST['amount']);
    $category_id = !empty($_POST['category_id']) ? clean_input($_POST['category_id']) : null;
    $card_id = !empty($_POST['card_id']) ? clean_input($_POST['card_id']) : null;
    $day_of_month = clean_input($_POST['day_of_month']);
    
    if (empty($description) || empty($amount) || empty($day_of_month)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être un nombre positif";
    } elseif ($day_of_month < 1 || $day_of_month > 31) {
        $error = "Le jour doit être entre 1 et 31";
    } else {
        $stmt = $pdo->prepare("INSERT INTO recurring_transactions (user_id, type, description, amount, category_id, card_id, day_of_month) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([get_user_id(), $type, $description, $amount, $category_id, $card_id, $day_of_month])) {
            $success = "Transaction récurrente ajoutée !";
        } else {
            $error = "Erreur lors de l'ajout";
        }
    }
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, get_user_id()])) {
        $success = "Transaction récurrente supprimée !";
    }
}

// Activer/Désactiver
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE recurring_transactions SET is_active = 1 - is_active WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, get_user_id()])) {
        $success = "Statut modifié !";
    }
}

// Récupérer les transactions récurrentes
$stmt = $pdo->prepare("
    SELECT r.*, c.name as category_name, ca.card_name
    FROM recurring_transactions r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN cards ca ON r.card_id = ca.id
    WHERE r.user_id = ?
    ORDER BY r.day_of_month, r.type
");
$stmt->execute([get_user_id()]);
$recurring = $stmt->fetchAll();

// Récupérer les catégories
$categories_income = $pdo->query("SELECT * FROM categories WHERE type = 'income' ORDER BY name")->fetchAll();
$categories_expense = $pdo->query("SELECT * FROM categories WHERE type = 'expense' ORDER BY name")->fetchAll();

// Récupérer les cartes
$cards = get_user_cards(get_user_id(), $pdo);

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-sync-alt"></i> Transactions Récurrentes</h2>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        Les transactions récurrentes sont générées automatiquement le jour spécifié de chaque mois.
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

    <form method="POST" style="background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i> Ajouter une Transaction Récurrente</h3>
        
        <div class="form-group">
            <label>Type</label>
            <select name="type" id="typeSelect" required onchange="updateCategories()">
                <option value="">-- Sélectionner --</option>
                <option value="income">Revenu</option>
                <option value="expense">Dépense</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" placeholder="Ex: Salaire, Loyer..." required>
        </div>
        
        <div class="form-group">
            <label>Montant (DH)</label>
            <input type="number" step="0.01" name="amount" required>
        </div>
        
        <div class="form-group">
            <label>Jour du mois (1-31)</label>
            <input type="number" name="day_of_month" min="1" max="31" value="1" required>
        </div>
        
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id" id="categorySelect">
                <option value="">-- Aucune --</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Carte</label>
            <select name="card_id">
                <option value="">-- Aucune --</option>
                <?php foreach ($cards as $card): ?>
                    <option value="<?= $card['id'] ?>">
                        <?= htmlspecialchars($card['card_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" name="add" class="btn btn-success">
            <i class="fas fa-save"></i> Ajouter
        </button>
    </form>

    <!-- Liste des transactions récurrentes -->
    <table>
        <thead>
            <tr>
                <th><i class="fas fa-calendar-day"></i> Jour</th>
                <th><i class="fas fa-tag"></i> Type</th>
                <th><i class="fas fa-file-alt"></i> Description</th>
                <th><i class="fas fa-money-bill-wave"></i> Montant</th>
                <th><i class="fas fa-list"></i> Catégorie</th>
                <th><i class="fas fa-credit-card"></i> Carte</th>
                <th><i class="fas fa-toggle-on"></i> Statut</th>
                <th><i class="fas fa-cog"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recurring as $trans): ?>
                <tr style="<?= $trans['is_active'] ? '' : 'opacity: 0.5;' ?>">
                    <td><strong><?= $trans['day_of_month'] ?></strong></td>
                    <td>
                        <?php if ($trans['type'] == 'income'): ?>
                            <span style="color: var(--success);"><i class="fas fa-arrow-up"></i> Revenu</span>
                        <?php else: ?>
                            <span style="color: var(--danger);"><i class="fas fa-arrow-down"></i> Dépense</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trans['description']) ?></td>
                    <td style="font-weight: bold; color: <?= $trans['type'] == 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                        <?= number_format($trans['amount'], 2) ?> DH
                    </td>
                    <td><?= $trans['category_name'] ? htmlspecialchars($trans['category_name']) : '-' ?></td>
                    <td><?= $trans['card_name'] ? htmlspecialchars($trans['card_name']) : '-' ?></td>
                    <td>
                        <a href="?toggle=<?= $trans['id'] ?>" class="btn btn-small" style="background: <?= $trans['is_active'] ? 'var(--success)' : 'var(--text-secondary)' ?>">
                            <i class="fas fa-<?= $trans['is_active'] ? 'check' : 'times' ?>"></i>
                            <?= $trans['is_active'] ? 'Active' : 'Inactive' ?>
                        </a>
                    </td>
                    <td>
                        <a href="?delete=<?= $trans['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Supprimer cette transaction récurrente ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($recurring)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fas fa-sync-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>Aucune transaction récurrente</p>
        </div>
    <?php endif; ?>
</div>

<script>
    const categoriesIncome = <?= json_encode($categories_income) ?>;
    const categoriesExpense = <?= json_encode($categories_expense) ?>;
    
    function updateCategories() {
        const type = document.getElementById('typeSelect').value;
        const categorySelect = document.getElementById('categorySelect');
        
        categorySelect.innerHTML = '<option value="">-- Aucune --</option>';
        
        const categories = type === 'income' ? categoriesIncome : categoriesExpense;
        
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            categorySelect.appendChild(option);
        });
    }
</script>

<?php include 'includes/footer.php'; ?>