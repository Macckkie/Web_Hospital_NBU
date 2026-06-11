<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/patient_actions.php
if ($action === 'add_patient' || $action === 'edit_patient' || $action === 'delete_patient' || $action === 'cure_patient') {
    
    // Помощна функция за проверка дали лекар лекува този пациент
    $checkDocAccess = function($patientId, $currentDoctorId) use ($pdo) {
        $stmt = $pdo->prepare("SELECT doctor_id FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $pat = $stmt->fetch();
        return $pat && intval($pat['doctor_id']) === intval($currentDoctorId);
    };

    if ($action === 'add_patient') {
        // Само Администратор и Лекари могат да добавят пациенти
        if ($role !== 'admin' && $role !== 'doctor') {
            redirectWithMessage('danger', 'Нямате достъп за добавяне на пациент.');
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

        // Ако е лекар, автоматично задава себе си като лекуващ лекар
        if ($role === 'doctor') {
            $doctor_id = $_SESSION['doctor_id'];
        } else {
            $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($illness)) {
            try {
                $pdo->beginTransaction();
                $userIdCreated = null;

                // Акаунт за пациента при необходимост
                if (!empty($username) && !empty($password)) {
                    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk->execute([$username]);
                    if ($chk->fetch()) throw new Exception('Потребителското име за пациента вече съществува.');
                    
                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
                    $insUser->execute([$username, $hPass]);
                    $userIdCreated = $pdo->lastInsertId();
                }

                // Проверка на капацитета на стаята
                if ($room_id) {
                    $roomCheck = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM patients WHERE room_id = rooms.id AND status = 'admitted') as occupied FROM rooms WHERE id = ?");
                    $roomCheck->execute([$room_id]);
                    $roomData = $roomCheck->fetch();
                    if ($roomData && $roomData['occupied'] >= $roomData['capacity']) {
                        throw new Exception('Избраната стая вече е запълнена до максималния си капацитет.');
                    }
                }

                $unique_pat_num = 'PAT' . rand(10000, 99999);
                $admission_date = date('Y-m-d');

                $insPat = $pdo->prepare("INSERT INTO patients (user_id, unique_patient_number, first_name, last_name, phone, email, illness, treatment, doctor_id, department_id, room_id, admission_date, status, treatment_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admitted', ?)");
                $insPat->execute([$userIdCreated, $unique_pat_num, $first_name, $last_name, $phone, $email, $illness, $treatment, $doctor_id, $department_id, $room_id, $admission_date, $treatment_cost]);

                $pdo->commit();
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Пациентът беше регистриран успешно в болницата!', 'patients');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'patients');
            }
        } else {
            redirectWithMessage('danger', 'Моля, попълнете задължителните полета за пациента.', 'patients');
        }
    }

    if ($action === 'edit_patient') {
        $id = intval($_POST['patient_id']);
        
        // Ако потребителят е лекар, той може да редактира само пациентите, които лекува
        if ($role === 'doctor') {
            if (!$checkDocAccess($id, $_SESSION['doctor_id'])) {
                redirectWithMessage('danger', 'Нямате права да редактирате пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($role !== 'admin') {
            redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
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

        // Ако е админ, позволяваме промяна на лекаря
        if ($role === 'admin') {
            $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
        } else {
            // Лекарят запазва себе си
            $doctor_id = $_SESSION['doctor_id'];
        }

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone) && !empty($illness)) {
            try {
                $stmt = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, phone = ?, email = ?, illness = ?, treatment = ?, doctor_id = ?, department_id = ?, room_id = ?, treatment_cost = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $email, $illness, $treatment, $doctor_id, $department_id, $room_id, $treatment_cost, $id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Картонът на пациента беше актуализиран успешно!', 'patients');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка при актуализиране на пациента: ' . $e->getMessage(), 'patients');
            }
        }
    }

    if ($action === 'delete_patient') {
        $id = intval($_POST['patient_id']);

        if ($role === 'doctor') {
            if (!$checkDocAccess($id, $_SESSION['doctor_id'])) {
                redirectWithMessage('danger', 'Нямате права да изтриете пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($role !== 'admin') {
            redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
        }

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $pat = $stmt->fetch();

            $delPat = $pdo->prepare("DELETE FROM patients WHERE id = ?");
            $delPat->execute([$id]);

            if ($pat && $pat['user_id']) {
                $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$pat['user_id']]);
            }

            $pdo->commit();
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Пациентът беше изписан и изтрит успешно от архива!', 'patients');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            redirectWithMessage('danger', 'Грешка при изтриване на пациента: ' . $e->getMessage(), 'patients');
        }
    }

    // Операция "Излекуван" (Cured / Discharge)
    if ($action === 'cure_patient') {
        $id = intval($_POST['patient_id']);

        if ($role === 'doctor') {
            if (!$checkDocAccess($id, $_SESSION['doctor_id'])) {
                redirectWithMessage('danger', 'Нямате права да изпишете пациент, който не лекувате лично.', 'patients');
            }
        } elseif ($role !== 'admin') {
            redirectWithMessage('danger', 'Нямате достъп за тази операция.', 'patients');
        }

        try {
            $discharge_date = date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE patients SET status = 'cured', discharge_date = ? WHERE id = ?");
            $stmt->execute([$discharge_date, $id]);
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Пациентът беше успешно маркиран като излекуван и изписан от днес!', 'patients');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Грешка при изписване на пациента: ' . $e->getMessage(), 'patients');
        }
    }
}
?>
