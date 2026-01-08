<?php
// orders.php (racine) - Vue utilisateur
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$page_title = 'Mes Commandes - Coquettes Bouquets';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Récupérer les commandes de l'utilisateur
$stmt = $db->prepare("
    SELECT o.*, COUNT(oi.id) as items_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="text-center mb-3">Mes Commandes</h1>
    
    <?php if (empty($orders)): ?>
        <div class="alert" style="text-align: center; padding: 3rem;">
            <p>Vous n'avez pas encore passé de commande.</p>
            <a href="/products/index.php" class="btn btn-primary">Découvrir nos produits</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card" style="background: white; border-radius: var(--border-radius); 
                      padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--box-shadow);">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                Commande #<?php echo $order['id']; ?>
                            </h3>
                            <p style="color: #666; font-size: 0.9rem;">
                                Date : <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="background: <?php 
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
                                };
                            ?>;
                            padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; 
                            font-weight: 600; display: inline-block;">
                                <?php 
                                $statusLabels = [
                                    'PENDING' => 'En attente',
                                    'CONFIRMED' => 'Confirmée',
                                    'SHIPPED' => 'Expédiée'
                                ];
                                echo $statusLabels[$order['status']] ?? $order['status'];
                                ?>
                            </div>
                            
                            <p style="font-size: 1.2rem; font-weight: bold; color: var(--accent-color); 
                                   margin-top: 0.5rem;">
                                <?php echo number_format($order['total'], 2, ',', ' '); ?> €
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <p><strong><?php echo $order['items_count']; ?></strong> article(s) dans cette commande</p>
                        
                        <a href="/order_details.php?id=<?php echo $order['id']; ?>" 
                           class="btn" style="background: var(--accent-color); color: white; margin-top: 0.5rem;">
                            Voir les détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 3rem;">
        <a href="/" class="btn btn-secondary">← Retour à l'accueil</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
