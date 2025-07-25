<?php
echo "<h2>Database Setup</h2>";

$host = "localhost";
$user = "root";
$password = "";
$dbname = "computer_store";
$port = 3306;

// Connect to MySQL without database first
$conn = new mysqli($host, $user, $password, "", $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to MySQL successfully<br>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS computer_store";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database 'computer_store' created successfully or already exists<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
    exit;
}

// Close connection and reconnect to the specific database
$conn->close();
$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("❌ Connection to database failed: " . $conn->connect_error);
}

echo "✅ Connected to 'computer_store' database<br>";

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Users table created successfully<br>";
} else {
    echo "❌ Error creating users table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Products table created successfully<br>";
} else {
    echo "❌ Error creating products table: " . $conn->error . "<br>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_price DECIMAL(10,2),
    status VARCHAR(50) DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Orders table created successfully<br>";
} else {
    echo "❌ Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Order items table created successfully<br>";
} else {
    echo "❌ Error creating order_items table: " . $conn->error . "<br>";
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Cart table created successfully<br>";
} else {
    echo "❌ Error creating cart table: " . $conn->error . "<br>";
}

// Create test users
$test_users = [
    [
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => 'admin123',
        'is_admin' => 1
    ],
    [
        'name' => 'John Doe',
        'email' => 'user@test.com',
        'password' => 'user123',
        'is_admin' => 0
    ]
];

echo "<h3>Creating Test Users:</h3>";

foreach ($test_users as $user) {
    // Check if user already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $user['email']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "ℹ️ User " . $user['email'] . " already exists<br>";
    } else {
        // Create new user
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("sssi", $user['name'], $user['email'], $hashed_password, $user['is_admin']);
        
        if ($insert_stmt->execute()) {
            echo "✅ Created user: " . $user['email'] . " (password: " . $user['password'] . ")<br>";
        } else {
            echo "❌ Error creating user " . $user['email'] . ": " . $insert_stmt->error . "<br>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Add some sample products
echo "<h3>Creating Sample Products:</h3>";

$sample_products = [
    [
        'name' => 'Gaming Laptop',
        'description' => 'High-performance gaming laptop with RTX graphics',
        'price' => 75000.00,
        'category' => 'Laptops',
    ],
    [
        'name' => 'Wireless Mouse',
        'description' => 'Ergonomic wireless mouse with RGB lighting',
        'price' => 2500.00,
        'category' => 'Accessories',
    ],
    [
        'name' => 'Mechanical Keyboard',
        'description' => 'RGB mechanical keyboard with blue switches',
        'price' => 4500.00,
        'category' => 'Accessories',
    ]
];

foreach ($sample_products as $product) {
    $check_product = $conn->prepare("SELECT id FROM products WHERE name = ?");
    if (!$check_product) {
        echo "❌ Error preparing check statement: " . $conn->error . "<br>";
        continue;
    }
    
    $check_product->bind_param("s", $product['name']);
    $check_product->execute();
    $result = $check_product->get_result();
    
    if ($result->num_rows > 0) {
        echo "ℹ️ Product '" . $product['name'] . "' already exists<br>";
    } else {
        $insert_product = $conn->prepare("INSERT INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
        if (!$insert_product) {
            echo "❌ Error preparing insert statement: " . $conn->error . "<br>";
            $check_product->close();
            continue;
        }

        $insert_product->bind_param("ssds", $product['name'], $product['description'], $product['price'], $product['category']);

        if ($insert_product->execute()) {
            echo "✅ Created product: " . $product['name'] . "<br>";
        } else {
            echo "❌ Error creating product " . $product['name'] . ": " . $insert_product->error . "<br>";
        }
        $insert_product->close();
    }
    $check_product->close();
}

$conn->close();

echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p><strong>Test Credentials:</strong></p>";
echo "<p>📧 Admin: admin@test.com / 🔑 admin123</p>";
echo "<p>📧 User: user@test.com / 🔑 user123</p>";
echo "<br>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<p>1. <a href='login.php'>Test the login page</a></p>";
echo "<p>2. <a href='index.php'>Visit the main store</a></p>";
echo "<p>3. <a href='admin/index.php'>Access admin panel</a> (after logging in as admin)</p>";
echo "</div>";
?>
