<?php
// Database configuration for XAMPP

$host = "localhost";
$dbname = "hotel_db";
$username = "root";
$password = "Root@123";

// Create connection using PDO
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    // Set error mode to exception (important for debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Stop execution if connection fails
    die("Database Connection Failed: " . $e->getMessage());
}
?>