<?php
require_once 'config/database.php';
require_login(); // ⚠️ NOUVEAU - Protection de la page

$page_title = 'Dashboard';

$user_id = get_user_id(); // ⚠️ NOUVEAU - Récupérer l'ID utilisateur

// ⚠️ MODIFIÉ - Récupérer le total des revenus (par utilisateur)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_incomes = $stmt->fetch()['total'];

// ⚠️ MODIFIÉ - Récupérer le total des dépenses (par utilisateur)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_expenses = $stmt->fetch()['total'];

// Calculer le solde
$balance = $total_incomes - $total_expenses;

// ⚠️ MODIFIÉ - Revenus du mois en cours (par utilisateur)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM incomes 
                     WHERE MONTH(income_date) = MONTH(CURRENT_DATE()) 
                     AND YEAR(income_date) = YEAR(CURRENT_DATE())
                     AND user_id = ?");
$stmt->execute([$user_id]);
$monthly_incomes = $stmt->fetch()['total'];

// ⚠️ MODIFIÉ - Dépenses du mois en cours (par utilisateur)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) 
                     AND YEAR(expense_date) = YEAR(CURRENT_DATE())
                     AND user_id = ?");
$stmt->execute([$user_id]);
$monthly_expenses = $stmt->fetch()['total'];

// ⚠️ MODIFIÉ - Dernières transactions (par utilisateur)
$stmt = $pdo->prepare("
    (SELECT 'income' as type, description, amount, income_date as date 
     FROM incomes 
     WHERE user_id = ?
     ORDER BY income_date DESC LIMIT 5)
    UNION ALL
    (SELECT 'expense' as type, description, amount, expense_date as date 
     FROM expenses 
     WHERE user_id = ?
     ORDER BY expense_date DESC LIMIT 5)
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$recent_transactions = $stmt->fetchAll();

// Inclure le header
include 'includes/header.php';
?>

<!-- Dashboard Cards -->
<div class="dashboard">
    <div class="card income">
        <h3><i class="fas fa-arrow-up"></i> Total Revenus</h3>
        <div class="amount"><?= number_format($total_incomes, 2) ?> DH</div>
    </div>
    
    <div class="card expense">
        <h3><i class="fas fa-arrow-down"></i> Total Dépenses</h3>
        <div class="amount"><?= number_format($total_expenses, 2) ?> DH</div>
    </div>
    
    <div class="card balance">
        <h3><i class="fas fa-wallet"></i> Solde Actuel</h3>
        <div class="amount"><?= number_format($balance, 2) ?> DH</div>
    </div>
</div>

<div class="dashboard">
    <div class="card income">
        <h3><i class="fas fa-calendar-alt"></i> Revenus ce mois</h3>
        <div class="amount"><?= number_format($monthly_incomes, 2) ?> DH</div>
    </div>
    
    <div class="card expense">
        <h3><i class="fas fa-calendar-alt"></i> Dépenses ce mois</h3>
        <div class="amount"><?= number_format($monthly_expenses, 2) ?> DH</div>
    </div>
    
    <div class="card balance">
        <h3><i class="fas fa-chart-pie"></i> Solde du mois</h3>
        <div class="amount"><?= number_format($monthly_incomes - $monthly_expenses, 2) ?> DH</div>
    </div>
</div>

<!-- Graphique -->
<div class="content">
    <h3><i class="fas fa-chart-bar"></i> Comparaison Revenus vs Dépenses</h3>
    <canvas id="comparisonChart"></canvas>
</div>

<!-- Dernières transactions -->
<div class="content">
    <h2><i class="fas fa-history"></i> Dernières Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_transactions as $trans): ?>
                <tr>
                    <td>
                        <?php if ($trans['type'] == 'income'): ?>
                            <span style="color: #10b981;">✓ Revenu</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">✗ Dépense</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trans['description']) ?></td>
                    <td style="font-weight: bold; color: <?= $trans['type'] == 'income' ? '#10b981' : '#ef4444' ?>">
                        <?= number_format($trans['amount'], 2) ?> DH
                    </td>
                    <td><?= date('d/m/Y', strtotime($trans['date'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ce mois', 'Total'],
            datasets: [{
                label: 'Revenus',
                data: [<?= $monthly_incomes ?>, <?= $total_incomes ?>],
                backgroundColor: '#10b981'
            }, {
                label: 'Dépenses',
                data: [<?= $monthly_expenses ?>, <?= $total_expenses ?>],
                backgroundColor: '#ef4444'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>