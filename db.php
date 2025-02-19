<?php
// db.php
$host = 'localhost';   // Change this to your database host
$dbname = 'users';     // Database name
$username = 'root';    // Your database username
$password = '';        // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
