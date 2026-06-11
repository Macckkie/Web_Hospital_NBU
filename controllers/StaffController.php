<?php
require_once 'BaseController.php';

class StaffController extends BaseController {

    public function addStaff() {
        $this->requireRole('admin');
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $staff_role = $_POST['staff_role']; // nurse / maintenance
        $phone = trim($_POST['phone']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!empty($first_name) && !empty($last_name) && !empty($phone)) {
            try {
                $this->pdo->beginTransaction();
                $userIdCreated = null;

                if (!empty($username) && !empty($password)) {
                    $chk = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) throw new Exception('Потребителското име вече е заето.');

                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $insUser->execute([$username, $hPass, $staff_role]);
                    $userIdCreated = $this->pdo->lastInsertId();
                }

                $insStaff = $this->pdo->prepare("INSERT INTO staff (user_id, first_name, last_name, role, phone, department_id) VALUES (?, ?, ?, ?, ?, ?)");
                $insStaff->execute([$userIdCreated, $first_name, $last_name, $staff_role, $phone, $department_id]);

                $this->pdo->commit();
                $this->redirectWithMessage('success', 'Служителят беше добавен успешно!', 'staff');
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
            }
        }
    }

    public function editStaff() {
        $this->requireRole('admin');
        $id = intval($_POST['staff_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone)) {
            try {
                $stmt = $this->pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, phone = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $department_id, $id]);
                $this->redirectWithMessage('success', 'Данните за служителя бяха актуализирани!', 'staff');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
            }
        }
    }

    public function deleteStaff() {
        $this->requireRole('admin');
        $id = intval($_POST['staff_id']);
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
            $stmt->execute([$id]);
            $st = $stmt->fetch();

            $delSt = $this->pdo->prepare("DELETE FROM staff WHERE id = ?");
            $delSt->execute([$id]);

            if ($st && $st['user_id']) {
                $delUs = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUs->execute([$st['user_id']]);
            }

            $this->pdo->commit();
            $this->redirectWithMessage('success', 'Служителят беше успешно изтрит от системата.', 'staff');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->redirectWithMessage('danger', 'Грешка при изтриване на служител: ' . $e->getMessage(), 'staff');
        }
    }
}
?>
