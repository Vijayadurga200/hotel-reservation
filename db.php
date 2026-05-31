<?php
$host = "mysql-6ca1d4f-vijayadurgachandana7-1cae.j.aivencloud.com";
$port = 27447;
$dbname = "defaultdb";
$username = "avnadmin";
$password = "AVNS_1EO2y8s-g1dQKO-HBLE";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";

    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
