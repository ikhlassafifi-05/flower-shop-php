<?php
// order_details.php - Détails d'une commande (pour admin et users)
session_start();
require_once __DIR__ . '/config/db.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: /orders.php');
    exit;
}

$orderId = (int)$_GET['id'];
$is_admin = isset($_SESSION['user_roles']) && in_array('ROLE_ADMIN', $_SESSION['user_roles']);
$db = Database::getConnection();

// Récupérer la commande
$stmt = $db->prepare("
    SELECT o.*, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: /orders.php');
    exit;
}

// Vérifier les permissions
if (!$is_admin && $order['user_id'] != $_SESSION['user_id']) {
    header('Location: /403.php');
    exit;
}

// Récupérer les articles
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.image_path, p.description
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$page_title = 'Commande #' . $orderId;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="text-center mb-3">Commande #<?php echo $order['id']; ?></h1>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 1rem;">
        <!-- Gauche : Détails et articles -->
        <div>
            <!-- Info commande -->
            <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
                 box-shadow: var(--box-shadow); margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                    📄 Informations
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <p><strong>Client :</strong><br>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </p>
                        <p><strong>Date :</strong><br>
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <p><strong>Statut :</strong><br>
                            <span style="padding: 0.3rem 1rem; border-radius: 20px; font-weight: 600;
                                  background: <?php 
                                    echo match($order['status']) {
                                        'PENDING' => '#fff3cd',
                                        'CONFIRMED' => '#d4edda',
                                        'SHIPPED' => '#cce5ff',
                                        default => '#f8f9fa'
                                    };
                                  ?>; 
                                  color: <?php 
                                    echo match($order['status']) {
                                        'PENDING' => '#856404',
                                        'CONFIRMED' => '#155724',
                                        'SHIPPED' => '#004085',
                                        default => '#6c757d'
                                  }; ?>;">
                                <?php echo $order['status']; ?>
                            </span>
                        </p>
                        
                        <?php if ($is_admin): ?>
                            <form method="POST" action="update_order_status.php" style="margin-top: 0.5rem;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" style="padding: 0.5rem; width: 100%;" 
                                        onchange="this.form.submit()">
                                    <option value="PENDING" <?php echo $order['status'] == 'PENDING' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="CONFIRMED" <?php echo $order['status'] == 'CONFIRMED' ? 'selected' : ''; ?>>Confirmée</option>
                                    <option value="SHIPPED" <?php echo $order['status'] == 'SHIPPED' ? 'selected' : ''; ?>>Expédiée</option>
                                </select>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Articles -->
            <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
                 box-shadow: var(--box-shadow);">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                    🛍️ Articles (<?php echo count($items); ?>)
                </h3>
                
                <?php foreach ($items as $item): ?>
                    <div style="display: flex; gap: 1rem; padding: 1rem; border-bottom: 1px solid #eee; 
                         align-items: center;">
                        <img src="/assets/images/<?php echo htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                        
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.3rem 0; color: var(--primary-color);">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h4>
                            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...
                            </p>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                                Quantité : <?php echo $item['quantity']; ?> × 
                                <?php echo number_format($item['price'], 2, ',', ' '); ?> €
                            </p>
                        </div>
                        
                        <div style="font-weight: bold; color: var(--accent-color); font-size: 1.1rem;">
                            <?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> €
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Droite : Récapitulatif et actions -->
        <div>
            <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
                 box-shadow: var(--box-shadow); position: sticky; top: 100px;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem;">💰 Récapitulatif</h3>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; 
                         border-bottom: 1px solid #eee;">
                        <span>Sous-total :</span>
                        <span><?php echo number_format($order['total'], 2, ',', ' '); ?> €</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; 
                         border-bottom: 1px solid #eee;">
                        <span>Livraison :</span>
                        <span>
                            <?php if ($order['total'] > 200): ?>
                                <span style="color: #4CAF50;">GRATUITE</span>
                            <?php else: ?>
                                9,90 €
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; 
                         font-weight: bold; font-size: 1.2rem; color: var(--primary-color);">
                        <span>Total :</span>
                        <span>
                            <?php 
                            $shipping = ($order['total'] > 200) ? 0 : 9.90;
                            echo number_format($order['total'] + $shipping, 2, ',', ' '); 
                            ?> €
                        </span>
                    </div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/orders.php" class="btn btn-secondary">
                        ← Retour aux commandes
                    </a>
                    
                    <?php if ($is_admin): ?>
                        <a href="/admin/products/" class="btn">
                            Gérer les produits
                        </a>
                    <?php else: ?>
                        <a href="/products/index.php" class="btn btn-primary">
                            Commander à nouveau
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
