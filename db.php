<?php
// db.php - Връзка с базата данни с автоматична инициализация на схемата и демо данните

$host = '127.0.0.1';
$port = '3310'; // Портът за MySQL в тази XAMPP инсталация
$user = 'root';
$pass = '';
$dbname = 'hospital_db';

try {
    // 1. Първоначална връзка към MySQL без селектирана база данни
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 2. Проверка дали базата данни съществува
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        // Базата данни липсва - създаваме я и изпълняваме schema.sql
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        $sqlPath = __DIR__ . '/schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Изпълняваме целия SQL скрипт наведнъж
            $pdo->exec($sql);
        } else {
            throw new Exception("Файлът schema.sql не бе намерен за инициализация.");
        }
    } else {
        // Базата данни съществува - селектираме я за работа
        $pdo->exec("USE `$dbname`");
    }

} catch (PDOException $e) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
            <strong>Грешка при връзка с базата данни:</strong> " . htmlspecialchars($e->getMessage()) . "
         </div>");
} catch (Exception $e) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
            <strong>Системна грешка:</strong> " . htmlspecialchars($e->getMessage()) . "
         </div>");
}
?>
