<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: index.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$product = $result->fetch_assoc();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $category = sanitize($_POST['category']);
    $image = sanitize($_POST['image']);
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // Update product
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image, $product_id);
        
        if ($stmt->execute()) {
            $success = 'Product updated successfully!';
            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            $error = 'Error updating product: ' . $stmt->error;
        }
    }
}

// Get existing categories
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$categories_result = $conn->query($categories_query);
$existing_categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
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
            <li><a href="orders.php">Manage Orders</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <h1>Edit Product</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p><a href="../product.php?id=<?php echo $product_id; ?>">View product</a> | <a href="index.php">Back to dashboard</a></p>
            </div>
        <?php endif; ?>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Edit Form -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h2>Product Details</h2>
                    <form method="POST" action="edit_product.php?id=<?php echo $product_id; ?>" id="productForm">
                        <div class="form-group">
                            <label for="name">Product Name: *</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-control"
                                   value="<?php echo sanitize($product['name']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description: *</label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control" 
                                      rows="4"
                                      required><?php echo sanitize($product['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₱): *</label>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   class="form-control"
                                   step="0.01" 
                                   min="0.01"
                                   value="<?php echo $product['price']; ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category: *</label>
                            <input type="text" 
                                   id="category" 
                                   name="category" 
                                   class="form-control"
                                   list="categories"
                                   value="<?php echo sanitize($product['category']); ?>"
                                   required>
                            <datalist id="categories">
                                <?php foreach ($existing_categories as $cat): ?>
                                    <option value="<?php echo sanitize($cat['category']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image URL:</label>
                            <input type="url" 
                                   id="image" 
                                   name="image" 
                                   class="form-control"
                                   value="<?php echo sanitize($product['image']); ?>">
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <a href="index.php" class="btn">Cancel</a>
                            <button type="submit" class="btn btn-success" style="flex: 1;">Update Product</button>
                        </div>
                    </form>
                </div>
                
                <!-- Product Preview -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h2>Product Preview</h2>
                    <div class="product-card" style="margin: 0;">
                        <div id="preview-image">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo sanitize($product['image']); ?>" 
                                     alt="<?php echo sanitize($product['name']); ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                    No Image
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 id="preview-name"><?php echo sanitize($product['name']); ?></h3>
                            <p id="preview-description"><?php echo sanitize(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <div class="product-price" id="preview-price"><?php echo formatPrice($product['price']); ?></div>
                            <span class="product-category" id="preview-category"><?php echo sanitize($product['category']); ?></span>
                            <div style="margin-top: 1rem;">
                                <a href="../product.php?id=<?php echo $product_id; ?>" class="btn btn-sm" target="_blank">View on Store</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                        <h4>Product Information</h4>
                        <ul style="margin-top: 0.5rem;">
                            <li><strong>Product ID:</strong> #<?php echo $product['id']; ?></li>
                            <li><strong>Created:</strong> <?php echo date('F j, Y g:i A', strtotime($product['created_at'])); ?></li>
                        </ul>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div style="margin-top: 2rem; padding: 1rem; background: #ffe6e6; border-radius: 5px; border: 1px solid #ffcccc;">
                        <h4 style="color: #dc3545;">Danger Zone</h4>
                        <p style="font-size: 0.9rem; color: #666;">Once you delete a product, there is no going back. Please be certain.</p>
                        <button onclick="deleteProduct(<?php echo $product_id; ?>)" class="btn btn-sm btn-danger">
                            Delete Product
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Real-time preview updates
        function updatePreview() {
            const name = document.getElementById('name').value;
            const description = document.getElementById('description').value;
            const price = document.getElementById('price').value;
            const category = document.getElementById('category').value;
            const image = document.getElementById('image').value;
            
            document.getElementById('preview-name').textContent = name || 'Product Name';
            document.getElementById('preview-description').textContent = description ? description.substring(0, 100) + '...' : 'Product description...';
            document.getElementById('preview-price').textContent = price ? '₱' + parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2}) : '₱0.00';
            document.getElementById('preview-category').textContent = category || 'Category';
            
            // Update image
            const previewImage = document.getElementById('preview-image');
            if (image) {
                previewImage.innerHTML = '<img src="' + image + '" alt="' + name + '" class="product-image" onerror="this.parentElement.innerHTML=\'<div class=\\\"product-image\\\" style=\\\"background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;\\\">Invalid Image</div>\'">';
            } else {
                previewImage.innerHTML = '<div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">No Image</div>';
            }
        }
        
        // Add event listeners for real-time updates
        document.getElementById('name').addEventListener('input', updatePreview);
        document.getElementById('description').addEventListener('input', updatePreview);
        document.getElementById('price').addEventListener('input', updatePreview);
        document.getElementById('category').addEventListener('input', updatePreview);
        document.getElementById('image').addEventListener('input', updatePreview);
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            
            if (price <= 0) {
                alert('Price must be greater than 0');
                e.preventDefault();
                return false;
            }
        });
        
        // Delete product function
        function deleteProduct(productId) {
            if (!confirm('Are you absolutely sure you want to delete this product?\n\nThis action cannot be undone and will remove the product from all user carts and order history.')) {
                return;
            }
            
            if (!confirm('Last chance! This will permanently delete the product. Are you sure?')) {
                return;
            }
            
            fetch('delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product deleted successfully');
                    window.location.href = 'index.php';
                } else {
                    alert('Error deleting product: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting product');
            });
        }
    </script>
</body>
</html>
