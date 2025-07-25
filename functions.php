<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Format price
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

// Get cart count
function getCartCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    require_once 'db.php';
    global $conn;
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ? $row['total'] : 0;
}

// Add to cart
function addToCart($product_id, $quantity = 1) {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'db.php';
    global $conn;
    
    // Check if item already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $row['id']);
        return $update_stmt->execute();
    } else {
        // Add new item
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
        return $insert_stmt->execute();
    }
}

// Remove from cart
function removeFromCart($product_id) {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'db.php';
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    return $stmt->execute();
}

// Update cart quantity
function updateCartQuantity($product_id, $quantity) {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'db.php';
    global $conn;
    
    if ($quantity <= 0) {
        return removeFromCart($product_id);
    }
    
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $_SESSION['user_id'], $product_id);
    return $stmt->execute();
}

// Get cart items
function getCartItems() {
    if (!isLoggedIn()) {
        return [];
    }
    
    require_once 'db.php';
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Calculate cart total
function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

// Clear cart
function clearCart() {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'db.php';
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    return $stmt->execute();
}

// Create order
function createOrder() {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'db.php';
    global $conn;
    
    $total = getCartTotal();
    $cart_items = getCartItems();
    
    if (empty($cart_items)) {
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
        $stmt->bind_param("id", $_SESSION['user_id'], $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        // Clear cart
        clearCart();
        
        // Commit transaction
        $conn->commit();
        return $order_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Generate random string for session tokens
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>
