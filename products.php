<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($search) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY created_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$products = $result->fetch_all(MYSQLI_ASSOC);

// Get all categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Computer Store</title>
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
                    <li><a href="products.php" style="opacity: 0.8;">Products</a></li>
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
        <h1>Our Products</h1>
        
        <!-- Filters -->
        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <form method="GET" action="products.php" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label for="search">Search Products:</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           class="form-control"
                           placeholder="Search by name or description..."
                           value="<?php echo $search; ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category">Category:</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo sanitize($cat['category']); ?>" 
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Filter</button>
                <?php if ($category || $search): ?>
                    <a href="products.php" class="btn btn-warning">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter Results Info -->
        <?php if ($category || $search): ?>
            <div class="alert alert-info">
                Showing results for:
                <?php if ($search): ?>
                    <strong>Search:</strong> "<?php echo $search; ?>"
                <?php endif; ?>
                <?php if ($category): ?>
                    <strong>Category:</strong> <?php echo $category; ?>
                <?php endif; ?>
                (<?php echo count($products); ?> products found)
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
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
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-sm btn-success">Login to Buy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h3>No products found</h3>
                <p>
                    <?php if ($category || $search): ?>
                        No products match your current filters. <a href="products.php">View all products</a>
                    <?php else: ?>
                        No products are currently available. Please check back later.
                    <?php endif; ?>
                </p>
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
