<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    
    if ($order_id && in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $order_id);
        
        if ($update_stmt->execute()) {
            $success = "Order #$order_id status updated to: $status";
        } else {
            $error = "Failed to update order status";
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query for orders
$query = "
    SELECT o.*, u.name as user_name, u.email as user_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];
$types = "";

if ($status_filter && $status_filter != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY o.order_date DESC";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get order statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_price) as total_revenue,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="add_product.php">Add Product</a></li>
            <li><a href="orders.php" style="background: rgba(255,255,255,0.1);">Manage Orders</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <h1>Order Management</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Order Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: #667eea; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo $stats['total_orders']; ?></h3>
                <p>Total Orders</p>
            </div>
            <div style="background: #28a745; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo formatPrice($stats['total_revenue']); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div style="background: #ffc107; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo $stats['pending_orders']; ?></h3>
                <p>Pending Orders</p>
            </div>
            <div style="background: #17a2b8; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo $stats['processing_orders']; ?></h3>
                <p>Processing</p>
            </div>
            <div style="background: #6f42c1; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo $stats['shipped_orders']; ?></h3>
                <p>Shipped</p>
            </div>
            <div style="background: #20c997; color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
                <h3><?php echo $stats['delivered_orders']; ?></h3>
                <p>Delivered</p>
            </div>
        </div>

        <!-- Filters -->
        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <form method="GET" action="orders.php" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                    <label for="search">Search Orders:</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           class="form-control"
                           placeholder="Search by customer name, email, or order ID..."
                           value="<?php echo $search; ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status">Status:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Filter</button>
                <?php if ($status_filter || $search): ?>
                    <a href="orders.php" class="btn btn-warning">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Orders Table -->
        <?php if (!empty($orders)): ?>
            <div style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo sanitize($order['user_name']); ?></strong>
                                    <br>
                                    <small><?php echo sanitize($order['user_email']); ?></small>
                                </td>
                                <td><strong><?php echo formatPrice($order['total_price']); ?></strong></td>
                                <td>
                                    <form method="POST" action="orders.php" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="width: auto; display: inline-block;">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                    <br>
                                    <small><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
                                </td>
                                <td>
                                    <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" class="btn btn-sm">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h3>No orders found</h3>
                <p>
                    <?php if ($status_filter || $search): ?>
                        No orders match your current filters. <a href="orders.php">View all orders</a>
                    <?php else: ?>
                        No orders have been placed yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Order Details</h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="orderDetailsContent">
                Loading...
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function viewOrderDetails(orderId) {
            document.getElementById('orderModal').style.display = 'block';
            document.getElementById('orderDetailsContent').innerHTML = 'Loading...';
            
            fetch('orders.php?action=get_details&order_id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = 'Error loading order details.';
                });
        }
        
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <?php
    // Handle AJAX request for order details
    if (isset($_GET['action']) && $_GET['action'] == 'get_details' && isset($_GET['order_id'])) {
        $order_id = (int)$_GET['order_id'];
        
        // Get order details
        $order_stmt = $conn->prepare("
            SELECT o.*, u.name as user_name, u.email as user_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order_detail = $order_result->fetch_assoc();
        
        // Get order items
        $items_stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
        
        if ($order_detail) {
            echo '<div>';
            echo '<p><strong>Order ID:</strong> #' . $order_detail['id'] . '</p>';
            echo '<p><strong>Customer:</strong> ' . sanitize($order_detail['user_name']) . '</p>';
            echo '<p><strong>Email:</strong> ' . sanitize($order_detail['user_email']) . '</p>';
            echo '<p><strong>Order Date:</strong> ' . date('F j, Y g:i A', strtotime($order_detail['order_date'])) . '</p>';
            echo '<p><strong>Status:</strong> <span style="padding: 0.2rem 0.5rem; background: #667eea; color: white; border-radius: 3px;">' . sanitize($order_detail['status']) . '</span></p>';
            echo '<p><strong>Total:</strong> ' . formatPrice($order_detail['total_price']) . '</p>';
            
            echo '<h3>Order Items:</h3>';
            echo '<div>';
            foreach ($order_items as $item) {
                echo '<div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">';
                echo '<div>' . sanitize($item['product_name'] ?: 'Product #' . $item['product_id']) . '</div>';
                echo '<div>Qty: ' . $item['quantity'] . ' × ' . formatPrice($item['price']) . ' = ' . formatPrice($item['quantity'] * $item['price']) . '</div>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            echo 'Order not found.';
        }
        exit();
    }
    ?>
</body>
</html>
