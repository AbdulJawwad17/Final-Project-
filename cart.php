<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require login to access cart
requireLogin();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($product_id > 0) {
            $success = addToCart($product_id, $quantity);
            $cart_count = getCartCount();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'cart_count' => $cart_count,
                'message' => $success ? 'Product added to cart' : 'Failed to add product to cart'
            ]);
            exit();
        }
    }
    
    if ($action == 'update') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($product_id > 0) {
            $success = updateCartQuantity($product_id, $quantity);
            $cart_total = getCartTotal();
            $cart_count = getCartCount();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'cart_total' => formatPrice($cart_total),
                'cart_count' => $cart_count
            ]);
            exit();
        }
    }
    
    if ($action == 'remove') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        
        if ($product_id > 0) {
            $success = removeFromCart($product_id);
            $cart_total = getCartTotal();
            $cart_count = getCartCount();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'cart_total' => formatPrice($cart_total),
                'cart_count' => $cart_count
            ]);
            exit();
        }
    }
}

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Computer Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><a href="index.php" style="color: white; text-decoration: none;">🖥️ Computer Store</a></h1>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php" style="opacity: 0.8;">Cart</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-info">
                <span>Welcome, <?php echo sanitize($_SESSION['name']); ?>!</span>
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                <h3>Your cart is empty</h3>
                <p>Start shopping to add items to your cart.</p>
                <a href="products.php" class="btn">Browse Products</a>
            </div>
        <?php else: ?>
            <div id="cart-container">
                <!-- Cart Items -->
                <div style="margin-bottom: 2rem;">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" id="cart-item-<?php echo $item['product_id']; ?>">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo sanitize($item['image']); ?>" 
                                     alt="<?php echo sanitize($item['name']); ?>" 
                                     class="cart-item-image">
                            <?php else: ?>
                                <div class="cart-item-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                    No Image
                                </div>
                            <?php endif; ?>
                            
                            <div class="cart-item-info">
                                <h4><?php echo sanitize($item['name']); ?></h4>
                                <div class="cart-item-price"><?php echo formatPrice($item['price']); ?> each</div>
                                
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>"
                                           min="1" 
                                           onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                                
                                <div style="margin-top: 0.5rem;">
                                    <strong>Subtotal: <?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                                </div>
                            </div>
                            
                            <div>
                                <button onclick="removeFromCart(<?php echo $item['product_id']; ?>)" class="btn btn-sm btn-danger">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Order Summary</h3>
                    <div style="display: flex; justify-content: space-between; margin: 1rem 0; padding: 1rem 0; border-bottom: 2px solid #667eea;">
                        <strong>Total: <span id="cart-total"><?php echo formatPrice($cart_total); ?></span></strong>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <a href="products.php" class="btn">Continue Shopping</a>
                        <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- JavaScript -->
    <script>
        function updateQuantity(productId, quantity) {
            if (quantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    removeFromCart(productId);
                }
                return;
            }
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update&product_id=' + productId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the quantity input
                    const quantityInput = document.querySelector(`#cart-item-${productId} .quantity-input`);
                    quantityInput.value = quantity;
                    
                    // Update cart total
                    document.getElementById('cart-total').textContent = data.cart_total;
                    
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Recalculate subtotal for this item
                    location.reload(); // Simple reload to update all calculations
                } else {
                    alert('Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }

        function removeFromCart(productId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item from the page
                    const cartItem = document.getElementById(`cart-item-${productId}`);
                    cartItem.remove();
                    
                    // Update cart total
                    document.getElementById('cart-total').textContent = data.cart_total;
                    
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        if (data.cart_count > 0) {
                            cartCount.textContent = data.cart_count;
                        } else {
                            cartCount.remove();
                        }
                    }
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        location.reload(); // Reload to show empty cart message
                    }
                } else {
                    alert('Error removing item from cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item from cart');
            });
        }
    </script>
</body>
</html>
