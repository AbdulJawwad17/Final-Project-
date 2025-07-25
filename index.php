<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get featured products
$featured_query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 6";
$featured_result = $conn->query($featured_query);
$featured_products = $featured_result->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Store - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>🖥️ Computer Store</h1>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Welcome to Computer Store</h2>
            <p>Find the best computers, laptops, and accessories for all your needs</p>
            <a href="products.php" class="btn">Shop Now</a>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <!-- Categories Section -->
        <section class="categories-section">
            <h2>Shop by Category</h2>
            <div class="products-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="product-card">
                        <div class="product-info">
                            <h3><?php echo sanitize($category['category']); ?></h3>
                            <a href="products.php?category=<?php echo urlencode($category['category']); ?>" class="btn">
                                Browse <?php echo sanitize($category['category']); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="featured-products">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card fade-in">
                        <?php if ($product['image']): ?>
                            <img src="<?php echo sanitize($product['image']); ?>" 
                                 alt="<?php echo sanitize($product['name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                No Image
                            </div>
                        <?php endif; ?>
                        <div class="product-info">
                            <h3><?php echo sanitize($product['name']); ?></h3>
                            <p><?php echo sanitize(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <?php if ($product['category']): ?>
                                <span class="product-category"><?php echo sanitize($product['category']); ?></span>
                            <?php endif; ?>
                            <div style="margin-top: 1rem;">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">View Details</a>
                                <?php if (isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-sm btn-success">Add to Cart</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (empty($featured_products)): ?>
            <div class="alert alert-info">
                <p>No products available. Please contact the administrator to add products.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- JavaScript -->
    <script>
        function addToCart(productId) {
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId
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
                    alert('Product added to cart!');
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
