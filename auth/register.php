<?php
// register.php - Страница за регистрация в системата за Web_Hospital_NBU
session_start();
require_once '../config/db.php';
/** @var PDO $pdo */

$error = '';
$success = '';

// Вземане на отделенията и стаите за Пациенти/Лекари
try {
    $deptStmt = $pdo->query("SELECT * FROM departments");
    $departments = $deptStmt->fetchAll();

    $roomStmt = $pdo->query("SELECT * FROM rooms WHERE capacity > 0");
    $rooms = $roomStmt->fetchAll();

    $docStmt = $pdo->query("SELECT id, first_name, last_name FROM doctors");
    $doctors = $docStmt->fetchAll();
} catch (Exception $e) {
    $departments = [];
    $rooms = [];
    $doctors = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($password) && !empty($first_name) && !empty($last_name) && !empty($phone)) {
        try {
            // Проверка за съществуващо потребителско име
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->fetch()) {
                $error = 'Потребителското име вече е заето.';
            } else {
                $pdo->beginTransaction();

                // 1. Създаване на потребител
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $insUser->execute([$username, $hashedPass, $role]);
                $userId = $pdo->lastInsertId();

                // 2. Създаване на запис в съответната таблица според ролята
                if ($role === 'patient') {
                    // Генериране на уникален пациентски номер
                    $patientNum = 'PAT' . rand(10000, 99999);
                    
                    // По подразбиране
                    $illness = 'Профилактичен преглед';
                    $treatment = 'Общ клиничен преглед';
                    $docId = !empty($doctors) ? $doctors[0]['id'] : null;
                    $deptId = !empty($departments) ? $departments[0]['id'] : null;
                    $roomId = !empty($rooms) ? $rooms[0]['id'] : null;
                    $admissionDate = date('Y-m-d');

                    $insPat = $pdo->prepare("INSERT INTO patients (user_id, unique_patient_number, first_name, last_name, phone, email, illness, treatment, doctor_id, department_id, room_id, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admitted')");
                    $insPat->execute([$userId, $patientNum, $first_name, $last_name, $phone, $email, $illness, $treatment, $docId, $deptId, $roomId, $admissionDate]);
                
                } elseif ($role === 'doctor') {
                    // Генериране на УИН
                    $docNum = 'DOC' . rand(10000, 99999);
                    $qualification = 'Младши лекар';
                    $deptId = !empty($departments) ? $departments[0]['id'] : null;

                    $insDoc = $pdo->prepare("INSERT INTO doctors (user_id, unique_doc_number, first_name, last_name, phone, email, qualification, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insDoc->execute([$userId, $docNum, $first_name, $last_name, $phone, $email, $qualification, $deptId]);
                
                } elseif ($role === 'nurse' || $role === 'maintenance') {
                    $deptId = !empty($departments) ? $departments[0]['id'] : null;

                    $insStaff = $pdo->prepare("INSERT INTO staff (user_id, first_name, last_name, role, phone, department_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $insStaff->execute([$userId, $first_name, $last_name, $role, $phone, $deptId]);
                }

                $pdo->commit();
                $success = 'Регистрацията е успешна! Можете да влезете в системата.';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Възникна грешка: ' . $e->getMessage();
        }
    } else {
        $error = 'Моля, попълнете всички задължителни полета.';
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Система за управление на болница</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card glass-panel" style="max-width: 600px; padding: 32px;">
            <div class="auth-header" style="margin-bottom: 24px;">
                <div class="auth-logo">🏥</div>
                <h1 class="auth-title">Регистрация</h1>
                <p class="auth-subtitle">Създайте своя профил в системата</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️ <?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅ <?php echo htmlspecialchars($success); ?></span>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <a href="login.php" class="btn btn-primary">Към вход</a>
                </div>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">Потребителско име *</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Потребителско име" required autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Парола *</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Минимум 6 знака" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">Име *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Вашето име" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Фамилия *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Вашата фамилия" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Телефон *</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="Телефонен номер" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Имейл</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Имейл адрес">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="role" class="form-label">Искана роля в системата *</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="patient">Пациент (Регистрация на болничен картон)</option>
                            <option value="doctor">Лекар</option>
                            <option value="nurse">Медицинска сестра</option>
                            <option value="maintenance">Поддръжка на болницата</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-bottom: 20px;">
                        Регистрирай ме
                    </button>
                </form>

                <div style="text-align: center; font-size: 14px; color: var(--text-muted);">
                    Вече имате профил? <a href="login.php" style="font-weight: 600;">Влезте оттук</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
