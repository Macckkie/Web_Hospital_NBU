<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/shift_actions.php
if ($action === 'add_shift' || $action === 'delete_shift') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_shift') {
        $doctor_id = intval($_POST['doctor_id']);
        $shift_date = $_POST['shift_date'];
        $shift_type = $_POST['shift_type'];

        if ($doctor_id > 0 && !empty($shift_date) && !empty($shift_type)) {
            try {
                // Проверка дали лекарят вече има дежурство на тази дата
                $check = $pdo->prepare("SELECT id FROM doctor_shifts WHERE doctor_id = ? AND shift_date = ?");
                $check->execute([$doctor_id, $shift_date]);
                if ($check->fetch()) {
                    redirectWithMessage('danger', 'Този лекар вече има назначено дежурство за избраната дата!', 'shifts');
                }

                $stmt = $pdo->prepare("INSERT INTO doctor_shifts (doctor_id, shift_date, shift_type) VALUES (?, ?, ?)");
                $stmt->execute([$doctor_id, $shift_date, $shift_type]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Дежурството беше добавено успешно!', 'shifts');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'shifts');
            }
        }
    }

    if ($action === 'delete_shift') {
        $id = intval($_POST['shift_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM doctor_shifts WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Дежурството беше премахнато от графика.', 'shifts');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'shifts');
        }
    }
}
?>
