<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/staff_actions.php
if ($action === 'add_staff' || $action === 'edit_staff' || $action === 'delete_staff') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_staff') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $staff_role = $_POST['staff_role']; // nurse / maintenance
        $phone = trim($_POST['phone']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!empty($first_name) && !empty($last_name) && !empty($phone)) {
            try {
                $pdo->beginTransaction();
                $userIdCreated = null;

                if (!empty($username) && !empty($password)) {
                    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) throw new Exception('Потребителското име вече е заето.');

                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $insUser->execute([$username, $hPass, $staff_role]);
                    $userIdCreated = $pdo->lastInsertId();
                }

                $insStaff = $pdo->prepare("INSERT INTO staff (user_id, first_name, last_name, role, phone, department_id) VALUES (?, ?, ?, ?, ?, ?)");
                $insStaff->execute([$userIdCreated, $first_name, $last_name, $staff_role, $phone, $department_id]);

                $pdo->commit();
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Служителят беше добавен успешно!', 'staff');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
            }
        }
    }

    if ($action === 'edit_staff') {
        $id = intval($_POST['staff_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone)) {
            try {
                $stmt = $pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, phone = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $department_id, $id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Данните за служителя бяха актуализирани!', 'staff');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
            }
        }
    }

    if ($action === 'delete_staff') {
        $id = intval($_POST['staff_id']);
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
            $stmt->execute([$id]);
            $st = $stmt->fetch();

            $delSt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
            $delSt->execute([$id]);

            if ($st && $st['user_id']) {
                $delUs = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUs->execute([$st['user_id']]);
            }

            $pdo->commit();
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Служителят беше успешно изтрит от системата.', 'staff');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            redirectWithMessage('danger', 'Грешка при изтриване на служител: ' . $e->getMessage(), 'staff');
        }
    }
}
?>
