<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "computer_store";
$port = 3306;

try {
    $conn = new mysqli($host, $user, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
