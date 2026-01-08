<?php
// cart.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'models/Product.php';

if (!Auth::isLogged()) {
    header('Location: login.php');
    exit;
}

// Gestion du panier en session
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Supprimer un article
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        header('Location: cart.php?message=Produit retiré du panier');
        exit;
    }
}

// Modifier la quantité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    header('Location: cart.php?message=Quantité mise à jour');
    exit;
}

// Passer commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $db = Database::getConnection();
    
    try {
        $db->beginTransaction();
        
        // 1. Créer la commande
        $total = 0;
        $items = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = Product::getById($productId);
            if ($product && $product['stock'] >= $quantity) {
                $total += $product['price'] * $quantity;
                $items[] = ['product' => $product, 'quantity' => $quantity];
            }
        }
        
        if (empty($items)) {
            throw new Exception("Panier vide ou produits indisponibles");
        }
        
        $stmt = $db->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
        $stmt->execute([Auth::getUserId(), $total]);
        $orderId = $db->lastInsertId();
        
        // 2. Ajouter les articles et mettre à jour le stock
        foreach ($items as $item) {
            // Ajouter à order_items
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $orderId,
                $item['product']['id'],
                $item['quantity'],
                $item['product']['price']
            ]);
            
            // Mettre à jour le stock
            Product::updateStock($item['product']['id'], $item['quantity']);
        }
        
        $db->commit();
        $_SESSION['cart'] = []; // Vider le panier
        $success = "🎉 Commande #$orderId passée avec succès !";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "❌ Erreur lors de la commande : " . $e->getMessage();
    }
}

