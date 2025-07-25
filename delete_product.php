<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

header('Content-Type: application/json');

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

// Check if product exists
$check_stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$check_stmt->bind_param("i", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Delete from cart first (due to foreign key constraints)
    $delete_cart = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
    $delete_cart->bind_param("i", $product_id);
    $delete_cart->execute();
    
    // Note: We're not deleting from order_items as that would affect order history
    // Instead, we'll keep the order history intact for business records
    
    // Delete the product
    $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_product->bind_param("i", $product_id);
    $delete_product->execute();
    
    if ($delete_product->affected_rows > 0) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Product "' . $product['name'] . '" deleted successfully'
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
}
?>
