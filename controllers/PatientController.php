<?php
require_once 'BaseController.php';

class PatientController extends BaseController {

    private function checkDocAccess($patientId, $currentDoctorId) {
        $stmt = $this->pdo->prepare("SELECT doctor_id FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $pat = $stmt->fetch();
        return $pat && intval($pat['doctor_id']) === intval($currentDoctorId);
    }

    public function addPatient() {
        $this->requireRoles(['admin', 'doctor']);

        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $illness = trim($_POST['illness']);
        $treatment = trim($_POST['treatment']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $treatment_cost = floatval($_POST['treatment_cost'] ?? 0.00);

        if ($this->role === 'doctor') {
            $doctor_id = $_SESSION['doctor_id'];
        } else {
            $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($illness)) {
            try {
                $this->pdo->beginTransaction();
                $userIdCreated = null;

                if (!empty($username) && !empty($password)) {
                    $chk = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) throw new Exception('Потребителското име за пациента вече съществува.');
                    
                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
                    $insUser->execute([$username, $hPass]);
                    $userIdCreated = $this->pdo->lastInsertId();
                }

                if ($room_id) {
                    $roomCheck = $this->pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM patients WHERE room_id = rooms.id AND status = 'admitted') as occupied FROM rooms WHERE id = ?");
                    $roomCheck->execute([$room_id]);
                    $roomData = $roomCheck->fetch();
                    if ($roomData && $roomData['occupied'] >= $roomData['capacity']) {
                        throw new Exception('Избраната стая вече е запълнена до максималния си капацитет.');
                    }
                }

                $unique_pat_num = 'PAT' . rand(10000, 99999);
                $admission_date = date('Y-m-d');

                $insPat = $this->pdo->prepare("INSERT INTO patients (user_id, unique_patient_number, first_name, last_name, phone, email, illness, treatment, doctor_id, department_id, room_id, admission_date, status, treatment_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admitted', ?)");
                $insPat->execute([$userIdCreated, $unique_pat_num, $first_name, $last_name, $phone, $email, $illness, $treatment, $doctor_id, $department_id, $room_id, $admission_date, $treatment_cost]);

                $this->pdo->commit();
                $this->redirectWithMessage('success', 'Пациентът беше регистриран успешно в болницата!', 'patients');
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'patients');
            }
        } else {
            $this->redirectWithMessage('danger', 'Моля, попълнете задължителните полета за пациента.', 'patients');
        }
    }

    public function editPatient() {
        $id = intval($_POST['patient_id']);
        
        if ($this->role === 'doctor') {
            if (!$this->checkDocAccess($id, $_SESSION['doctor_id'])) {
                $this->redirectWithMessage('danger', 'Нямате права да редактирате пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($this->role !== 'admin') {
            $this->redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
        }

        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $illness = trim($_POST['illness']);
        $treatment = trim($_POST['treatment']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $treatment_cost = floatval($_POST['treatment_cost'] ?? 0.00);

        if ($this->role === 'admin') {
            $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
        } else {
            $doctor_id = $_SESSION['doctor_id'];
        }

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone) && !empty($illness)) {
            try {
                $stmt = $this->pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, phone = ?, email = ?, illness = ?, treatment = ?, doctor_id = ?, department_id = ?, room_id = ?, treatment_cost = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $email, $illness, $treatment, $doctor_id, $department_id, $room_id, $treatment_cost, $id]);
                $this->redirectWithMessage('success', 'Картонът на пациента беше актуализиран успешно!', 'patients');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка при актуализиране на пациента: ' . $e->getMessage(), 'patients');
            }
        }
    }

    public function deletePatient() {
        $id = intval($_POST['patient_id']);

        if ($this->role === 'doctor') {
            if (!$this->checkDocAccess($id, $_SESSION['doctor_id'])) {
                $this->redirectWithMessage('danger', 'Нямате права да изтриете пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($this->role !== 'admin') {
            $this->redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
        }

        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $pat = $stmt->fetch();

            $delPat = $this->pdo->prepare("DELETE FROM patients WHERE id = ?");
            $delPat->execute([$id]);

            if ($pat && $pat['user_id']) {
                $delUser = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$pat['user_id']]);
            }

            $this->pdo->commit();
            $this->redirectWithMessage('success', 'Пациентът беше изписан и изтрит успешно от архива!', 'patients');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->redirectWithMessage('danger', 'Грешка при изтриване на пациента: ' . $e->getMessage(), 'patients');
        }
    }

    public function curePatient() {
        $id = intval($_POST['patient_id']);

        if ($this->role === 'doctor') {
            if (!$this->checkDocAccess($id, $_SESSION['doctor_id'])) {
                $this->redirectWithMessage('danger', 'Нямате права да изпишете пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($this->role !== 'admin') {
            $this->redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
        }

        try {
            $discharge_date = date('Y-m-d');
            $stmt = $this->pdo->prepare("UPDATE patients SET status = 'cured', discharge_date = ? WHERE id = ?");
            $stmt->execute([$discharge_date, $id]);
            $this->redirectWithMessage('success', 'Пациентът беше успешно маркиран като излекуван и изписан от днес!', 'patients');
        } catch (Exception $e) {
            $this->redirectWithMessage('danger', 'Грешка при изписване на пациента: ' . $e->getMessage(), 'patients');
        }
    }
}
?>
