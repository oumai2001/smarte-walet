<?php
require_once 'config/database.php';
require_login();

$page_title = 'Limites Mensuelles';

// Gestion de l'ajout/modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $category_id = clean_input($_POST['category_id']);
    $monthly_limit = clean_input($_POST['monthly_limit']);
    
    if (empty($category_id) || empty($monthly_limit)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($monthly_limit)) {
        $error = "Le montant doit être un nombre positif";
    } else {
        // Vérifier si une limite existe déjà
        $stmt = $pdo->prepare("SELECT id FROM category_limits WHERE user_id = ? AND category_id = ?");
        $stmt->execute([get_user_id(), $category_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE category_limits SET monthly_limit = ? WHERE user_id = ? AND category_id = ?");
            if ($stmt->execute([$monthly_limit, get_user_id(), $category_id])) {
                $success = "Limite mise à jour !";
            } else {
                $error = "Erreur lors de la mise à jour";
            }
        } else {
            // Insertion
            $stmt = $pdo->prepare("INSERT INTO category_limits (user_id, category_id, monthly_limit) VALUES (?, ?, ?)");
            if ($stmt->execute([get_user_id(), $category_id, $monthly_limit])) {
                $success = "Limite ajoutée !";
            } else {
                $error = "Erreur lors de l'ajout";
            }
        }
    }
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM category_limits WHERE user_id = ? AND category_id = ?");
    if ($stmt->execute([get_user_id(), $category_id])) {
        $success = "Limite supprimée !";
    }
}

// Récupérer les catégories de dépenses
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'expense' ORDER BY name")->fetchAll();

// Récupérer les limites actuelles avec les dépenses du mois
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.name,
        cl.monthly_limit,
        COALESCE(SUM(e.amount), 0) as spent_this_month
    FROM categories c
    LEFT JOIN category_limits cl ON c.id = cl.category_id AND cl.user_id = ?
    LEFT JOIN expenses e ON c.id = e.category_id 
        AND e.user_id = ? 
        AND MONTH(e.expense_date) = MONTH(CURDATE())
        AND YEAR(e.expense_date) = YEAR(CURDATE())
    WHERE c.type = 'expense'
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute([get_user_id(), get_user_id()]);
$limits_data = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-chart-line"></i> Limites Mensuelles par Catégorie</h2>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        Définissez une limite mensuelle pour chaque catégorie. Vous serez bloqué si vous dépassez cette limite.
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
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-plus-circle"></i> Définir/Modifier une Limite</h3>
        
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Limite mensuelle (DH)</label>
            <input type="number" step="0.01" name="monthly_limit" placeholder="Ex: 1500.00" required>
        </div>
        
        <button type="submit" name="save" class="btn btn-success">
            <i class="fas fa-save"></i> Enregistrer
        </button>
    </form>

    <!-- Tableau des limites -->
    <table>
        <thead>
            <tr>
                <th><i class="fas fa-tag"></i> Catégorie</th>
                <th><i class="fas fa-coins"></i> Limite Mensuelle</th>
                <th><i class="fas fa-wallet"></i> Dépensé ce mois</th>
                <th><i class="fas fa-chart-bar"></i> Progression</th>
                <th><i class="fas fa-cog"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($limits_data as $data): ?>
                <tr>
                    <td><?= htmlspecialchars($data['name']) ?></td>
                    <td>
                        <?php if ($data['monthly_limit']): ?>
                            <strong><?= number_format($data['monthly_limit'], 2) ?> DH</strong>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">Aucune limite</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="color: var(--danger); font-weight: 600;">
                            <?= number_format($data['spent_this_month'], 2) ?> DH
                        </span>
                    </td>
                    <td>
                        <?php if ($data['monthly_limit']): ?>
                            <?php 
                                $percentage = ($data['spent_this_month'] / $data['monthly_limit']) * 100;
                                $color = $percentage >= 100 ? 'var(--danger)' : ($percentage >= 80 ? 'var(--warning)' : 'var(--success)');
                            ?>
                            <div style="width: 100%; background: var(--bg-main); border-radius: 1rem; overflow: hidden; height: 25px; position: relative;">
                                <div style="width: <?= min($percentage, 100) ?>%; background: <?= $color ?>; height: 100%; transition: width 0.3s;"></div>
                                <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 0.75rem; font-weight: 600; color: var(--text-primary);">
                                    <?= number_format($percentage, 1) ?>%
                                </span>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($data['monthly_limit']): ?>
                            <a href="?delete=<?= $data['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Supprimer cette limite ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>