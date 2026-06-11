<?php
/** @var PDO $pdo */
// controllers/BaseController.php

class BaseController {
    protected $pdo;
    protected $role;
    protected $userId;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->role = $_SESSION['role'] ?? '';
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    protected function redirectWithMessage($type, $msg, $anchor = '') {
        $_SESSION['alert_type'] = $type;
        $_SESSION['alert_msg'] = $msg;
        header("Location: dashboard.php" . ($anchor ? "#$anchor" : ""));
        exit();
    }

    protected function requireRole($requiredRole) {
        if ($this->role !== $requiredRole) {
            $this->redirectWithMessage('danger', 'Нямате достъп до тази операция.');
        }
    }
    
    protected function requireRoles($rolesArray) {
        if (!in_array($this->role, $rolesArray)) {
            $this->redirectWithMessage('danger', 'Нямате достъп до тази операция.');
        }
    }
}
?>
