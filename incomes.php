<?php
require_once 'config/database.php';
require_login();

$page_title = 'Revenus';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {

    $description  = clean_input($_POST['description']);
    $amount       = clean_input($_POST['amount']);
    $date         = clean_input($_POST['income_date']);
    $category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $card_id      = !empty($_POST['card_id']) ? (int)$_POST['card_id'] : null;

    if (empty($description) || empty($amount) || empty($date) || empty($card_id)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!validate_amount($amount)) {
        $error = "Le montant doit être positif";
    } elseif (!validate_date($date)) {
        $error = "Date invalide";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO incomes (description, amount, income_date, category_id, card_id, user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([
            $description,
            $amount,
            $date,
            $category_id,
            $card_id,
            get_user_id()
        ])) {

            // ➕ Ajouter le montant au solde de la carte
            update_card_balance($card_id, $amount, 'add', $pdo);

            $success = "Revenu ajouté avec succès !";
        } else {
            $error = "Erreur lors de l'ajout du revenu";
        }
    }
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Récupérer le revenu pour connaître la carte et le montant
    $stmt = $pdo->prepare("
        SELECT amount, card_id 
        FROM incomes 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, get_user_id()]);
    $income = $stmt->fetch();

    if ($income) {
        // Supprimer le revenu
        $stmt = $pdo->prepare("DELETE FROM incomes WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, get_user_id()])) {

            // ➖ Retirer le montant du solde de la carte
            if ($income['card_id']) {
                update_card_balance($income['card_id'], $income['amount'], 'subtract', $pdo);
            }

            $success = "Revenu supprimé avec succès !";
        }
    }
}

$sql = "
    SELECT i.*, 
           c.name AS category_name, 
           ca.card_name
    FROM incomes i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN cards ca ON i.card_id = ca.id
    WHERE i.user_id = ?
    ORDER BY i.income_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([get_user_id()]);
$incomes = $stmt->fetchAll();

$categories = $pdo
    ->query("SELECT * FROM categories WHERE type = 'income' ORDER BY name")
    ->fetchAll();

$cards = get_user_cards(get_user_id(), $pdo);

include 'includes/header.php';
?>

<div class="content">
    <h2><i class="fas fa-arrow-up"></i> Gestion des Revenus</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST" class="box">
        <h3>Ajouter un revenu</h3>

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
                    <option value="<?= $cat['id'] ?>">
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
                    <option value="<?= $card['id'] ?>">
                        <?= htmlspecialchars($card['card_name']) ?>
                        (<?= number_format($card['balance'], 2) ?> DH)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="add" class="btn btn-success">
            Ajouter
        </button>
    </form>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Catégorie</th>
                <th>Carte</th>
                <th>Montant</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incomes as $income): ?>
                <tr>
                    <td><?= htmlspecialchars($income['description']) ?></td>
                    <td><?= $income['category_name'] ?? '-' ?></td>
                    <td><?= $income['card_name'] ?? '-' ?></td>
                    <td style="color: green; font-weight: bold;">
                        <?= number_format($income['amount'], 2) ?> DH
                    </td>
                    <td><?= date('d/m/Y', strtotime($income['income_date'])) ?></td>
                    <td>
                          <a href="edit_income.php?id=<?= $income['id'] ?>" class="btn btn-small">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="?delete=<?= $income['id'] ?>"
                           onclick="return confirm('Supprimer ce revenu ?')"
                           class="btn btn-danger btn-small">
                            Supprimer
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
