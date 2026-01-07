<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du produit depuis l'URL
$product_id = $_GET['id'] ?? 0;

// Récupérer les informations du produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le produit n'existe pas, rediriger vers le catalogue
if (!$product) {
    header('Location: catalogue.php');
    exit();
}

// Récupérer les produits similaires (même couleur ou saison)
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE id != ? 
    AND (color = ? OR season = ?)
    ORDER BY RAND()
    LIMIT 4
");
$stmt->execute([$product_id, $product['color'], $product['season']]);
$similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion de l'ajout au panier
if (isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && $quantity <= 10) {
        // Initialiser le panier s'il n'existe pas
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Vérifier si le produit est déjà dans le panier
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // Ajouter le produit s'il n'est pas déjà dans le panier
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image_url']
            ];
        }
        
        $_SESSION['success_message'] = "Le bouquet a été ajouté à votre panier !";
        header('Location: panier.php');
        exit();
    } else {
        $error_message = "Veuillez choisir une quantité entre 1 et 10.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Coquette Bouquet</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Style coquette pour la page produit */
        :root {
            --rose-pastel: #ffd6e7;
            --lavande: #e6e6fa;
            --pale-mint: #c7f0d2;
            --blush: #f9c5d1;
            --vintage-rose: #b87c7c;
            --beige-creme: #f9f3e9;
        }
        
        body {
            background-color: var(--beige-creme);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #555;
        }
        
        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb a {
            color: var(--vintage-rose);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(255, 214, 231, 0.2);
            margin-bottom: 3rem;
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
        }
        
        .product-images {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
            border: 3px solid var(--rose-pastel);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--vintage-rose);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .product-info h1 {
            color: var(--vintage-rose);
            font-size: 2.2rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .product-code {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .product-price {
            font-size: 2.5rem;
            color: var(--vintage-rose);
            font-weight: bold;
            margin: 1.5rem 0;
        }
        
        .product-description {
            line-height: 1.6;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .product-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            background: var(--pale-mint);
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
            color: #5a7c6a;
        }
        
        .meta-item.color {
            background: var(--lavande);
            color: #6a6a8a;
        }
        
        .meta-item.season {
            background: var(--rose-pastel);
            color: var(--vintage-rose);
        }
        
        .quantity-selector {
            margin-bottom: 2rem;
        }
        
        .quantity-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--vintage-rose);
            font-weight: 600;
        }
        
        .quantity-input {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--blush);
            background: white;
            color: var(--vintage-rose);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .qty-btn:hover {
            background: var(--blush);
            color: white;
        }
        
        #quantity {
            width: 60px;
            text-align: center;
            padding: 0.5rem;
            border: 2px solid var(--blush);
            border-radius: 10px;
            font-size: 1.1rem;
            color: var(--vintage-rose);
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, var(--rose-pastel), var(--blush));
            color: var(--vintage-rose);
            border: none;
            padding: 1.2rem 2.5rem;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .add-to-cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 214, 231, 0.4);
        }
        
        .back-to-catalogue {
            display: inline-block;
            text-align: center;
            width: 100%;
            color: var(--vintage-rose);
            text-decoration: none;
            padding: 0.8rem;
            border: 2px solid var(--rose-pastel);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .back-to-catalogue:hover {
            background: var(--rose-pastel);
        }
        
        .similar-products {
            margin-top: 4rem;
        }
        
        .similar-title {
            text-align: center;
            color: var(--vintage-rose);
            font-size: 1.8rem;
            margin-bottom: 2rem;
        }
        
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .similar-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(255, 214, 231, 0.3);
            transition: transform 0.3s;
            border: 1px solid var(--rose-pastel);
        }
        
        .similar-card:hover {
            transform: translateY(-5px);
        }
        
        .similar-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .similar-info {
            padding: 1.5rem;
        }
        
        .similar-name {
            color: var(--vintage-rose);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .similar-price {
            color: var(--vintage-rose);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .view-product {
            display: block;
            text-align: center;
            background: var(--rose-pastel);
            color: var(--vintage-rose);
            padding: 0.5rem;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 1rem;
            transition: background 0.3s;
        }
        
        .view-product:hover {
            background: var(--blush);
            color: white;
        }
        
        .stock-info {
            color: #5a7c6a;
            background: var(--pale-mint);
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .stock-info.in-stock {
            background: var(--pale-mint);
            color: #2e7d32;
        }
        
        .stock-info.low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="product-container">
        <!-- Fil d'Ariane -->
        <div class="breadcrumb">
            <a href="catalogue.php">Catalogue</a> &gt; 
            <span><?= htmlspecialchars($product['name']) ?></span>
        </div>
        
        <!-- Section principale du produit -->
        <div class="product-detail">
            <div class="product-images">
                <img src="uploads/bouquets/<?= htmlspecialchars($product['image_url']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="main-image">
                <?php if ($product['stock'] < 5 && $product['stock'] > 0): ?>
                    <div class="product-badge">💖 Bientôt épuisé !</div>
                <?php elseif ($product['stock'] == 0): ?>
                    <div class="product-badge">⏸️ Temporairement indisponible</div>
                <?php else: ?>
                    <div class="product-badge">✨ Nouveau</div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="product-code">Code: <?= htmlspecialchars($product['code']) ?></div>
                
                <div class="product-price"><?= number_format($product['price'], 2) ?> €</div>
                
                <!-- Info stock -->
                <?php if ($product['stock'] > 10): ?>
                    <div class="stock-info in-stock">✅ Disponible (Plus de 10 en stock)</div>
                <?php elseif ($product['stock'] > 0 && $product['stock'] <= 10): ?>
                    <div class="stock-info low-stock">⚠️ Seulement <?= $product['stock'] ?> disponible(s)</div>
                <?php else: ?>
                    <div class="stock-info">⏸️ Ce produit est actuellement épuisé</div>
                <?php endif; ?>
                
                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                
                <div class="product-meta">
                    <span class="meta-item color">🎨 <?= htmlspecialchars($product['color']) ?></span>
                    <span class="meta-item season">🍂 <?= htmlspecialchars($product['season']) ?></span>
                    <span class="meta-item">💐 <?= htmlspecialchars($product['category']) ?></span>
                </div>
                
                <!-- Formulaire d'ajout au panier -->
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                
                <?php if ($product['stock'] > 0): ?>
                <form method="POST" action="">
                    <div class="quantity-selector">
                        <label class="quantity-label">Quantité :</label>
                        <div class="quantity-input">
                            <button type="button" class="qty-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" maxlength="2">
                            <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                        💐 Ajouter au panier
                    </button>
                </form>
                <?php else: ?>
                    <button class="add-to-cart-btn" disabled>
                        ⏸️ Produit indisponible
                    </button>
                <?php endif; ?>
                
                <a href="catalogue.php" class="back-to-catalogue">← Retour au catalogue</a>
            </div>
        </div>
        
        <!-- Produits similaires -->
        <?php if (!empty($similar_products)): ?>
        <div class="similar-products">
            <h2 class="similar-title">💖 Vous aimerez aussi...</h2>
            <div class="similar-grid">
                <?php foreach ($similar_products as $similar): ?>
                <div class="similar-card">
                    <img src="uploads/bouquets/<?= htmlspecialchars($similar['image_url']) ?>" 
                         alt="<?= htmlspecialchars($similar['name']) ?>" 
                         class="similar-image">
                    <div class="similar-info">
                        <h3 class="similar-name"><?= htmlspecialchars($similar['name']) ?></h3>
                        <div class="similar-price"><?= number_format($similar['price'], 2) ?> €</div>
                        <a href="produit.php?id=<?= $similar['id'] ?>" class="view-product">Voir ce bouquet</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        // Gestion de la quantité
        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value) + change;
            
            // Limites
            if (value < 1) value = 1;
            if (value > 10) value = 10;
            
            input.value = value;
        }
        
        // Validation de la quantité avant soumission
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const quantity = document.getElementById('quantity').value;
            if (quantity < 1 || quantity > 10) {
                e.preventDefault();
                alert('Veuillez choisir une quantité entre 1 et 10.');
            }
        });
        
        // Animation d'entrée pour les produits similaires
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.similar-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>