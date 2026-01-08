<?php
// admin/orders/index.php - Vue administrateur
session_start();
require_once __DIR__ . '/../../config/db.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_roles']) || !in_array('ROLE_ADMIN', $_SESSION['user_roles'])) {
    header('Location: /403.php');
    exit;
}

$page_title = 'Gestion des Commandes - Admin';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getConnection();

// Récupérer toutes les commandes avec infos utilisateur
$stmt = $db->query("
    SELECT o.*, u.email as user_email, 
           COUNT(oi.id) as items_count,
           SUM(oi.quantity) as total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

// Statistiques
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total) as total_revenue,
        AVG(total) as avg_order_value
    FROM orders
");
$stats = $statsStmt->fetch();
?>

<div class="container">
    <h1 class="text-center mb-1">Gestion des Commandes</h1>
    <p class="text-center" style="color: #666; margin-bottom: 2rem;">
        Panel d'administration
    </p>
    
    <!-- Statistiques -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
          gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
             box-shadow: var(--box-shadow); text-align: center;">
            <div style="font-size: 2rem; color: var(--primary-color); font-weight: bold;">
                <?php echo $stats['total_orders']; ?>
            </div>
            <div style="color: #666;">Commandes totales</div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
             box-shadow: var(--box-shadow); text-align: center;">
            <div style="font-size: 2rem; color: var(--accent-color); font-weight: bold;">
                <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', ' '); ?> €
            </div>
            <div style="color: #666;">Chiffre d'affaires</div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius); 
             box-shadow: var(--box-shadow); text-align: center;">
            <div style="font-size: 2rem; color: var(--primary-color); font-weight: bold;">
                <?php echo number_format($stats['avg_order_value'] ?? 0, 2, ',', ' '); ?> €
            </div>
            <div style="color: #666;">Panier moyen</div>
        </div>
    </div>
    
    <!-- Tableau des commandes -->
    <div style="background: white; border-radius: var(--border-radius); overflow: hidden; 
         box-shadow: var(--box-shadow);">
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">ID</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Client</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Date</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Total</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Statut</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Articles</th>
                    <th style="padding: 1rem; background: var(--light-bg); text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 1rem;">#<?php echo $order['id']; ?></td>
                    <td style="padding: 1rem;">
                        <?php echo htmlspecialchars($order['user_email']); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                    </td>
                    <td style="padding: 1rem; font-weight: bold; color: var(--accent-color);">
                        <?php echo number_format($order['total'], 2, ',', ' '); ?> €
                    </td>
                    <td style="padding: 1rem;">
                        <form method="POST" action="/admin/orders/update_status.php" 
                              style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" style="padding: 0.3rem; border: 1px solid #ddd; 
                                   border-radius: 4px;" onchange="this.form.submit()">
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
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo $order['total_items']; ?> article(s)
                    </td>
                    <td style="padding: 1rem;">
                        <a href="/admin/orders/details.php?id=<?php echo $order['id']; ?>" 
                           class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                            Détails
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Navigation admin -->
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="/admin/products/" class="btn btn-secondary">
                Gestion Produits
            </a>
            <a href="/" class="btn">
                Retour au site
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
