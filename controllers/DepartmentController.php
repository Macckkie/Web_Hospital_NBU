<?php
require_once 'BaseController.php';

class DepartmentController extends BaseController {

    public function addDepartment() {
        $this->requireRole('admin');
        $name = trim($_POST['name']);
        $head_doctor_id = !empty($_POST['head_doctor_id']) ? intval($_POST['head_doctor_id']) : null;

        if (!empty($name)) {
            try {
                if ($head_doctor_id) {
                    $check = $this->pdo->prepare("SELECT id, name FROM departments WHERE head_doctor_id = ?");
                    $check->execute([$head_doctor_id]);
                    $otherDept = $check->fetch();
                    if ($otherDept) {
                        $this->redirectWithMessage('danger', 'Този лекар вече е ръководител на отделение: ' . htmlspecialchars($otherDept['name']), 'departments');
                    }
                }

                $stmt = $this->pdo->prepare("INSERT INTO departments (name, head_doctor_id) VALUES (?, ?)");
                $stmt->execute([$name, $head_doctor_id]);
                $this->redirectWithMessage('success', 'Отделението беше добавено успешно!', 'departments');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'departments');
            }
        }
    }

    public function editDepartment() {
        $this->requireRole('admin');
        $id = intval($_POST['department_id']);
        $name = trim($_POST['name']);
        $head_doctor_id = !empty($_POST['head_doctor_id']) ? intval($_POST['head_doctor_id']) : null;

        if ($id > 0 && !empty($name)) {
            try {
                if ($head_doctor_id) {
                    $check = $this->pdo->prepare("SELECT id, name FROM departments WHERE head_doctor_id = ? AND id != ?");
                    $check->execute([$head_doctor_id, $id]);
                    $otherDept = $check->fetch();
                    if ($otherDept) {
                        $this->redirectWithMessage('danger', 'Този лекар вече е ръководител на друго отделение: ' . htmlspecialchars($otherDept['name']), 'departments');
                    }
                }

                $stmt = $this->pdo->prepare("UPDATE departments SET name = ?, head_doctor_id = ? WHERE id = ?");
                $stmt->execute([$name, $head_doctor_id, $id]);
                $this->redirectWithMessage('success', 'Отделението беше актуализирано успешно!', 'departments');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'departments');
            }
        }
    }

    public function deleteDepartment() {
        $this->requireRole('admin');
        $id = intval($_POST['department_id']);
        try {
            $stmt = $this->pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            $this->redirectWithMessage('success', 'Отделението беше изтрито успешно!', 'departments');
        } catch (Exception $e) {
            $this->redirectWithMessage('danger', 'Не можете да изтриете отделение, в което има назначени лекари или настанени пациенти.', 'departments');
        }
    }
}
?>
