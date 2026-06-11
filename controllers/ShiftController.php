<?php
require_once 'BaseController.php';

class ShiftController extends BaseController {

    public function addShift() {
        $this->requireRole('admin');
        $doctor_id = intval($_POST['doctor_id']);
        $shift_date = $_POST['shift_date'];
        $shift_type = $_POST['shift_type'];

        if ($doctor_id > 0 && !empty($shift_date) && !empty($shift_type)) {
            try {
                $check = $this->pdo->prepare("SELECT id FROM doctor_shifts WHERE doctor_id = ? AND shift_date = ?");
                $check->execute([$doctor_id, $shift_date]);
                if ($check->fetch()) {
                    $this->redirectWithMessage('danger', 'Този лекар вече има назначено дежурство за избраната дата!', 'shifts');
                }

                $stmt = $this->pdo->prepare("INSERT INTO doctor_shifts (doctor_id, shift_date, shift_type) VALUES (?, ?, ?)");
                $stmt->execute([$doctor_id, $shift_date, $shift_type]);
                $this->redirectWithMessage('success', 'Дежурството беше добавено успешно!', 'shifts');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'shifts');
            }
        }
    }

    public function deleteShift() {
        $this->requireRole('admin');
        $id = intval($_POST['shift_id']);
        try {
            $stmt = $this->pdo->prepare("DELETE FROM doctor_shifts WHERE id = ?");
            $stmt->execute([$id]);
            $this->redirectWithMessage('success', 'Дежурството беше премахнато от графика.', 'shifts');
        } catch (Exception $e) {
            $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'shifts');
        }
    }
}
?>
