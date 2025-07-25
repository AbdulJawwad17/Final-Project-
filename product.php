<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();

// Get related products (same category)
$related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$related_stmt->bind_param("si", $product['category'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
$related_products = $related_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($product['name']); ?> - Computer Store</title>
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
                    <?php if (isLoggedIn()): ?>
                        <li><a href="cart.php">Cart</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin/index.php">Admin</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <a href="cart.php" class="cart-icon">
                        🛒
                        <?php 
                        $cart_count = getCartCount();
                        if ($cart_count > 0): 
                        ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <span>Welcome, <?php echo sanitize($_SESSION['name']); ?>!</span>
                    <a href="logout.php" class="btn btn-sm">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm">Login</a>
                    <a href="register.php" class="btn btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 2rem;">
            <a href="index.php">Home</a> &gt; 
            <a href="products.php">Products</a> &gt; 
            <?php if ($product['category']): ?>
                <a href="products.php?category=<?php echo urlencode($product['category']); ?>"><?php echo sanitize($product['category']); ?></a> &gt; 
            <?php endif; ?>
            <span><?php echo sanitize($product['name']); ?></span>
        </nav>

        <!-- Product Details -->
        <div class="product-detail">
            <div>
                <?php if ($product['image']): ?>
                    <img src="<?php echo sanitize($product['image']); ?>" 
                         alt="<?php echo sanitize($product['name']); ?>" 
                         class="product-detail-image">
                <?php else: ?>
                    <div class="product-detail-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; min-height: 400px;">
                        No Image Available
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-detail-info">
                <h1><?php echo sanitize($product['name']); ?></h1>
                
                <?php if ($product['category']): ?>
                    <span class="product-category" style="margin-bottom: 1rem; display: inline-block;">
                        <?php echo sanitize($product['category']); ?>
                    </span>
                <?php endif; ?>
                
                <div class="product-detail-price"><?php echo formatPrice($product['price']); ?></div>
                
                <div class="product-detail-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(sanitize($product['description'])); ?></p>
                </div>
                
                <div style="margin-top: 2rem;">
                    <?php if (isLoggedIn()): ?>
                        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                            <label for="quantity">Quantity:</label>
                            <input type="number" 
                                   id="quantity" 
                                   value="1" 
                                   min="1" 
                                   max="10" 
                                   style="width: 80px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-success" style="margin-right: 1rem;">
                            Add to Cart
                        </button>
                        <a href="cart.php" class="btn">View Cart</a>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p><a href="login.php">Login</a> to add this item to your cart and make a purchase.</p>
                        </div>
                        <a href="login.php" class="btn btn-success">Login to Buy</a>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                    <h4>Product Information</h4>
                    <ul style="margin-top: 0.5rem;">
                        <li><strong>Product ID:</strong> #<?php echo $product['id']; ?></li>
                        <li><strong>Category:</strong> <?php echo sanitize($product['category']); ?></li>
                        <li><strong>Added:</strong> <?php echo date('F j, Y', strtotime($product['created_at'])); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section style="margin-top: 4rem;">
                <h2>Related Products</h2>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="product-card">
                            <?php if ($related['image']): ?>
                                <img src="<?php echo sanitize($related['image']); ?>" 
                                     alt="<?php echo sanitize($related['name']); ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                    No Image
                                </div>
                            <?php endif; ?>
                            <div class="product-info">
                                <h3><?php echo sanitize($related['name']); ?></h3>
                                <p><?php echo sanitize(substr($related['description'], 0, 80)) . '...'; ?></p>
                                <div class="product-price"><?php echo formatPrice($related['price']); ?></div>
                                <div style="margin-top: 1rem;">
                                    <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- JavaScript -->
    <script>
        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    } else if (data.cart_count > 0) {
                        // Create cart count element if it doesn't exist
                        const cartIcon = document.querySelector('.cart-icon');
                        const countSpan = document.createElement('span');
                        countSpan.className = 'cart-count';
                        countSpan.textContent = data.cart_count;
                        cartIcon.appendChild(countSpan);
                    }
                    
                    // Show success message
                    alert('Product added to cart successfully!');
                    
                    // Reset quantity to 1
                    document.getElementById('quantity').value = 1;
                } else {
                    alert('Error adding product to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }
    </script>
</body>
</html>
