<?php
// db.php - Връзка с базата данни с динамично засичане на порта (3310 или 3306) и авто-инициализация

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hospital_db';

$ports = ['3310', '3306'];
$connected = false;
$pdo = null;
$lastError = '';

foreach ($ports as $port) {
    try {
        // Първоначална връзка към MySQL без селектирана база данни
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $connected = true;
        break; // Успешно свързване, прекъсваме цикъла
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
    }
}

if (!$connected) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
            <strong>Грешка при връзка с базата данни (опитани портове 3310 и 3306):</strong> " . htmlspecialchars($lastError) . "
         </div>");
}

try {
    // 2. Проверка дали базата данни съществува
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $dbExists = $stmt->fetch();

    $needInit = false;

    if (!$dbExists) {
        // Базата данни липсва - създаваме я
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        $needInit = true;
    } else {
        // Базата данни съществува - селектираме я
        $pdo->exec("USE `$dbname`");
        // Проверяваме дали таблицата 'users' съществува
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
        if (!$tableCheck->fetch()) {
            $needInit = true;
        } else {
            // Самолекуващ се механизъм за паролите: ако съществува стара парола, я обновяваме автоматично на 'password123' за всички потребители
            try {
                $testStmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
                $testStmt->execute();
                $adminHash = $testStmt->fetchColumn();
                
                if ($adminHash && !password_verify('password123', $adminHash)) {
                    $newHash = password_hash('password123', PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ?")->execute([$newHash]);
                }
            } catch (Exception $ex) {
                $needInit = true;
            }
        }
    }

    if ($needInit) {
        $sqlPath = __DIR__ . '/schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Изпълняваме целия SQL скрипт наведнъж
            $pdo->exec($sql);
        } else {
            throw new Exception("Файлът schema.sql не бе намерен за инициализация.");
        }
    }

} catch (Exception $e) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
            <strong>Системна грешка при инициализация:</strong> " . htmlspecialchars($e->getMessage()) . "
         </div>");
}
?>
