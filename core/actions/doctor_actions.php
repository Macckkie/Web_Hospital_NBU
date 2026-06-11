<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/doctor_actions.php
if ($action === 'add_doctor' || $action === 'edit_doctor' || $action === 'delete_doctor') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_doctor') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $qualification = trim($_POST['qualification']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($qualification)) {
            try {
                $pdo->beginTransaction();
                $userIdCreated = null;

                // Ако е предоставено потребителско име, създаваме акаунт
                if (!empty($username) && !empty($password)) {
                    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) {
                        throw new Exception('Потребителското име за лекаря вече съществува.');
                    }
                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'doctor')");
                    $insUser->execute([$username, $hPass]);
                    $userIdCreated = $pdo->lastInsertId();
                }

                $unique_doc_num = 'DOC' . rand(10000, 99999);
                $insDoc = $pdo->prepare("INSERT INTO doctors (user_id, unique_doc_number, first_name, last_name, phone, email, qualification, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insDoc->execute([$userIdCreated, $unique_doc_num, $first_name, $last_name, $phone, $email, $qualification, $department_id]);

                $pdo->commit();
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Лекарят беше добавен успешно!', 'doctors');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
            }
        } else {
            redirectWithMessage('danger', 'Моля, попълнете задължителните полета за лекаря.', 'doctors');
        }
    }

    if ($action === 'edit_doctor') {
        $id = intval($_POST['doctor_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $qualification = trim($_POST['qualification']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone) && !empty($qualification)) {
            try {
                $stmt = $pdo->prepare("UPDATE doctors SET first_name = ?, last_name = ?, phone = ?, email = ?, qualification = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $email, $qualification, $department_id, $id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Данните за лекаря бяха актуализирани!', 'doctors');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
            }
        }
    }

    if ($action === 'delete_doctor') {
        $id = intval($_POST['doctor_id']);
        try {
            $pdo->beginTransaction();
            
            // Вземаме user_id, за да изтрием и акаунта
            $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            // Изтриваме лекаря
            $delDoc = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
            $delDoc->execute([$id]);

            // Изтриваме потребителя
            if ($doc && $doc['user_id']) {
                $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$doc['user_id']]);
            }

            $pdo->commit();
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Лекарят и неговият потребителски профил бяха успешно изтрити!', 'doctors');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            redirectWithMessage('danger', 'Не можете да изтриете лекаря, защото той има регистрирани пациенти или е ръководител на отделение.', 'doctors');
        }
    }
}
?>
