<?php
require_once 'BaseController.php';

class DoctorController extends BaseController {

    public function addDoctor() {
        $this->requireRole('admin');
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
                $this->pdo->beginTransaction();
                $userIdCreated = null;

                if (!empty($username) && !empty($password)) {
                    $chk = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) {
                        throw new Exception('Потребителското име за лекаря вече съществува.');
                    }
                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'doctor')");
                    $insUser->execute([$username, $hPass]);
                    $userIdCreated = $this->pdo->lastInsertId();
                }

                $unique_doc_num = 'DOC' . rand(10000, 99999);
                $insDoc = $this->pdo->prepare("INSERT INTO doctors (user_id, unique_doc_number, first_name, last_name, phone, email, qualification, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insDoc->execute([$userIdCreated, $unique_doc_num, $first_name, $last_name, $phone, $email, $qualification, $department_id]);

                $this->pdo->commit();
                $this->redirectWithMessage('success', 'Лекарят беше добавен успешно!', 'doctors');
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
            }
        } else {
            $this->redirectWithMessage('danger', 'Моля, попълнете задължителните полета за лекаря.', 'doctors');
        }
    }

    public function editDoctor() {
        $this->requireRole('admin');
        $id = intval($_POST['doctor_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $qualification = trim($_POST['qualification']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone) && !empty($qualification)) {
            try {
                $stmt = $this->pdo->prepare("UPDATE doctors SET first_name = ?, last_name = ?, phone = ?, email = ?, qualification = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $email, $qualification, $department_id, $id]);
                $this->redirectWithMessage('success', 'Данните за лекаря бяха актуализирани!', 'doctors');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
            }
        }
    }

    public function deleteDoctor() {
        $this->requireRole('admin');
        $id = intval($_POST['doctor_id']);
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            $delDoc = $this->pdo->prepare("DELETE FROM doctors WHERE id = ?");
            $delDoc->execute([$id]);

            if ($doc && $doc['user_id']) {
                $delUser = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$doc['user_id']]);
            }

            $this->pdo->commit();
            $this->redirectWithMessage('success', 'Лекарят и неговият потребителски профил бяха успешно изтрити!', 'doctors');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->redirectWithMessage('danger', 'Не можете да изтриете лекаря, защото той има регистрирани пациенти или е ръководител на отделение.', 'doctors');
        }
    }
}
?>
