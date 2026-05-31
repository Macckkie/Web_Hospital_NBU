<?php
// index.php - Начална страница и Вход в системата за Web_Hospital_NBU
session_start();
require_once 'db.php';

// Ако потребителят е вече влязъл, го пренасочваме към таблото
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Вземане на информация за болницата
try {
    $hospitalStmt = $pdo->query("SELECT * FROM hospital_info LIMIT 1");
    $hospital = $hospitalStmt->fetch();
    if (!$hospital) {
        $hospital = [
            'name' => 'Университетска болница "Здраве" - НБУ',
            'address' => 'гр. София, ул. Монтевидео №21'
        ];
    }
} catch (Exception $e) {
    $hospital = [
        'name' => 'Университетска болница "Здраве" - НБУ',
        'address' => 'гр. София, ул. Монтевидео №21'
    ];
}

// Обработка на формата за вход
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Входът е успешен! Запазваме сесията
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Вземане на личните данни според ролята
                if ($user['role'] === 'doctor') {
                    $docStmt = $pdo->prepare("SELECT id, first_name, last_name FROM doctors WHERE user_id = ?");
                    $docStmt->execute([$user['id']]);
                    $doc = $docStmt->fetch();
                    if ($doc) {
                        $_SESSION['doctor_id'] = $doc['id'];
                        $_SESSION['full_name'] = $doc['first_name'] . ' ' . $doc['last_name'];
                    }
                } elseif ($user['role'] === 'patient') {
                    $patStmt = $pdo->prepare("SELECT id, first_name, last_name FROM patients WHERE user_id = ?");
                    $patStmt->execute([$user['id']]);
                    $pat = $patStmt->fetch();
                    if ($pat) {
                        $_SESSION['patient_id'] = $pat['id'];
                        $_SESSION['full_name'] = $pat['first_name'] . ' ' . $pat['last_name'];
                    }
                } elseif ($user['role'] === 'nurse' || $user['role'] === 'maintenance') {
                    $staffStmt = $pdo->prepare("SELECT id, first_name, last_name FROM staff WHERE user_id = ?");
                    $staffStmt->execute([$user['id']]);
                    $staff = $staffStmt->fetch();
                    if ($staff) {
                        $_SESSION['staff_id'] = $staff['id'];
                        $_SESSION['full_name'] = $staff['first_name'] . ' ' . $staff['last_name'];
                    }
                } else {
                    $_SESSION['full_name'] = 'Администратор';
                }

                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Невалидно потребителско име или парола.';
            }
        } catch (PDOException $e) {
            $error = 'Грешка в базата данни: ' . $e->getMessage();
        }
    } else {
        $error = 'Моля, попълнете всички полета.';
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card glass-panel">
            <div class="auth-header">
                <div class="auth-logo">🏥</div>
                <h1 class="auth-title">Добре дошли</h1>
                <p class="auth-subtitle">Система за управление на <?php echo htmlspecialchars($hospital['name']); ?></p>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">📍 <?php echo htmlspecialchars($hospital['address']); ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️ <?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Потребителско име</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Въведете потребителско име" required autofocus autocomplete="username">
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="password" class="form-label">Парола</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Въведете вашата парола" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-bottom: 20px;">
                    Вход в системата
                </button>
            </form>

            <div style="text-align: center; font-size: 14px; color: var(--text-muted);">
                Нямате профил? <a href="register.php" style="font-weight: 600;">Регистрирайте се тук</a>
            </div>

            <div style="margin-top: 32px; padding: 16px; border: 1px dashed var(--primary); background: var(--primary-light); border-radius: var(--radius-md); font-size: 11px; color: var(--primary-dark); text-align: left;">
                <p style="font-weight: 700; margin-bottom: 8px; font-family: var(--font-heading); font-size: 12px; text-align: center; text-transform: uppercase;">🎓 ДЕМО ДОСТЪП ЗА ОЦЕНЯВАНЕ (НБУ)</p>
                <p style="margin-bottom: 10px; font-weight: 500; text-align: center;">Всички пароли са защитени и шифрирани с висок клас сигурност (bcrypt).<br>За целите на теста въведете парола: <strong>password123</strong></p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>• <strong>Администратор:</strong> admin</div>
                    <div>• <strong>Директор:</strong> director</div>
                    <div>• <strong>Лекар:</strong> dr.ivanov</div>
                    <div>• <strong>Сестра:</strong> nurse.stoyanova</div>
                    <div>• <strong>Поддръжка:</strong> maint.petrov</div>
                    <div>• <strong>Пациент:</strong> patient.dimitrov</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
