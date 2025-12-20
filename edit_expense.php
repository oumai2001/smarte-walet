<?php
require_once 'config/database.php';
require_login(); // Protection de la page

$page_title = 'Modifier Revenu';

// Vérifier qu'on a l'ID
if (!isset($_GET['id'])) {
    header('Location: incomes.php');
    exit;
}

$id = (int)$_GET['id'];

// Récupérer le revenu (vérifier qu'il appartient à l'utilisateur)
$stmt = $pdo->prepare("SELECT * FROM incomes WHERE id = ? AND user_id = ?");
$stmt->execute([$id, get_user_id()]);
$income = $stmt->fetch();

if (!$income) {
    header('Location: incomes.php');
    exit;
}

// Récupérer les catégories et cartes de l'utilisateur
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'income' ORDER BY name")->fetchAll();
$cards = get_user_cards(get_user_id(), $pdo);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $description = clean_input($_POST['description']);
    $amount      = clean_input($_POST['amount']);
    $date        = clean_input($_POST['income_date']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $card_id     = !empty($_POST['card_id']) ? (int)$_POST['card_id'] : null;

    // Validation
    if (empty($description) || empty($amount) || empty($date) || empty($card_id)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être positif";
    } elseif (!validate_date($date)) {
        $error = "Date invalide";
    } else {
        // ⚡ Mise à jour du solde des cartes
        if ($income['card_id'] != $card_id || $income['amount'] != $amount) {
            // Retirer l'ancien montant de l'ancienne carte
            if ($income['card_id']) {
                update_card_balance($income['card_id'], $income['amount'], 'subtract', $pdo);
            }
            // Ajouter le nouveau montant à la nouvelle carte
            if ($card_id) {
                update_card_balance($card_id, $amount, 'add', $pdo);
            }
        }

        // Mise à jour du revenu
        $stmt = $pdo->prepare("
            UPDATE incomes
            SET description = ?, amount = ?, income_date = ?, category_id = ?, card_id = ?
            WHERE id = ? AND user_id = ?
        ");
        if ($stmt->execute([$description, $amount, $date, $category_id, $card_id, $id, get_user_id()])) {
            $success = "Revenu modifié avec succès !";
            // Mettre à jour $income pour refléter les nouvelles valeurs
            $income['description'] = $description;
            $income['amount'] = $amount;
            $income['income_date'] = $date;
            $income['category_id'] = $category_id;
            $income['card_id'] = $card_id;
        } else {
            $error = "Erreur lors de la modification";
        }
    }
}

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-edit"></i> Modifier le Revenu</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
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
            <select name="card_id" required>
                <option value="">-- Choisir une carte --</option>
                <?php foreach ($cards as $card): ?>
                    <option value="<?= $card['id'] ?>" <?= $income['card_id'] == $card['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($card['card_name']) ?> (<?= number_format($card['balance'], 2) ?> DH)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="update" class="btn btn-success">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <a href="incomes.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Annuler
        </a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
