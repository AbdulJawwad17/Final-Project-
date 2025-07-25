<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require login to access checkout
requireLogin();

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$success = '';
$error = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = createOrder();
    
    if ($order_id) {
        $_SESSION['last_order_id'] = $order_id;
        header('Location: checkout.php?success=1');
        exit();
    } else {
        $error = 'Failed to process your order. Please try again.';
    }
}

// Check for success message
if (isset($_GET['success']) && isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
    unset($_SESSION['last_order_id']);
    
    // Get order details
    $order_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $order_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order = $order_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Computer Store</title>
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
                    <li><a href="cart.php">Cart</a></li>
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
        <?php if (isset($order)): ?>
            <!-- Order Success -->
            <div class="alert alert-success">
                <h2>🎉 Order Placed Successfully!</h2>
                <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_price']); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
                <p>Thank you for your purchase! We will process your order shortly.</p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="products.php" class="btn">Continue Shopping</a>
                <a href="index.php" class="btn btn-success">Back to Home</a>
            </div>
        <?php else: ?>
            <!-- Checkout Form -->
            <h1>Checkout</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Order Summary -->
                <div>
                    <h2>Order Summary</h2>
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #eee;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo sanitize($item['image']); ?>" 
                                             alt="<?php echo sanitize($item['name']); ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo sanitize($item['name']); ?></strong>
                                        <br>
                                        <small>Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></small>
                                    </div>
                                </div>
                                <strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="padding: 1rem 0; border-top: 2px solid #667eea;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong style="font-size: 1.2rem;">Total: <?php echo formatPrice($cart_total); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div>
                    <h2>Customer Information</h2>
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <form method="POST" action="checkout.php">
                            <div class="form-group">
                                <label>Full Name:</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo sanitize($_SESSION['name']); ?>" 
                                       readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" 
                                       class="form-control" 
                                       value="<?php echo sanitize($_SESSION['email']); ?>" 
                                       readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Delivery Address:</label>
                                <textarea id="address" 
                                          name="address" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Enter your delivery address..."
                                          required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number:</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-control" 
                                       placeholder="Enter your phone number..."
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment">Payment Method:</label>
                                <select id="payment" name="payment" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="cash_on_delivery">Cash on Delivery</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Order Notes (Optional):</label>
                                <textarea id="notes" 
                                          name="notes" 
                                          class="form-control" 
                                          rows="2" 
                                          placeholder="Any special instructions..."></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <a href="cart.php" class="btn">Back to Cart</a>
                                <button type="submit" class="btn btn-success" style="flex: 1;">
                                    Place Order (<?php echo formatPrice($cart_total); ?>)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- JavaScript -->
    <script>
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const address = document.getElementById('address').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const payment = document.getElementById('payment').value;
            
            if (!address) {
                alert('Please enter your delivery address');
                e.preventDefault();
                return false;
            }
            
            if (!phone) {
                alert('Please enter your phone number');
                e.preventDefault();
                return false;
            }
            
            if (!payment) {
                alert('Please select a payment method');
                e.preventDefault();
                return false;
            }
            
            // Confirm order
            if (!confirm('Are you sure you want to place this order?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
