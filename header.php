<?php
// includes/header.php
session_start();

// Configuration pour toutes les pages
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$page_title = $page_title ?? 'Coquettes Bouquets';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- STYLE UNIQUE POUR TOUTES LES PAGES -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container header-content">
            <a href="/" class="logo">🌸 Coquettes Bouquets</a>
            
            <nav class="main-nav">
                <a href="/" class="nav-link">Accueil</a>
                <a href="/products/index.php" class="nav-link">Catalogue</a>
                <a href="/cart/index.php" class="nav-link">
                    Panier
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span style="color: var(--primary-color);">
                        <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                    </span>
                    <a href="/auth/logout.php" class="nav-link">Déconnexion</a>
                <?php else: ?>
                    <a href="/auth/login.php" class="nav-link">Connexion</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <main class="container" style="min-height: 70vh; padding: 2rem 0;">
