<?php
$host = "mysql-6ca1d4f-vijayadurgachandana7-1cae.j.aivencloud.com";
$port = 3306;
$dbname = "hotel_db";
$username = "avnadmin";
$password = "Root@123";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";

    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
