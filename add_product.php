<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

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
        // Insert product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image);
        
        if ($stmt->execute()) {
            $success = 'Product added successfully!';
            // Clear form
            $_POST = array();
        } else {
            $error = 'Error adding product: ' . $stmt->error;
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
    <title>Add Product - Admin</title>
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
            <li><a href="add_product.php" style="background: rgba(255,255,255,0.1);">Add Product</a></li>
            <li><a href="orders.php">Manage Orders</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <h1>Add New Product</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p><a href="../products.php">View all products</a> | <a href="add_product.php">Add another product</a></p>
            </div>
        <?php endif; ?>
        
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <form method="POST" action="add_product.php" id="productForm">
                    <div class="form-group">
                        <label for="name">Product Name: *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control"
                               value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description: *</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="4"
                                  required><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₱): *</label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               class="form-control"
                               step="0.01" 
                               min="0.01"
                               value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category: *</label>
                        <input type="text" 
                               id="category" 
                               name="category" 
                               class="form-control"
                               list="categories"
                               value="<?php echo isset($_POST['category']) ? sanitize($_POST['category']) : ''; ?>"
                               required>
                        <datalist id="categories">
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?php echo sanitize($cat['category']); ?>">
                            <?php endforeach; ?>
                            <option value="Laptops">
                            <option value="Desktops">
                            <option value="Accessories">
                            <option value="Components">
                            <option value="Monitors">
                            <option value="Storage">
                            <option value="Memory">
                            <option value="Graphics Cards">
                            <option value="Processors">
                        </datalist>
                        <small style="color: #666;">Start typing to see suggestions or enter a new category</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image URL (Optional):</label>
                        <input type="url" 
                               id="image" 
                               name="image" 
                               class="form-control"
                               placeholder="https://example.com/image.jpg"
                               value="<?php echo isset($_POST['image']) ? sanitize($_POST['image']) : ''; ?>">
                        <small style="color: #666;">Enter a valid image URL or leave blank</small>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="image-preview" style="margin: 1rem 0; text-align: center;"></div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <a href="index.php" class="btn">Back to Dashboard</a>
                        <button type="submit" class="btn btn-success" style="flex: 1;">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Add Popular Products -->
        <div style="max-width: 600px; margin: 2rem auto;">
            <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Quick Add Popular Products</h3>
                <p>Click any button below to quickly fill the form with sample product data:</p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
                    <button onclick="fillSampleProduct('laptop')" class="btn btn-sm">Gaming Laptop</button>
                    <button onclick="fillSampleProduct('desktop')" class="btn btn-sm">Gaming Desktop</button>
                    <button onclick="fillSampleProduct('monitor')" class="btn btn-sm">Gaming Monitor</button>
                    <button onclick="fillSampleProduct('mouse')" class="btn btn-sm">Gaming Mouse</button>
                    <button onclick="fillSampleProduct('keyboard')" class="btn btn-sm">Mechanical Keyboard</button>
                    <button onclick="fillSampleProduct('headset')" class="btn btn-sm">Gaming Headset</button>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Image preview
        document.getElementById('image').addEventListener('input', function() {
            const imageUrl = this.value;
            const preview = document.getElementById('image-preview');
            
            if (imageUrl) {
                preview.innerHTML = '<img src="' + imageUrl + '" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 5px;" onerror="this.style.display=\'none\'">';
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Sample product data
        const sampleProducts = {
            laptop: {
                name: 'ROG Strix Gaming Laptop',
                description: 'High-performance gaming laptop with RTX 4060 graphics card, Intel Core i7 processor, 16GB RAM, and 512GB SSD. Perfect for gaming and professional work.',
                price: '85000.00',
                category: 'Laptops',
                image: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=400'
            },
            desktop: {
                name: 'Custom Gaming Desktop PC',
                description: 'Powerful gaming desktop with RTX 4070 graphics, AMD Ryzen 7 processor, 32GB DDR4 RAM, 1TB NVMe SSD. Ready for 4K gaming and content creation.',
                price: '120000.00',
                category: 'Desktops',
                image: 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?w=400'
            },
            monitor: {
                name: '27" 4K Gaming Monitor',
                description: '27-inch 4K IPS monitor with 144Hz refresh rate, HDR support, and AMD FreeSync technology. Perfect for competitive gaming and content creation.',
                price: '25000.00',
                category: 'Monitors',
                image: 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=400'
            },
            mouse: {
                name: 'RGB Gaming Mouse',
                description: 'Ergonomic wireless gaming mouse with RGB lighting, 12000 DPI sensor, programmable buttons, and 70-hour battery life.',
                price: '3500.00',
                category: 'Accessories',
                image: 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400'
            },
            keyboard: {
                name: 'RGB Mechanical Keyboard',
                description: 'Premium mechanical keyboard with Cherry MX switches, RGB backlighting, programmable macros, and aluminum construction.',
                price: '6500.00',
                category: 'Accessories',
                image: 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=400'
            },
            headset: {
                name: 'Wireless Gaming Headset',
                description: 'Premium wireless gaming headset with 7.1 surround sound, noise-canceling microphone, and 20-hour battery life.',
                price: '8500.00',
                category: 'Accessories',
                image: 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=400'
            }
        };
        
        function fillSampleProduct(type) {
            const product = sampleProducts[type];
            if (product) {
                document.getElementById('name').value = product.name;
                document.getElementById('description').value = product.description;
                document.getElementById('price').value = product.price;
                document.getElementById('category').value = product.category;
                document.getElementById('image').value = product.image;
                
                // Trigger image preview
                document.getElementById('image').dispatchEvent(new Event('input'));
            }
        }
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            
            if (price <= 0) {
                alert('Price must be greater than 0');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