// Récupérer les produits du panier
$cartProducts = [];
$cartTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $product = Product::getById($productId);
    if ($product) {
        $product['quantity'] = $quantity;
        $product['subtotal'] = $product['price'] * $quantity;
        $cartProducts[] = $product;
        $cartTotal += $product['subtotal'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panier - Coquettes Bouquets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 🌸 STYLE COQUETTE & FÉMININ */
        :root {
            --rose-clair: #fff0f6;
            --rose-principal: #ff9a9e;
            --rose-fonce: #ff6b8b;
            --rose-pale: #ffebef;
            --vert-menthe: #b2f2bb;
            --blanc-creme: #fffaf0;
            --gris-clair: #f8f9fa;
            --gris-texte: #495057;
            --ombre-legere: 0 4px 15px rgba(255, 107, 139, 0.1);
            --ombre-portee: 0 8px 25px rgba(255, 107, 139, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--rose-clair) 0%, var(--blanc-creme) 100%);
            color: var(--gris-texte);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* 🌸 HEADER STYLÉ */
        .header-coquette {
            background: linear-gradient(135deg, var(--rose-principal) 0%, var(--rose-fonce) 100%);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--ombre-portee);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: white;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .logo-icon {
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        .user-info {
            color: white;
            font-weight: 500;
        }
        
        /* 🌸 MAIN CONTENT */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .page-header h2 {
            font-size: 2.2rem;
            color: var(--rose-fonce);
            font-weight: 300;
            margin-bottom: 0.5rem;
        }
        
        .page-header::after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--rose-principal), var(--rose-fonce));
            margin: 1rem auto;
            border-radius: 2px;
        }
        
        /* 🌸 MESSAGES */
        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            animation: slideIn 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--ombre-legere);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e6f7e9 0%, #d4f1d8 100%);
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ffe6e6 0%, #ffd4d4 100%);
            border-left: 4px solid #f44336;
            color: #c62828;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%);
            border-left: 4px solid #ff9800;
            color: #e65100;
        }
        
        /* 🌸 CART STYLES */
        .cart-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--ombre-portee);
            overflow: hidden;
            margin-bottom: 3rem;
        }
        
        .cart-empty {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--gris-texte);
        }
        
        .cart-empty-icon {
            font-size: 4rem;
            color: var(--rose-principal);
            margin-bottom: 1rem;
            opacity: 0.6;
        }
        
        .cart-empty h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--rose-fonce);
            font-weight: 300;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table thead {
            background: linear-gradient(135deg, var(--rose-pale) 0%, #ffebef 100%);
        }
        
        .cart-table th {
            padding: 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--rose-fonce);
            border-bottom: 2px solid var(--rose-pale);
        }
        
        .cart-table td {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: var(--ombre-legere);
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .product-details h4 {
            font-size: 1.1rem;
            color: var(--gris-texte);
            margin-bottom: 0.3rem;
        }
        
        .product-details p {
            color: var(--rose-principal);
            font-size: 0.9rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-input {
            width: 70px;
            padding: 0.5rem;
            border: 2px solid var(--rose-pale);
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
            color: var(--gris-texte);
            transition: border-color 0.3s;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: var(--rose-principal);
        }
        
        .btn-update {
            background: var(--rose-pale);
            color: var(--rose-fonce);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-update:hover {
            background: var(--rose-principal);
            color: white;
        }
        
        .btn-remove {
            background: transparent;
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-remove:hover {
            background: #ff6b6b;
            color: white;
        }
        
        .price-cell {
            font-weight: 600;
            color: var(--rose-fonce);
        }
        
        .subtotal-cell {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--rose-fonce);
        }
        
        /* 🌸 CART SUMMARY */
        .cart-summary {
            background: linear-gradient(135deg, var(--rose-pale) 0%, #ffebef 100%);
            padding: 2rem;
            border-radius: 0 0 20px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .summary-total {
            font-size: 1.4rem;
            color: var(--rose-fonce);
        }
        
        .summary-total span {
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--rose-principal) 0%, var(--rose-fonce) 100%);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--ombre-legere);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 107, 139, 0.3);
        }
        
        .btn-checkout:active {
            transform: translateY(-1px);
        }
        
        .btn-continue {
            background: white;
            color: var(--rose-fonce);
            border: 2px solid var(--rose-principal);
            padding: 0.8rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            margin-right: 1rem;
        }
        
        .btn-continue:hover {
            background: var(--rose-pale);
            transform: translateY(-2px);
        }
        
        /* 🌸 RESPONSIVE */
        @media (max-width: 768px) {
            .header-coquette {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .cart-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .cart-summary {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
            
            .btn-continue {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
        
        /* 🌸 FOOTER */
        .footer-coquette {
            background: var(--rose-pale);
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
            border-top: 1px solid rgba(255, 107, 139, 0.1);
        }
        
        .footer-coquette p {
            color: var(--rose-fonce);
            margin-bottom: 0.5rem;
        }
        
        /* 🌸 ANIMATIONS */
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- 🌸 HEADER -->
    <header class="header-coquette">
        <a href="index.php" class="logo">
            <span class="logo-icon">🌸</span>
            <h1>Coquettes Bouquets</h1>
        </a>
        
        <div class="nav-links">
            <span class="user-info">
                Bonjour, <?php echo htmlspecialchars(Auth::getUser()['email']); ?>
            </span>
            <a href="index.php" class="nav-link">
                <span>🏠</span> Boutique
            </a>
            <a href="cart.php" class="nav-link active">
                <span>🛒</span> Panier
                <?php if (!empty($cartProducts)): ?>
                    <span class="pulse">(<?php echo count($cartProducts); ?>)</span>
                <?php endif; ?>
            </a>
            <?php if (Auth::isAdmin()): ?>
                <a href="admin/" class="nav-link">
                    <span>👑</span> Admin
                </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-link">
                <span>🚪</span> Déconnexion
            </a>
        </div>
    </header>

    <!-- 🌸 MAIN CONTENT -->
    <main class="container fade-in">
        <div class="page-header">
            <h2>Votre Panier d'Émotions</h2>
            <p>Révisez vos sélections avant de passer commande</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <span>✅</span> <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <span>🎉</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span>❌</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Panier -->
        <div class="cart-container">
            <?php if (empty($cartProducts)): ?>
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <h3>Votre panier est vide</h3>
                    <p>Ajoutez des bouquets pour illuminer votre journée !</p>
                    <a href="index.php" class="btn-continue" style="margin-top: 1rem;">
                        Découvrir nos bouquets 🌸
                    </a>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Sous-total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartProducts as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="assets/images/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="cart-item-image">
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="price-cell">
                                    <?php echo number_format($product['price'], 2, ',', ' '); ?> €
                                </td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <div class="quantity-control">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="<?php echo $product['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $product['stock']; ?>"
                                                   class="quantity-input">
                                            <button type="submit" name="update_quantity" class="btn-update">
                                                Mettre à jour
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="subtotal-cell">
                                    <?php echo number_format($product['subtotal'], 2, ',', ' '); ?> €
                                </td>
                                <td>
                                    <a href="?remove=<?php echo $product['id']; ?>" 
                                       class="btn-remove"
                                       onclick="return confirm('Retirer ce produit du panier ?')">
                                        Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-summary">
                    <div class="summary-total">
                        Total panier : <span><?php echo number_format($cartTotal, 2, ',', ' '); ?> €</span>
                    </div>
                    <div>
                        <a href="index.php" class="btn-continue">
                            Continuer mes achats
                        </a>
                        <form method="POST" action="" style="display: inline;">
                            <button type="submit" 
                                    name="checkout" 
                                    class="btn-checkout"
                                    onclick="return confirm('Confirmer votre commande ?')">
                                <span>💝</span>
                                Finaliser ma commande
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </main>

    <!-- 🌸 FOOTER -->
    <footer class="footer-coquette">
        <p>🌸 Coquettes Bouquets - Fleurs qui font vibrer le cœur 🌸</p>
        <p>© <?php echo date('Y'); ?> - Tous droits réservés</p>
        <p style="font-size: 0.9rem; margin-top: 1rem; color: var(--rose-principal);">
            Livraison en 24h • Emballages éco-responsables • Fraîcheur garantie
        </p>
    </footer>
</body>
</html>
