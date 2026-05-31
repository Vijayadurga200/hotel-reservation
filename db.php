<?php
$host = "mysql-6ca1d4f-vijayadurgachandana7-1cae.j.aivencloud.com";
$port = 27447;
$dbname = "defaultdb";
$username = "avnadmin";
$password = "AVNS_1EO2y8s-g1dQKO-HBLE"; // paste from Aiven

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
