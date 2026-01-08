<?php
// thank_you.php
session_start();
require_once __DIR__ . '/config/db.php';

$orderId = $_GET['id'] ?? $_SESSION['last_order_id'] ?? null;

if (!$orderId) {
    header('Location: /');
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Merci ! - Coquettes Bouquets</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .thank-you {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            padding: 40px;
        }
        
        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .order-number {
            background: #fff0f6;
            padding: 15px 30px;
            border-radius: 50px;
            display: inline-block;
            font-size: 1.2rem;
            color: #ff6b8b;
            margin: 20px 0;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="thank-you">
        <div class="success-icon">🌸</div>
        <h1>Merci pour votre commande !</h1>
        
        <div class="order-number">
            Commande #<?php echo $order['id']; ?>
        </div>
        
        <p>Votre commande a été enregistrée avec succès.</p>
        <p>Un email de confirmation a été envoyé à votre adresse.</p>
        
        <div style="background: #e6f7e9; padding: 20px; border-radius: 10px; margin: 30px 0;">
            <p><strong>Total :</strong> <?php echo number_format($order['total'], 2, ',', ' '); ?> €</p>
            <p><strong>Statut :</strong> <?php echo ucfirst($order['status']); ?></p>
        </div>
        
        <div class="actions">
            <a href="/" class="btn">← Retour à la boutique</a>
            <a href="/orders.php" class="btn btn-secondary">📋 Voir mes commandes</a>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
