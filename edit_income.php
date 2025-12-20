<?php
require_once 'config/database.php';
require_login(); // ⚠️ NOUVEAU - Protection de la page

$page_title = 'Modifier Revenu';

if (!isset($_GET['id'])) {
    header('Location: incomes.php');
    exit;
}

$id = (int)$_GET['id'];

// ⚠️ MODIFIÉ - Récupérer le revenu (vérifier qu'il appartient à l'utilisateur)
$stmt = $pdo->prepare("SELECT * FROM incomes WHERE id = ? AND user_id = ?");
$stmt->execute([$id, get_user_id()]);
$income = $stmt->fetch();

if (!$income) {
    header('Location: incomes.php');
    exit;
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $card_id = !empty($_POST['card_id']) ? clean_input($_POST['card_id']) : null;
        $stmt = $pdo->prepare("UPDATE incomes SET description = ?, amount = ?, income_date = ?, category_id = ?, card_id = ? WHERE id = ? AND user_id = ?");
if ($stmt->execute([$description, $amount, $date, $category_id, $card_id, $id, get_user_id()])) {
            header('Location: incomes.php');
            exit;
        } else {
            $error = "Erreur lors de la modification";
        }
    }

}

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'income' ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-edit"></i> Modifier le Revenu</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius);">
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="<?= htmlspecialchars($income['description']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Montant (DH)</label>
            <input type="number" step="0.01" name="amount" value="<?= $income['amount'] ?>" required>
        </div>
        
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="income_date" value="<?= $income['income_date'] ?>" required>
        </div>
        
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id">
                <option value="">-- Aucune --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $income['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
    <label>Carte</label>
   <select name="card_id">
    <option value="">-- Aucune --</option>
    <?php 
    $cards = get_user_cards(get_user_id(), $pdo);
    foreach ($cards as $card): ?>
        <option value="<?= $card['id'] ?>" <?= $income['card_id'] == $card['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($card['card_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

</div>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <a href="incomes.php" class="btn">
            <i class="fas fa-arrow-left"></i> Annuler
        </a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>