<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Get statistics
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM orders")->fetch_assoc()['total'] ?? 0;

// Get recent orders
$recent_orders_query = "
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 5
";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

// Get low stock products (if we had stock management)
$recent_products_query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 5";
$recent_products = $conn->query($recent_products_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Computer Store</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><a href="../index.php" style="color: white; text-decoration: none;">🖥️ Computer Store</a></h1>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="../index.php">Store Front</a></li>
                    <li><a href="../cart.php">Cart</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <span>Admin: <?php echo sanitize($_SESSION['name']); ?></span>
                <a href="../logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <ul>
            <li><a href="index.php" style="background: rgba(255,255,255,0.1);">Dashboard</a></li>
            <li><a href="add_product.php">Add Product</a></li>
            <li><a href="orders.php">Manage Orders</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <h1>Admin Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; text-align: center;">
                <h3>Total Users</h3>
                <div style="font-size: 2.5rem; font-weight: bold; margin: 1rem 0;"><?php echo $users_count; ?></div>
                <p>Registered users</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 2rem; border-radius: 10px; text-align: center;">
                <h3>Total Products</h3>
                <div style="font-size: 2.5rem; font-weight: bold; margin: 1rem 0;"><?php echo $products_count; ?></div>
                <p>Products in store</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 2rem; border-radius: 10px; text-align: center;">
                <h3>Total Orders</h3>
                <div style="font-size: 2.5rem; font-weight: bold; margin: 1rem 0;"><?php echo $orders_count; ?></div>
                <p>Orders placed</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); color: white; padding: 2rem; border-radius: 10px; text-align: center;">
                <h3>Total Revenue</h3>
                <div style="font-size: 2.5rem; font-weight: bold; margin: 1rem 0;"><?php echo formatPrice($total_revenue); ?></div>
                <p>Total sales</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 3rem;">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                <a href="add_product.php" class="btn btn-success">Add New Product</a>
                <a href="orders.php" class="btn btn-primary">View All Orders</a>
                <a href="../products.php" class="btn btn-warning">View Store</a>
                <a href="../setup.php" class="btn" target="_blank">Run Setup</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Recent Orders -->
            <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h2>Recent Orders</h2>
                <?php if (!empty($recent_orders)): ?>
                    <div style="margin-top: 1rem;">
                        <?php foreach ($recent_orders as $order): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Order #<?php echo $order['id']; ?></strong>
                                    <br>
                                    <small>by <?php echo sanitize($order['user_name']); ?></small>
                                    <br>
                                    <small><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <strong><?php echo formatPrice($order['total_price']); ?></strong>
                                    <br>
                                    <span style="padding: 0.2rem 0.5rem; background: 
                                        <?php echo $order['status'] == 'pending' ? '#ffc107' : '#28a745'; ?>; 
                                        color: white; border-radius: 3px; font-size: 0.8rem;">
                                        <?php echo sanitize($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="orders.php" class="btn btn-sm">View All Orders</a>
                    </div>
                <?php else: ?>
                    <p>No orders yet.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Products -->
            <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h2>Recent Products</h2>
                <?php if (!empty($recent_products)): ?>
                    <div style="margin-top: 1rem;">
                        <?php foreach ($recent_products as $product): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong><?php echo sanitize($product['name']); ?></strong>
                                    <br>
                                    <small><?php echo sanitize($product['category']); ?></small>
                                    <br>
                                    <small>Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <strong><?php echo formatPrice($product['price']); ?></strong>
                                    <br>
                                    <div style="margin-top: 0.5rem;">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">Edit</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="../products.php" class="btn btn-sm">View All Products</a>
                    </div>
                <?php else: ?>
                    <p>No products yet. <a href="add_product.php">Add your first product</a></p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
