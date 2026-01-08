<?php
// checkout.php (racine)
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/login.php'; // Pour les fonctions d'auth

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier si le panier n'est pas vide
if (empty($_SESSION['cart'])) {
    header('Location: /cart/index.php?error=empty_cart');
    exit;
}

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getConnection();
    
    try {
        $db->beginTransaction();
        
        // 1. Calculer le total et vérifier les stocks
        $total = 0;
        $items = [];
        $errors = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Récupérer le produit depuis la DB
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND stock >= ?");
            $stmt->execute([$productId, $quantity]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $errors[] = "Produit #$productId non disponible en quantité suffisante";
                continue;
            }
            
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;
            
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['price'],
                'subtotal' => $subtotal,
                'product' => $product
            ];
        }
        
        // Si erreurs de stock, annuler
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }
        
        // 2. Créer la commande
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, total, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $db->lastInsertId();
        
        // 3. Ajouter les articles et mettre à jour les stocks
        foreach ($items as $item) {
            // Ajouter à order_items
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
            
            // Décrémenter le stock
            $stmt = $db->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // 4. Valider la transaction
        $db->commit();
        
        // 5. Envoyer l'email de confirmation
        sendOrderConfirmationEmail($_SESSION['user_email'], $orderId, $items, $total);
        
        // 6. Vider le panier et rediriger
        $_SESSION['cart'] = [];
        $_SESSION['last_order_id'] = $orderId;
        
        header('Location: /thank_you.php?id=' . $orderId);
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Fonction pour envoyer l'email
function sendOrderConfirmationEmail($email, $orderId, $items, $total) {
    $subject = "🌸 Confirmation de commande #$orderId - Coquettes Bouquets";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #ff9a9e; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .item { border-bottom: 1px solid #eee; padding: 10px 0; }
            .total { font-size: 18px; font-weight: bold; color: #ff6b8b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Coquettes Bouquets</h1>
                <h2>Merci pour votre commande !</h2>
            </div>
            <div class='content'>
                <p>Bonjour,</p>
                <p>Votre commande <strong>#$orderId</strong> a bien été enregistrée.</p>
                <h3>Récapitulatif :</h3>";
    
    foreach ($items as $item) {
        $message .= "
                <div class='item'>
                    <strong>{$item['product']['name']}</strong><br>
                    Quantité: {$item['quantity']} × {$item['price']} € = {$item['subtotal']} €
                </div>";
    }
    
    $message .= "
                <div class='total'>
                    Total: $total €
                </div>
                <p>Nous préparons votre commande avec amour 💖</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Configuration pour MailHog (localhost:1025)
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: no-reply@coquettes-bouquets.fr',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // En développement avec MailHog
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', '1025');
    
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

// Récupérer les infos utilisateur et les produits du panier
$db = Database::getConnection();

// Infos utilisateur
$stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Produits du panier
$cartItems = [];
$cartTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if ($product) {
        $subtotal = $product['price'] * $quantity;
        $cartTotal += $subtotal;
        
        $cartItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de commande - Coquettes Bouquets</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .checkout-page {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .order-summary {
            background: #fff0f6;
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 154, 158, 0.3);
        }
        
        .summary-total {
            font-size: 1.4rem;
            font-weight: bold;
            color: #ff6b8b;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 107, 139, 0.3);
        }
        
        .cart-item-checkout {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff9a9e, #ff6b8b);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.3s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffe6e6;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .user-info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="checkout-page">
        <h1>🌸 Validation de commande</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
                <br><br>
                <a href="/cart/index.php" class="btn">← Retour au panier</a>
            </div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div>
                <!-- Informations client -->
                <div class="user-info-card">
                    <h3>Informations de livraison</h3>
                    <p><strong>Client :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Date :</strong> <?php echo date('d/m/Y H:i'); ?></p>
                    <small>Un email de confirmation sera envoyé à cette adresse.</small>
                </div>
                
                <!-- Articles du panier -->
                <h3>Vos articles</h3>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item-checkout">
                        <img src="/assets/images/<?php echo htmlspecialchars($item['product']['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                             class="cart-item-image">
                        <div style="flex: 1;">
                            <h4><?php echo htmlspecialchars($item['product']['name']); ?></h4>
                            <p><?php echo htmlspecialchars($item['product']['description']); ?></p>
                            <p>Quantité : <?php echo $item['quantity']; ?></p>
                        </div>
                        <div style="font-weight: bold; color: #ff6b8b;">
                            <?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Formulaire de validation -->
                <form method="POST">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" required style="margin-top: 3px;">
                            <span>J'accepte les <a href="/terms.php" target="_blank">conditions générales de vente</a> *</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-checkout">
                         Confirmer et payer la commande
                    </button>
                </form>
            </div>
            
            <!-- Récapitulatif -->
            <div class="order-summary">
                <h3>Récapitulatif</h3>
                
                <div style="margin-bottom: 20px;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="summary-item">
                            <span><?php echo htmlspecialchars($item['product']['name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-item">
                    <span>Sous-total</span>
                    <span><?php echo number_format($cartTotal, 2, ',', ' '); ?> €</span>
                </div>
                
                <div class="summary-item">
                    <span>Livraison</span>
                    <span>
                        <?php if ($cartTotal > 200): ?>
                            <span style="color: #4CAF50;">GRATUITE</span>
                        <?php else: ?>
                            9,90 €
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php
                $shipping = ($cartTotal > 200) ? 0 : 9.90;
                $finalTotal = $cartTotal + $shipping;
                ?>
                
                <div class="summary-total">
                    <span>Total TTC</span>
                    <span><?php echo number_format($finalTotal, 2, ',', ' '); ?> €</span>
                </div>
                
                <?php if ($cartTotal > 200): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 15px; text-align: center;">
                         Livraison offerte !
                    </div>
                <?php else: ?>
                    <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-top: 15px; text-align: center;">
                        Ajoutez <?php echo number_format(200 - $cartTotal, 2, ',', ' '); ?> € pour la livraison gratuite
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <p><small>Paiement sécurisé SSL</small></p>
                    <p><small>Retour sous 14 jours</small></p>
                    <p><small>Livraison 24-48h</small></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!document.querySelector('input[type="checkbox"]').checked) {
                e.preventDefault();
                alert('Veuillez accepter les conditions générales de vente');
                return false;
            }
            
            return confirm('Confirmer définitivement votre commande ?');
        });
    </script>
</body>
</html>
