<?php
// actions.php - Контролер за обработка на всички CRUD операции за Web_Hospital_NBU
session_start();
require_once 'db.php';

// Проверка за оторизация
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Помощна функция за съобщения
function redirectWithMessage($type, $msg, $anchor = '') {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_msg'] = $msg;
    header("Location: dashboard.php" . ($anchor ? "#$anchor" : ""));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. АДМИНИСТРАТОР: Промяна на данни за БОЛНИЦАТА
    // ==========================================
    if ($action === 'edit_hospital') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        $name = trim($_POST['name']);
        $address = trim($_POST['address']);

        if (!empty($name) && !empty($address)) {
            try {
                // Проверяваме дали има запис в hospital_info
                $stmt = $pdo->query("SELECT id FROM hospital_info LIMIT 1");
                $exists = $stmt->fetch();

                if ($exists) {
                    $update = $pdo->prepare("UPDATE hospital_info SET name = ?, address = ? WHERE id = ?");
                    $update->execute([$name, $address, $exists['id']]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO hospital_info (name, address) VALUES (?, ?)");
                    $insert->execute([$name, $address]);
                }
                redirectWithMessage('success', 'Данните за болницата бяха успешно актуализирани!', 'hospital');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'hospital');
            }
        } else {
            redirectWithMessage('danger', 'Моля, попълнете всички полета за болницата.', 'hospital');
        }
    }

    // ==========================================
    // 2. АДМИНИСТРАТОР: Промяна на данни за ДИРЕКТОРА
    // ==========================================
    if ($action === 'edit_director') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($email)) {
            try {
                $stmt = $pdo->query("SELECT id FROM director_info LIMIT 1");
                $exists = $stmt->fetch();

                if ($exists) {
                    $update = $pdo->prepare("UPDATE director_info SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
                    $update->execute([$first_name, $last_name, $phone, $email, $exists['id']]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO director_info (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)");
                    $insert->execute([$first_name, $last_name, $phone, $email]);
                }
                redirectWithMessage('success', 'Данните за директора бяха успешно актуализирани!', 'director');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'director');
            }
        } else {
            redirectWithMessage('danger', 'Моля, попълнете всички полета за директора.', 'director');
        }
    }

    // ==========================================
    // 3. АДМИНИСТРАТОР: CRUD за СТАИ
    // ==========================================
    if ($action === 'add_room' || $action === 'edit_room' || $action === 'delete_room') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        if ($action === 'add_room') {
            $room_number = trim($_POST['room_number']);
            $type = $_POST['type'];
            $capacity = intval($_POST['capacity']);
            $price = floatval($_POST['price_per_day']);

            if (!empty($room_number) && $capacity > 0 && $price >= 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type, capacity, price_per_day) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$room_number, $type, $capacity, $price]);
                    redirectWithMessage('success', 'Стаята беше добавена успешно!', 'rooms');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка: Стаята с този номер вероятно вече съществува.', 'rooms');
                }
            } else {
                redirectWithMessage('danger', 'Невалидни данни за стаята.', 'rooms');
            }
        }

        if ($action === 'edit_room') {
            $id = intval($_POST['room_id']);
            $room_number = trim($_POST['room_number']);
            $type = $_POST['type'];
            $capacity = intval($_POST['capacity']);
            $price = floatval($_POST['price_per_day']);

            if ($id > 0 && !empty($room_number) && $capacity > 0 && $price >= 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, type = ?, capacity = ?, price_per_day = ? WHERE id = ?");
                    $stmt->execute([$room_number, $type, $capacity, $price, $id]);
                    redirectWithMessage('success', 'Стаята беше актуализирана успешно!', 'rooms');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка при актуализиране на стаята.', 'rooms');
                }
            }
        }

        if ($action === 'delete_room') {
            $id = intval($_POST['room_id']);
            try {
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$id]);
                redirectWithMessage('success', 'Стаята беше изтрита успешно!', 'rooms');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Не можете да изтриете стая, в която в момента има настанени пациенти.', 'rooms');
            }
        }
    }

    // ==========================================
    // 4. АДМИНИСТРАТОР: CRUD за ОТДЕЛЕНИЯ
    // ==========================================
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
                redirectWithMessage('success', 'Отделението беше изтрито успешно!', 'departments');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Не можете да изтриете отделение, в което има назначени лекари или настанени пациенти.', 'departments');
            }
        }
    }

    // ==========================================
    // 5. АДМИНИСТРАТОР: CRUD за ЛЕКАРИ
    // ==========================================
    if ($action === 'add_doctor' || $action === 'edit_doctor' || $action === 'delete_doctor') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        if ($action === 'add_doctor') {
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
                    $pdo->beginTransaction();
                    $userIdCreated = null;

                    // Ако е предоставено потребителско име, създаваме акаунт
                    if (!empty($username) && !empty($password)) {
                        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $chk->execute([$username]);
                        if ($chk->fetch()) {
                            throw new Exception('Потребителското име за лекаря вече съществува.');
                        }
                        $hPass = password_hash($password, PASSWORD_DEFAULT);
                        $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'doctor')");
                        $insUser->execute([$username, $hPass]);
                        $userIdCreated = $pdo->lastInsertId();
                    }

                    $unique_doc_num = 'DOC' . rand(10000, 99999);
                    $insDoc = $pdo->prepare("INSERT INTO doctors (user_id, unique_doc_number, first_name, last_name, phone, email, qualification, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insDoc->execute([$userIdCreated, $unique_doc_num, $first_name, $last_name, $phone, $email, $qualification, $department_id]);

                    $pdo->commit();
                    redirectWithMessage('success', 'Лекарят беше добавен успешно!', 'doctors');
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
                }
            } else {
                redirectWithMessage('danger', 'Моля, попълнете задължителните полета за лекаря.', 'doctors');
            }
        }

        if ($action === 'edit_doctor') {
            $id = intval($_POST['doctor_id']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $qualification = trim($_POST['qualification']);
            $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

            if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone) && !empty($qualification)) {
                try {
                    $stmt = $pdo->prepare("UPDATE doctors SET first_name = ?, last_name = ?, phone = ?, email = ?, qualification = ?, department_id = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $phone, $email, $qualification, $department_id, $id]);
                    redirectWithMessage('success', 'Данните за лекаря бяха актуализирани!', 'doctors');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'doctors');
                }
            }
        }

        if ($action === 'delete_doctor') {
            $id = intval($_POST['doctor_id']);
            try {
                $pdo->beginTransaction();
                
                // Вземаме user_id, за да изтрием и акаунта
                $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
                $stmt->execute([$id]);
                $doc = $stmt->fetch();

                // Изтриваме лекаря
                $delDoc = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
                $delDoc->execute([$id]);

                // Изтриваме потребителя
                if ($doc && $doc['user_id']) {
                    $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $delUser->execute([$doc['user_id']]);
                }

                $pdo->commit();
                redirectWithMessage('success', 'Лекарят и неговият потребителски профил бяха успешно изтрити!', 'doctors');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                redirectWithMessage('danger', 'Не можете да изтриете лекаря, защото той има регистрирани пациенти или е ръководител на отделение.', 'doctors');
            }
        }
    }

    // ==========================================
    // 6. АДМИНИСТРАТОР И ЛЕКАР: CRUD за ПАЦИЕНТИ
    // ==========================================
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
                redirectWithMessage('success', 'Пациентът беше успешно маркиран като излекуван и изписан от днес!', 'patients');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка при изписване на пациента: ' . $e->getMessage(), 'patients');
            }
        }
    }

    // ==========================================
    // 7. АДМИНИСТРАТОР: CRUD за ДРУГ ПЕРСОНАЛ (Сестри и Поддръжка)
    // ==========================================
    if ($action === 'add_staff' || $action === 'edit_staff' || $action === 'delete_staff') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        if ($action === 'add_staff') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $staff_role = $_POST['staff_role']; // nurse / maintenance
            $phone = trim($_POST['phone']);
            $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            if (!empty($first_name) && !empty($last_name) && !empty($phone)) {
                try {
                    $pdo->beginTransaction();
                    $userIdCreated = null;

                    if (!empty($username) && !empty($password)) {
                        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $chk->execute([$username]);
                        if ($chk->fetch()) throw new Exception('Потребителското име вече е заето.');

                        $hPass = password_hash($password, PASSWORD_DEFAULT);
                        $insUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                        $insUser->execute([$username, $hPass, $staff_role]);
                        $userIdCreated = $pdo->lastInsertId();
                    }

                    $insStaff = $pdo->prepare("INSERT INTO staff (user_id, first_name, last_name, role, phone, department_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $insStaff->execute([$userIdCreated, $first_name, $last_name, $staff_role, $phone, $department_id]);

                    $pdo->commit();
                    redirectWithMessage('success', 'Служителят беше добавен успешно!', 'staff');
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
                }
            }
        }

        if ($action === 'edit_staff') {
            $id = intval($_POST['staff_id']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $phone = trim($_POST['phone']);
            $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

            if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($phone)) {
                try {
                    $stmt = $pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, phone = ?, department_id = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $phone, $department_id, $id]);
                    redirectWithMessage('success', 'Данните за служителя бяха актуализирани!', 'staff');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'staff');
                }
            }
        }

        if ($action === 'delete_staff') {
            $id = intval($_POST['staff_id']);
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
                $stmt->execute([$id]);
                $st = $stmt->fetch();

                $delSt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
                $delSt->execute([$id]);

                if ($st && $st['user_id']) {
                    $delUs = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $delUs->execute([$st['user_id']]);
                }

                $pdo->commit();
                redirectWithMessage('success', 'Служителят беше успешно изтрит от системата.', 'staff');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                redirectWithMessage('danger', 'Грешка при изтриване на служител: ' . $e->getMessage(), 'staff');
            }
        }
    }

    // ==========================================
    // 8. АДМИНИСТРАТОР: ГРАФИК ЗА ДЕЖУРСТВА
    // ==========================================
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
                redirectWithMessage('success', 'Дежурството беше премахнато от графика.', 'shifts');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'shifts');
            }
        }
    }

    // ==========================================
    // 9. АДМИНИСТРАТОР: CRUD за ПОТРЕБИТЕЛИ
    // ==========================================
    if ($action === 'add_user' || $action === 'edit_user' || $action === 'delete_user') {
        if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

        if ($action === 'add_user') {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $user_role = $_POST['user_role'];

            if (!empty($username) && !empty($password)) {
                try {
                    $hPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $hPass, $user_role]);
                    redirectWithMessage('success', 'Потребителят беше добавен успешно в базата!', 'users');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка: Потребителското име вече съществува.', 'users');
                }
            }
        }

        if ($action === 'edit_user') {
            $id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $user_role = $_POST['user_role'];
            $new_password = trim($_POST['password']);

            if ($id > 0 && !empty($username)) {
                try {
                    if (!empty($new_password)) {
                        $hPass = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $hPass, $user_role, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $user_role, $id]);
                    }
                    redirectWithMessage('success', 'Потребителският акаунт беше актуализиран!', 'users');
                } catch (Exception $e) {
                    redirectWithMessage('danger', 'Грешка: Потребителското име вече се ползва.', 'users');
                }
            }
        }

        if ($action === 'delete_user') {
            $id = intval($_POST['user_id']);
            if ($id === $userId) {
                redirectWithMessage('danger', 'Не можете да изтриете собствения си акаунт в момента!', 'users');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                redirectWithMessage('success', 'Потребителят беше успешно изтрит.', 'users');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Този потребител е свързан с медицинско лице или пациент. Първо изтрийте съответния лекар/пациент.', 'users');
            }
        }
    }
}

// Ако няма POST заявка, пренасочваме обратно
header("Location: dashboard.php");
exit();
?>
