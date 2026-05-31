<?php
// dashboard.php - Главно работно табло за Web_Hospital_NBU (Модулен Контролер)
session_start();
require_once 'db.php';

// Проверка дали потребителят е влязъл
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Потребител';

// Извличане на информация за болницата
$hospitalStmt = $pdo->query("SELECT * FROM hospital_info LIMIT 1");
$hospital = $hospitalStmt->fetch() ?: ['name' => 'Университетска болница - НБУ', 'address' => 'ул. Монтевидео 21'];

// Инициализиране на променливи за известия
$alertType = $_SESSION['alert_type'] ?? '';
$alertMsg = $_SESSION['alert_msg'] ?? '';
unset($_SESSION['alert_type'], $_SESSION['alert_msg']);

// Инициализация на данни според ролята
$data = [];

try {
    // -------------------------------------------------------------
    // АДМИНИСТРАТОРСКИ ДАННИ
    // -------------------------------------------------------------
    if ($role === 'admin') {
        // Болница и директор
        $directorStmt = $pdo->query("SELECT * FROM director_info LIMIT 1");
        $data['director'] = $directorStmt->fetch() ?: ['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => ''];

        // Списък стаи
        $roomsStmt = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number");
        $data['rooms'] = $roomsStmt->fetchAll();

        // Списък отделения
        $deptsStmt = $pdo->query("SELECT d.*, doc.first_name as doc_first, doc.last_name as doc_last FROM departments d LEFT JOIN doctors doc ON d.head_doctor_id = doc.id ORDER BY d.name");
        $data['departments'] = $deptsStmt->fetchAll();

        // Списък лекари
        $docsStmt = $pdo->query("SELECT d.*, dept.name as dept_name, u.username FROM doctors d LEFT JOIN departments dept ON d.department_id = dept.id LEFT JOIN users u ON d.user_id = u.id ORDER BY d.first_name");
        $data['doctors'] = $docsStmt->fetchAll();

        // Списък пациенти
        $patsStmt = $pdo->query("SELECT p.*, d.first_name as doc_first, d.last_name as doc_last, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id ORDER BY p.status, p.admission_date DESC");
        $data['patients'] = $patsStmt->fetchAll();

        // Списък друг персонал
        $staffStmt = $pdo->query("SELECT s.*, dept.name as dept_name, u.username FROM staff s LEFT JOIN departments dept ON s.department_id = dept.id LEFT JOIN users u ON s.user_id = u.id ORDER BY s.role, s.first_name");
        $data['staff'] = $staffStmt->fetchAll();

        // Списък дежурства
        $shiftsStmt = $pdo->query("SELECT s.*, doc.first_name, doc.last_name, dept.name as dept_name FROM doctor_shifts s JOIN doctors doc ON s.doctor_id = doc.id LEFT JOIN departments dept ON doc.department_id = dept.id ORDER BY s.shift_date DESC, s.shift_type");
        $data['shifts'] = $shiftsStmt->fetchAll();

        // Списък потребители
        $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role, username");
        $data['users'] = $usersStmt->fetchAll();

        // Статистики за администратора
        // Разпределение на пациенти по отделения
        $statsDeptStmt = $pdo->query("SELECT dept.name, COUNT(p.id) as count FROM departments dept LEFT JOIN patients p ON p.department_id = dept.id GROUP BY dept.id");
        $data['stats_departments'] = $statsDeptStmt->fetchAll();

        // Разпределение на пациенти по лекари
        $statsDocStmt = $pdo->query("SELECT CONCAT(doc.first_name, ' ', doc.last_name) as doc_name, COUNT(p.id) as count FROM doctors doc LEFT JOIN patients p ON p.doctor_id = doc.id GROUP BY doc.id");
        $data['stats_doctors'] = $statsDocStmt->fetchAll();

        // Общо приети срещу излекувани
        $statsStatusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM patients GROUP BY status");
        $data['stats_status'] = $statsStatusStmt->fetchAll();
    }

    // -------------------------------------------------------------
    // ДИРЕКТОРСКИ ДАННИ
    // -------------------------------------------------------------
    elseif ($role === 'director') {
        // Директорът вижда всичко за отделения, лекари, сестри, пациенти и персонал
        $data['departments'] = $pdo->query("SELECT d.*, doc.first_name as doc_first, doc.last_name as doc_last FROM departments d LEFT JOIN doctors doc ON d.head_doctor_id = doc.id ORDER BY d.name")->fetchAll();
        $data['doctors'] = $pdo->query("SELECT d.*, dept.name as dept_name FROM doctors d LEFT JOIN departments dept ON d.department_id = dept.id ORDER BY d.first_name")->fetchAll();
        $data['patients'] = $pdo->query("SELECT p.*, d.first_name as doc_first, d.last_name as doc_last, dept.name as dept_name, r.room_number FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id ORDER BY p.status, p.admission_date DESC")->fetchAll();
        $data['staff'] = $pdo->query("SELECT s.*, dept.name as dept_name FROM staff s LEFT JOIN departments dept ON s.department_id = dept.id ORDER BY s.role, s.first_name")->fetchAll();
        $data['rooms'] = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();

        // Филтър за статистика
        $filterDeptId = $_GET['filter_dept_id'] ?? null;
        $filterDocId = $_GET['filter_doc_id'] ?? null;

        // Статистика на приети/излекувани пациенти
        $statsQuery = "SELECT 
            SUM(CASE WHEN status = 'admitted' THEN 1 ELSE 0 END) as admitted_count,
            SUM(CASE WHEN status = 'cured' THEN 1 ELSE 0 END) as cured_count,
            COUNT(*) as total_count 
            FROM patients WHERE 1=1";
        
        $params = [];
        if ($filterDeptId) {
            $statsQuery .= " AND department_id = ?";
            $params[] = $filterDeptId;
        }
        if ($filterDocId) {
            $statsQuery .= " AND doctor_id = ?";
            $params[] = $filterDocId;
        }

        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute($params);
        $data['stats'] = $statsStmt->fetch() ?: ['admitted_count' => 0, 'cured_count' => 0, 'total_count' => 0];
    }

    // -------------------------------------------------------------
    // ЛЕКАРСКИ ДАННИ
    // -------------------------------------------------------------
    elseif ($role === 'doctor') {
        $doctorId = $_SESSION['doctor_id'] ?? null;

        if ($doctorId) {
            // Пациенти, които лекува лично
            $patStmt = $pdo->prepare("SELECT p.*, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.doctor_id = ? ORDER BY p.status, p.admission_date DESC");
            $patStmt->execute([$doctorId]);
            $data['my_patients'] = $patStmt->fetchAll();

            // Личен график на дежурства
            $shStmt = $pdo->prepare("SELECT * FROM doctor_shifts WHERE doctor_id = ? AND shift_date >= CURRENT_DATE ORDER BY shift_date ASC");
            $shStmt->execute([$doctorId]);
            $data['my_shifts'] = $shStmt->fetchAll();

            // Стаи за настаняване
            $data['rooms'] = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();

            // Списък отделения
            $data['departments'] = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

            // Статистика за лекаря
            $curedCnt = 0;
            $admittedCnt = 0;
            foreach ($data['my_patients'] as $p) {
                if ($p['status'] === 'cured') $curedCnt++;
                else $admittedCnt++;
            }
            $data['stats'] = ['admitted' => $admittedCnt, 'cured' => $curedCnt, 'total' => count($data['my_patients'])];
        } else {
            redirectWithMessage('danger', 'Грешка: Не сте регистриран в таблицата с лекари. Свържете се с админ.');
        }
    }

    // -------------------------------------------------------------
    // МЕДИЦИНСКА СЕСТРА ДАННИ
    // -------------------------------------------------------------
    elseif ($role === 'nurse') {
        $staffId = $_SESSION['staff_id'] ?? null;
        
        // Вземаме отделението на сестрата
        $nurseStmt = $pdo->prepare("SELECT department_id FROM staff WHERE id = ?");
        $nurseStmt->execute([$staffId]);
        $nurse = $nurseStmt->fetch();
        $deptId = $nurse['department_id'] ?? null;

        if ($deptId) {
            // Пациенти в нейното отделение
            $patStmt = $pdo->prepare("SELECT p.*, doc.first_name as doc_first, doc.last_name as doc_last, r.room_number FROM patients p LEFT JOIN doctors doc ON p.doctor_id = doc.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.department_id = ? ORDER BY p.status, p.admission_date DESC");
            $patStmt->execute([$deptId]);
            $data['dept_patients'] = $patStmt->fetchAll();

            // Лекари в нейното отделение
            $docStmt = $pdo->prepare("SELECT d.* FROM doctors d WHERE d.department_id = ?");
            $docStmt->execute([$deptId]);
            $data['dept_doctors'] = $docStmt->fetchAll();

            // Дежурства в нейното отделение
            $shiftStmt = $pdo->prepare("SELECT s.*, doc.first_name, doc.last_name FROM doctor_shifts s JOIN doctors doc ON s.doctor_id = doc.id WHERE doc.department_id = ? ORDER BY s.shift_date DESC");
            $shiftStmt->execute([$deptId]);
            $data['dept_shifts'] = $shiftStmt->fetchAll();

            // Отделение име
            $dNameStmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $dNameStmt->execute([$deptId]);
            $data['department_name'] = $dNameStmt->fetchColumn();
        } else {
            $data['dept_patients'] = [];
            $data['dept_doctors'] = [];
            $data['dept_shifts'] = [];
            $data['department_name'] = 'Невъведено';
        }
    }

    // -------------------------------------------------------------
    // ПОДДРЪЖКА ДАННИ
    // -------------------------------------------------------------
    elseif ($role === 'maintenance') {
        // Статус на стаите
        $data['rooms'] = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();
    }

    // -------------------------------------------------------------
    // ПАЦИЕНТСКИ ДАННИ
    // -------------------------------------------------------------
    elseif ($role === 'patient') {
        // Личен картон
        $patStmt = $pdo->prepare("SELECT p.*, doc.first_name as doc_first, doc.last_name as doc_last, doc.phone as doc_phone, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN doctors doc ON p.doctor_id = doc.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.user_id = ?");
        $patStmt->execute([$userId]);
        $data['patient_record'] = $patStmt->fetch();
    }

} catch (PDOException $e) {
    die("Грешка при извличане на данни: " . $e->getMessage());
}

// -------------------------------------------------------------
// ВКЛЮЧВАНЕ НА МОДУЛНИТЕ ШАБЛОНИ (РЕНДЕРИРАНЕ)
// -------------------------------------------------------------

// 1. Общ хедър
include 'includes/header.php';

// 2. Странично меню (Sidebar)
include 'includes/sidebar.php';
?>

<!-- ГЛАВЕН КОНТЕНТ БЛОК -->
<main class="main-content">
    
    <!-- Системни Известия (Alerts) -->
    <?php if (!empty($alertMsg)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alertType); ?>" id="system-alert">
            <span>
                <?php echo $alertType === 'success' ? '✅' : '⚠️'; ?>
                <?php echo htmlspecialchars($alertMsg); ?>
            </span>
            <button class="modal-close" style="font-size:16px;" onclick="document.getElementById('system-alert').style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <?php
    // 3. Динамично включване на таблото според ролята
    $dashboardViews = [
        'admin' => 'includes/admin_dashboard.php',
        'director' => 'includes/director_dashboard.php',
        'doctor' => 'includes/doctor_dashboard.php',
        'nurse' => 'includes/nurse_dashboard.php',
        'maintenance' => 'includes/maintenance_dashboard.php',
        'patient' => 'includes/patient_dashboard.php'
    ];

    $viewPath = $dashboardViews[$role] ?? '';
    if (!empty($viewPath) && file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo "<div class='alert alert-danger'>Грешка: Неуспешно зареждане на таблото за роля '$role'.</div>";
    }
    ?>

</main>

<?php
// 4. Модални форми (поставени на дъното за по-добра UX/DOM производителност)
include 'includes/modals.php';

// 5. Затварящи тагове и скрипт контролери
include 'includes/footer.php';
?>
