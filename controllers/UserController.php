<?php
require_once 'BaseController.php';

class UserController extends BaseController {

    public function addUser() {
        $this->requireRole('admin');
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $user_role = $_POST['user_role'];

        if (!empty($username) && !empty($password)) {
            try {
                $hPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hPass, $user_role]);
                $this->redirectWithMessage('success', 'Потребителят беше добавен успешно в базата!', 'users');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: Потребителското име вече съществува.', 'users');
            }
        }
    }

    public function editUser() {
        $this->requireRole('admin');
        $id = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $user_role = $_POST['user_role'];
        $new_password = trim($_POST['password']);

        if ($id > 0 && !empty($username)) {
            try {
                if (!empty($new_password)) {
                    $hPass = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $this->pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $hPass, $user_role, $id]);
                } else {
                    $stmt = $this->pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $user_role, $id]);
                }
                $this->redirectWithMessage('success', 'Потребителският акаунт беше актуализиран!', 'users');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: Потребителското име вече се ползва.', 'users');
            }
        }
    }

    public function deleteUser() {
        $this->requireRole('admin');
        $id = intval($_POST['user_id']);
        if ($id === $this->userId) {
            $this->redirectWithMessage('danger', 'Не можете да изтриете собствения си акаунт в момента!', 'users');
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $this->redirectWithMessage('success', 'Потребителят беше успешно изтрит.', 'users');
        } catch (Exception $e) {
            $this->redirectWithMessage('danger', 'Този потребител е свързан с медицинско лице или пациент. Първо изтрийте съответния лекар/пациент.', 'users');
        }
    }
}
?>
