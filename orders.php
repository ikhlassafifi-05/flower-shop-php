<?php
// orders.php - Fonctionne pour admin et users
session_start();
require_once __DIR__ . '/config/db.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Déterminer si admin
$is_admin = isset($_SESSION['user_roles']) && in_array('ROLE_ADMIN', $_SESSION['user_roles']);

$page_title = $is_admin ? 'Gestion des Commandes' : 'Mes Commandes';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Récupérer les commandes selon le rôle
if ($is_admin) {
    // Admin : toutes les commandes
    $stmt = $db->query("
        SELECT o.*, u.email as user_email, 
               COUNT(oi.id) as items_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
} else {
    // User : seulement ses commandes
    $stmt = $db->prepare("
        SELECT o.*, COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$orders = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="text-center mb-3">
        <?php echo $is_admin ? '📋 Gestion des Commandes' : '🌸 Mes Commandes'; ?>
    </h1>
    
    <?php if ($is_admin): ?>
        <!-- Statistiques admin seulement -->
        <?php
        $statsStmt = $db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total) as total_revenue
            FROM orders
        ");
        $stats = $statsStmt->fetch();
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
              gap: 1rem; margin-bottom: 2rem;">
            <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
                 box-shadow: var(--box-shadow); text-align: center;">
                <div style="font-size: 1.8rem; color: var(--primary-color); font-weight: bold;">
                    <?php echo $stats['total_orders']; ?>
                </div>
                <div style="color: #666;">Commandes</div>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
                 box-shadow: var(--box-shadow); text-align: center;">
                <div style="font-size: 1.8rem; color: var(--accent-color); font-weight: bold;">
                    <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', ' '); ?> €
                </div>
                <div style="color: #666;">Chiffre d'affaires</div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Liste des commandes -->
    <?php if (empty($orders)): ?>
        <div class="alert" style="text-align: center; padding: 3rem;">
            <p><?php echo $is_admin ? 'Aucune commande pour le moment.' : 'Vous n\'avez pas encore passé de commande.'; ?></p>
            <a href="/products/index.php" class="btn btn-primary">Voir les produits</a>
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
                            
                            <?php if ($is_admin): ?>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.3rem;">
                                    Client : <?php echo htmlspecialchars($order['user_email']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p style="color: #666; font-size: 0.9rem;">
                                Date : <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <!-- Statut -->
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
                            font-weight: 600; display: inline-block; margin-bottom: 0.5rem;">
                                <?php 
                                $statusLabels = [
                                    'PENDING' => '⏳ En attente',
                                    'CONFIRMED' => '✅ Confirmée',
                                    'SHIPPED' => '🚚 Expédiée'
                                ];
                                echo $statusLabels[$order['status']] ?? $order['status'];
                                ?>
                            </div>
                            
                            <p style="font-size: 1.2rem; font-weight: bold; color: var(--accent-color);">
                                <?php echo number_format($order['total'], 2, ',', ' '); ?> €
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="margin: 0;"><strong><?php echo $order['items_count']; ?></strong> article(s)</p>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                   class="btn" style="background: var(--accent-color); color: white; padding: 0.5rem 1rem;">
                                    Détails
                                </a>
                                
                                <?php if ($is_admin): ?>
                                    <!-- Menu déroulant pour changer le statut -->
                                    <form method="POST" action="update_order_status.php" 
                                          style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                                style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="PENDING" <?php echo $order['status'] == 'PENDING' ? 'selected' : ''; ?>>
                                                En attente
                                            </option>
                                            <option value="CONFIRMED" <?php echo $order['status'] == 'CONFIRMED' ? 'selected' : ''; ?>>
                                                Confirmée
                                            </option>
                                            <option value="SHIPPED" <?php echo $order['status'] == 'SHIPPED' ? 'selected' : ''; ?>>
                                                Expédiée
                                            </option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Navigation -->
    <div style="margin-top: 3rem; text-align: center;">
        <a href="/" class="btn btn-secondary">← Accueil</a>
        
        <?php if ($is_admin): ?>
            <a href="/admin/products/" class="btn" style="margin-left: 0.5rem;">Gérer les produits</a>
        <?php else: ?>
            <a href="/products/index.php" class="btn btn-primary" style="margin-left: 0.5rem;">Continuer mes achats</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
