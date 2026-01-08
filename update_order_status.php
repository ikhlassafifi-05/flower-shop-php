<?php
// update_order_status.php
session_start();
require_once __DIR__ . '/config/db.php';

// Vérifier si admin
if (!isset($_SESSION['user_roles']) || !in_array('ROLE_ADMIN', $_SESSION['user_roles'])) {
    header('Location: /403.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $db = Database::getConnection();
    
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
}

// Rediriger vers la page précédente
$referer = $_SERVER['HTTP_REFERER'] ?? '/orders.php';
header('Location: ' . $referer);
exit;
