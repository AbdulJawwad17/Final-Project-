<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect based on user type
                if ($user['is_admin']) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Computer Store</title>
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
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="form-container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Login to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control"
                           value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            
            <!-- Demo Credentials -->
            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                <h4>Demo Credentials:</h4>
                <p><strong>Admin:</strong> admin@test.com / admin123</p>
                <p><strong>User:</strong> user@test.com / user123</p>
            </div>
        </div>
    </main>

    <!-- JavaScript for demo login -->
    <script>
        // Quick login buttons for demo
        function quickLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
        
        // Add quick login buttons
        document.addEventListener('DOMContentLoaded', function() {
            const demoDiv = document.querySelector('div[style*="background: #f8f9fa"]');
            
            const adminBtn = document.createElement('button');
            adminBtn.textContent = 'Quick Admin Login';
            adminBtn.className = 'btn btn-sm btn-warning';
            adminBtn.style.marginRight = '10px';
            adminBtn.onclick = function() { quickLogin('admin@test.com', 'admin123'); };
            
            const userBtn = document.createElement('button');
            userBtn.textContent = 'Quick User Login';
            userBtn.className = 'btn btn-sm btn-warning';
            userBtn.onclick = function() { quickLogin('user@test.com', 'user123'); };
            
            demoDiv.appendChild(document.createElement('br'));
            demoDiv.appendChild(adminBtn);
            demoDiv.appendChild(userBtn);
        });
    </script>
</body>
</html>
