<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/user_actions.php
if ($action === 'add_user' || $action === 'edit_user' || $action === 'delete_user') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_user') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $user_role = $_POST['user_role'];

        if (!empty($username) && !empty($password)) {
            try {
                $hPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hPass, $user_role]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Потребителят беше добавен успешно в базата!', 'users');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: Потребителското име вече съществува.', 'users');
            }
        }
    }

    if ($action === 'edit_user') {
        $id = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $user_role = $_POST['user_role'];
        $new_password = trim($_POST['password']);

        if ($id > 0 && !empty($username)) {
            try {
                if (!empty($new_password)) {
                    $hPass = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $hPass, $user_role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $user_role, $id]);
                }
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Потребителският акаунт беше актуализиран!', 'users');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: Потребителското име вече се ползва.', 'users');
            }
        }
    }

    if ($action === 'delete_user') {
        $id = intval($_POST['user_id']);
        if ($id === $userId) {
            redirectWithMessage('danger', 'Не можете да изтриете собствения си акаунт в момента!', 'users');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Потребителят беше успешно изтрит.', 'users');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Този потребител е свързан с медицинско лице или пациент. Първо изтрийте съответния лекар/пациент.', 'users');
        }
    }
}
?>
