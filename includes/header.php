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
            </nav>
            
            <!-- Actions utilisateur -->
            <div class="user-actions">
                <button class="icon-btn" id="themeToggle" title="Changer le thème">
                    <i class="fas fa-moon"></i>
                </button>
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
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="incomes.php" class="<?= basename($_SERVER['PHP_SELF']) == 'incomes.php' || basename($_SERVER['PHP_SELF']) == 'edit_income.php' ? 'active' : '' ?>">
                <i class="fas fa-arrow-up"></i> Revenus
            </a>
            <a href="expenses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php' ? 'active' : '' ?>">
                <i class="fas fa-arrow-down"></i> Dépenses
            </a>
        </div>
    </nav>
    
    <!-- Overlay pour mobile -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <!-- Contenu Principal -->
    <main class="main-content">
        <div class="content-wrapper">