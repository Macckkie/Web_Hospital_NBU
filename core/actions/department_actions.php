<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/department_actions.php
if ($action === 'add_department' || $action === 'edit_department' || $action === 'delete_department') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_department') {
        $name = trim($_POST['name']);
        $head_doctor_id = !empty($_POST['head_doctor_id']) ? intval($_POST['head_doctor_id']) : null;

        if (!empty($name)) {
            try {
                // Валидация: Един лекар може да бъде ръководител само на едно отделение
                if ($head_doctor_id) {
                    $check = $pdo->prepare("SELECT id, name FROM departments WHERE head_doctor_id = ?");
                    $check->execute([$head_doctor_id]);
                    $otherDept = $check->fetch();
                    if ($otherDept) {
                        redirectWithMessage('danger', 'Този лекар вече е ръководител на отделение: ' . htmlspecialchars($otherDept['name']), 'departments');
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO departments (name, head_doctor_id) VALUES (?, ?)");
                $stmt->execute([$name, $head_doctor_id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Отделението беше добавено успешно!', 'departments');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'departments');
            }
        }
    }

    if ($action === 'edit_department') {
        $id = intval($_POST['department_id']);
        $name = trim($_POST['name']);
        $head_doctor_id = !empty($_POST['head_doctor_id']) ? intval($_POST['head_doctor_id']) : null;

        if ($id > 0 && !empty($name)) {
            try {
                // Валидация: Един лекар може да бъде ръководител само на едно отделение
                if ($head_doctor_id) {
                    $check = $pdo->prepare("SELECT id, name FROM departments WHERE head_doctor_id = ? AND id != ?");
                    $check->execute([$head_doctor_id, $id]);
                    $otherDept = $check->fetch();
                    if ($otherDept) {
                        redirectWithMessage('danger', 'Този лекар вече е ръководител на друго отделение: ' . htmlspecialchars($otherDept['name']), 'departments');
                    }
                }

                $stmt = $pdo->prepare("UPDATE departments SET name = ?, head_doctor_id = ? WHERE id = ?");
                $stmt->execute([$name, $head_doctor_id, $id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Отделението беше актуализирано успешно!', 'departments');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'departments');
            }
        }
    }

    if ($action === 'delete_department') {
        $id = intval($_POST['department_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Отделението беше изтрито успешно!', 'departments');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Не можете да изтриете отделение, в което има назначени лекари или настанени пациенти.', 'departments');
        }
    }
}
?>
