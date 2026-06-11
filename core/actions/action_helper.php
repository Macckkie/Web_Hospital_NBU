<?php
// action_helper.php - Общи функции за всички действия
session_start();
require_once '../../config/db.php';
/** @var PDO $pdo */

// Проверка за оторизация
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Помощна функция за съобщения
function redirectWithMessage($type, $msg, $anchor = '') {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_msg'] = $msg;
    header("Location: ../../pages/dashboard.php" . ($anchor ? "#$anchor" : ""));
    exit();
}

// Помощна функция за логване в Дневника на дейностите
function logActivity($pdo, $userId, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        // Игнорираме грешката при запис в дневника, за да не счупим основната логика
    }
}

// Защита - приемаме само POST заявки
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/dashboard.php");
    exit();
}

$action = $_POST['action'] ?? '';
?>
