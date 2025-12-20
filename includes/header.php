<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Smarte Walet</title>
    <meta name="description" content="Système de gestion financière professionnel pour suivre vos revenus et dépenses">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <div class="header-container">
            <!-- Logo et Nom -->
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="brand">
                    <h1>Smarte Walet</h1>
                    <p class="tagline">Gestion Intelligente de vos Finances</p>
                </div>
            </div>
            
            <!-- Navigation Desktop -->
            <nav class="desktop-nav">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="incomes.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'incomes.php' || basename($_SERVER['PHP_SELF']) == 'edit_income.php' ? 'active' : '' ?>">
                    <i class="fas fa-arrow-up"></i>
                    <span>Revenus</span>
                </a>
                <a href="expenses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php' ? 'active' : '' ?>">
                    <i class="fas fa-arrow-down"></i>
                    <span>Dépenses</span>
                </a>
                <a href="cards.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cards.php' ? 'active' : '' ?>">
    <i class="fas fa-credit-card"></i>
    <span>Mes Cartes</span>
</a>
<a href="transfers.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transfers.php' || basename($_SERVER['PHP_SELF']) == 'transfer_money.php' ? 'active' : '' ?>">
    <i class="fas fa-exchange-alt"></i>
    <span>Transferts</span>
</a>
            </nav>
            
            <!-- Actions utilisateur -->
            <div class="user-actions">
                <!-- ⚠️ NOUVEAU - Afficher nom utilisateur -->
                <?php if (is_logged_in()): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                        <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                        <span><?= get_user_name() ?></span>
                    </div>
                <?php endif; ?>
                
                <button class="icon-btn" id="themeToggle" title="Changer le thème">
                    <i class="fas fa-moon"></i>
                </button>
                
                <!-- ⚠️ NOUVEAU - Bouton déconnexion -->
                <?php if (is_logged_in()): ?>
                    <a href="logout.php" class="icon-btn" title="Déconnexion" style="text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php endif; ?>
                
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Navigation Mobile -->
    <nav class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <h3>Menu</h3>
            <button class="close-mobile-nav" id="closeMobileNav">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-nav-links">
            <!-- ⚠️ NOUVEAU - Nom utilisateur en mobile -->
            <?php if (is_logged_in()): ?>
                <div style="padding: 1rem; background: var(--bg-main); border-radius: var(--radius); margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);"><?= get_user_name() ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">Utilisateur</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="incomes.php" class="<?= basename($_SERVER['PHP_SELF']) == 'incomes.php' || basename($_SERVER['PHP_SELF']) == 'edit_income.php' ? 'active' : '' ?>">
                <i class="fas fa-arrow-up"></i> Revenus
            </a>
            <a href="expenses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php' ? 'active' : '' ?>">
                <i class="fas fa-arrow-down"></i> Dépenses
            </a>
            <a href="cards.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cards.php' ? 'active' : '' ?>">
    <i class="fas fa-credit-card"></i> Mes Cartes
</a>
<a href="transfers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'transfers.php' ? 'active' : '' ?>">
    <i class="fas fa-exchange-alt"></i> Transferts
</a>
            
            <!-- ⚠️ NOUVEAU - Déconnexion en mobile -->
            <?php if (is_logged_in()): ?>
                <a href="logout.php" style="border-top: 1px solid var(--border); margin-top: 1rem; padding-top: 1rem; color: var(--danger);">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Overlay pour mobile -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <!-- Contenu Principal -->
    <main class="main-content">
        <div class="content-wrapper">