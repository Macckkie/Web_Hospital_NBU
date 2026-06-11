<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hospital_db';
$ports = ['3310', '3306'];

foreach ($ports as $port) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
        echo "Dropped hospital_db on port $port\n";
    } catch (PDOException $e) {
        echo "Failed on port $port: " . $e->getMessage() . "\n";
    }
}
