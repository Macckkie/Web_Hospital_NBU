<?php
// dashboard.php - Главно работно табло за Web_Hospital_NBU
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
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Табло - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        
        <!-- СТРАНИЧНО МЕНЮ (Sidebar) -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">🏥</div>
                <div class="sidebar-title">
                    Болница
                    <span><?php echo htmlspecialchars(mb_strimwidth($hospital['name'], 0, 18, "...")); ?></span>
                </div>
            </div>

            <div class="user-profile-badge">
                <div class="user-profile-name">👋 <?php echo htmlspecialchars($fullName); ?></div>
                <div class="user-profile-role">
                    <?php 
                    $roleNames = [
                        'admin' => 'Администратор',
                        'director' => 'Директор',
                        'doctor' => 'Лекар',
                        'nurse' => 'Мед. сестра',
                        'maintenance' => 'Поддръжка',
                        'patient' => 'Пациент'
                    ];
                    echo $roleNames[$role] ?? $role; 
                    ?>
                </div>
            </div>

            <nav style="flex-grow: 1;">
                <ul class="sidebar-menu">
                    <!-- Бутони за Администратор -->
                    <?php if ($role === 'admin'): ?>
                        <li class="sidebar-link active" data-target="dashboard-overview">📊 Статистика & Общ преглед</li>
                        <li class="sidebar-link" data-target="patients-sec">👥 Пациенти</li>
                        <li class="sidebar-link" data-target="doctors-sec">🩺 Лекари</li>
                        <li class="sidebar-link" data-target="staff-sec">👔 Персонал (Сестри/Поддръжка)</li>
                        <li class="sidebar-link" data-target="departments-sec">🏢 Отделения</li>
                        <li class="sidebar-link" data-target="rooms-sec">🚪 Стаи</li>
                        <li class="sidebar-link" data-target="shifts-sec">📅 Дежурства</li>
                        <li class="sidebar-link" data-target="users-sec">🔑 Потребители</li>
                        <li class="sidebar-link" data-target="hospital-sec">⚙️ Настройки Болница & Директор</li>
                    
                    <!-- Бутони за Директор -->
                    <?php elseif ($role === 'director'): ?>
                        <li class="sidebar-link active" data-target="director-stats">📊 Директорски Анализ</li>
                        <li class="sidebar-link" data-target="director-pats">👥 Всички Пациенти</li>
                        <li class="sidebar-link" data-target="director-docs">🩺 Всички Лекари</li>
                        <li class="sidebar-link" data-target="director-depts">🏢 Всички Отделения</li>
                        <li class="sidebar-link" data-target="director-staff">👔 Всички Служители</li>
                        <li class="sidebar-link" data-target="director-rooms">🚪 Болнични Стаи</li>

                    <!-- Бутони за Лекар -->
                    <?php elseif ($role === 'doctor'): ?>
                        <li class="sidebar-link active" data-target="doc-mypatients">👥 Моите Пациенти</li>
                        <li class="sidebar-link" data-target="doc-myshifts">📅 График дежурства</li>

                    <!-- Бутони за Сестра -->
                    <?php elseif ($role === 'nurse'): ?>
                        <li class="sidebar-link active" data-target="nurse-patients">👥 Пациенти в отделението</li>
                        <li class="sidebar-link" data-target="nurse-shifts">📅 Дежурства на лекарите</li>

                    <!-- Бутони за Поддръжка -->
                    <?php elseif ($role === 'maintenance'): ?>
                        <li class="sidebar-link active" data-target="maint-rooms">🚪 Статус и Заетост на Стаите</li>

                    <!-- Бутони за Пациент -->
                    <?php elseif ($role === 'patient'): ?>
                        <li class="sidebar-link active" data-target="patient-card">📄 Моят Болничен Картон</li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="sidebar-link" style="color: var(--danger-light); padding: 8px 16px;">
                    <svg viewBox="0 0 24 24" style="width:18px; height:18px; fill:currentColor; margin-right:8px;">
                        <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"/>
                    </svg>
                    Изход от системата
                </a>
            </div>
        </aside>

        <!-- ГЛАВЕН ПАНЕЛ (Main Content) -->
        <main class="main-content">
            
            <!-- Известия (Alerts) -->
            <?php if (!empty($alertMsg)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($alertType); ?>" id="system-alert">
                    <span>
                        <?php echo $alertType === 'success' ? '✅' : '⚠️'; ?>
                        <?php echo htmlspecialchars($alertMsg); ?>
                    </span>
                    <button class="modal-close" style="font-size:16px;" onclick="document.getElementById('system-alert').style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <!-- ========================================================================= -->
            <!-- 1. ИЗГЛЕД: АДМИНИСТРАТОР -->
            <!-- ========================================================================= -->
            <?php if ($role === 'admin'): ?>
                
                <!-- Таб: Статистика & Общ преглед -->
                <section id="dashboard-overview" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📊 Добре дошли в Административния панел</h1>
                            <p class="page-subtitle">Тук можете да управлявате цялостната информация за болничната база</p>
                        </div>
                    </div>

                    <!-- Статистически Карти -->
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Регистрирани Пациенти</span>
                                <span class="stats-value"><?php echo count($data['patients']); ?></span>
                            </div>
                            <div class="stats-icon primary">👥</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Активни Лекари</span>
                                <span class="stats-value"><?php echo count($data['doctors']); ?></span>
                            </div>
                            <div class="stats-icon accent">🩺</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Общо Стаи</span>
                                <span class="stats-value"><?php echo count($data['rooms']); ?></span>
                            </div>
                            <div class="stats-icon warning">🚪</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Отделения</span>
                                <span class="stats-value"><?php echo count($data['departments']); ?></span>
                            </div>
                            <div class="stats-icon danger">🏢</div>
                        </div>
                    </div>

                    <!-- Интерактивни Графики (CSS/SVG) -->
                    <div class="stats-chart-container">
                        <!-- Разпределение по Отделения -->
                        <div class="chart-card">
                            <h3 class="chart-title">Разпределение на пациенти по отделения</h3>
                            <div class="bar-chart">
                                <?php 
                                $maxPats = 1;
                                foreach($data['stats_departments'] as $st) if ($st['count'] > $maxPats) $maxPats = $st['count'];
                                foreach($data['stats_departments'] as $st): 
                                    $pct = round(($st['count'] / $maxPats) * 100);
                                ?>
                                    <div class="bar-row">
                                        <div class="bar-label" title="<?php echo htmlspecialchars($st['name']); ?>"><?php echo htmlspecialchars($st['name']); ?></div>
                                        <div class="bar-progress-wrap">
                                            <div class="bar-progress" style="width: <?php echo $pct; ?>%; background-color: var(--primary);"></div>
                                        </div>
                                        <div class="bar-value"><?php echo $st['count']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Разпределение по Лекари -->
                        <div class="chart-card">
                            <h3 class="chart-title">Пациенти на лекуващ лекар</h3>
                            <div class="bar-chart">
                                <?php 
                                $maxDocPats = 1;
                                foreach($data['stats_doctors'] as $st) if ($st['count'] > $maxDocPats) $maxDocPats = $st['count'];
                                foreach($data['stats_doctors'] as $st): 
                                    $pct = round(($st['count'] / $maxDocPats) * 100);
                                ?>
                                    <div class="bar-row">
                                        <div class="bar-label" title="<?php echo htmlspecialchars($st['doc_name']); ?>"><?php echo htmlspecialchars($st['doc_name']); ?></div>
                                        <div class="bar-progress-wrap">
                                            <div class="bar-progress" style="width: <?php echo $pct; ?>%; background-color: var(--accent);"></div>
                                        </div>
                                        <div class="bar-value"><?php echo $st['count']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Таб: Пациенти -->
                <section id="patients-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👥 Управление на Пациенти</h1>
                            <p class="page-subtitle">Въвеждане, редактиране, изписване и генериране на сметки за пациенти</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addPatientModal')">➕ Нов Пациент</button>
                    </div>

                    <div class="card-table-wrap">
                        <div class="table-header-toolbar">
                            <div class="search-input-wrap">
                                <span class="search-icon">🔍</span>
                                <input type="text" class="form-control search-control" placeholder="Търсене на пациент..." onkeyup="filterTable(this, 'patientsTable')">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table" id="patientsTable">
                                <thead>
                                    <tr>
                                        <th>Идентификатор</th>
                                        <th>Име</th>
                                        <th>Заболяване</th>
                                        <th>Лекар</th>
                                        <th>Отделение</th>
                                        <th>Стая (Тип)</th>
                                        <th>Престой</th>
                                        <th>Статус</th>
                                        <th style="text-align:right;">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['patients'] as $p): 
                                        $adm = new DateTime($p['admission_date']);
                                        $dis = $p['discharge_date'] ? new DateTime($p['discharge_date']) : new DateTime();
                                        $days = $adm->diff($dis)->days;
                                        if ($days == 0) $days = 1; // минимум 1 ден
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['unique_patient_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?><br><span style="font-size:11px; color:var(--text-muted);"><?php echo htmlspecialchars($p['phone']); ?></span></td>
                                            <td><?php echo htmlspecialchars($p['illness']); ?></td>
                                            <td><?php echo $p['doc_first'] ? htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']) : '<span style="color:var(--text-muted);">Не е назначен</span>'; ?></td>
                                            <td><?php echo htmlspecialchars($p['dept_name'] ?? 'Не е посочено'); ?></td>
                                            <td><?php echo htmlspecialchars($p['room_number'] ?? 'Няма'); ?></td>
                                            <td><?php echo $p['admission_date']; ?> <?php echo $p['discharge_date'] ? 'до ' . $p['discharge_date'] : '(активен)'; ?><br><span style="font-size:11px; font-weight:600;"><?php echo $days; ?> дни</span></td>
                                            <td>
                                                <span class="badge badge-<?php echo $p['status'] === 'cured' ? 'accent' : 'primary'; ?>">
                                                    <?php echo $p['status'] === 'cured' ? 'Излекуван' : 'Лекува се'; ?>
                                                </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <div style="display:inline-flex; gap:6px;">
                                                    <?php if ($p['status'] === 'admitted'): ?>
                                                        <form action="actions.php" method="POST">
                                                            <input type="hidden" name="action" value="cure_patient">
                                                            <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                                            <button type="submit" class="btn btn-accent btn-sm" title="Маркирай като излекуван">✓</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <a href="bill.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" title="Преглед на сметка" target="_blank">🧾</a>
                                                    
                                                    <button class="btn btn-secondary btn-sm" onclick="openEditPatient(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️</button>
                                                    
                                                    <form action="actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете картона на пациента?')">
                                                        <input type="hidden" name="action" value="delete_patient">
                                                        <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Таб: Лекари -->
                <section id="doctors-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🩺 Управление на Лекари</h1>
                            <p class="page-subtitle">Добавяне, редакция и изтриване на медицински специалисти</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addDoctorModal')">➕ Нов Лекар</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Идентификатор</th>
                                    <th>Име</th>
                                    <th>Квалификация</th>
                                    <th>Отделение</th>
                                    <th>Телефон / Имейл</th>
                                    <th>Потребителско име</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['doctors'] as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['unique_doc_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($d['qualification']); ?></td>
                                        <td><?php echo htmlspecialchars($d['dept_name'] ?? 'Не е назначен'); ?></td>
                                        <td><?php echo htmlspecialchars($d['phone']); ?><br><span style="font-size:11px; color:var(--text-muted);"><?php echo htmlspecialchars($d['email']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($d['username'] ?? 'Няма профил'); ?></code></td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditDoctor(<?php echo htmlspecialchars(json_encode($d)); ?>)">✏️</button>
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете лекаря? Всички негови дежурства ще бъдат изтрити!')">
                                                    <input type="hidden" name="action" value="delete_doctor">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $d['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Допълнителен персонал -->
                <section id="staff-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👔 Персонал (Сестри и Поддръжка)</h1>
                            <p class="page-subtitle">Управление на помощен медицински и технически състав</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addStaffModal')">➕ Нов Служител</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Име</th>
                                    <th>Длъжност</th>
                                    <th>Отделение</th>
                                    <th>Телефон</th>
                                    <th>Потребител</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['staff'] as $s): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $s['role'] === 'nurse' ? 'primary' : 'warning'; ?>">
                                                <?php echo $s['role'] === 'nurse' ? 'Медицинска сестра' : 'Поддръжка'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($s['dept_name'] ?? 'Всички / Обща база'); ?></td>
                                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                                        <td><code><?php echo htmlspecialchars($s['username'] ?? 'Няма профил'); ?></code></td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditStaff(<?php echo htmlspecialchars(json_encode($s)); ?>)">✏️</button>
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете служителя?')">
                                                    <input type="hidden" name="action" value="delete_staff">
                                                    <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Отделения -->
                <section id="departments-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🏢 Управление на Отделения</h1>
                            <p class="page-subtitle">Добавяне, редакция и задаване на ръководител на отделение</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addDeptModal')">➕ Ново Отделение</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Име на отделение</th>
                                    <th>Назначен Ръководител</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['departments'] as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                        <td>
                                            <?php if ($d['head_doctor_id']): ?>
                                                👨‍⚕️ <?php echo htmlspecialchars($d['doc_first'] . ' ' . $d['doc_last']); ?>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">Няма назначен ръководител</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditDept(<?php echo htmlspecialchars(json_encode($d)); ?>)">✏️</button>
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Наистина ли искате да изтриете отделението?')">
                                                    <input type="hidden" name="action" value="delete_department">
                                                    <input type="hidden" name="department_id" value="<?php echo $d['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Стаи -->
                <section id="rooms-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🚪 Управление на Стаи</h1>
                            <p class="page-subtitle">Конфигуриране на стаи за настаняване, интензивно и операционни зали</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addRoomModal')">➕ Нова Стая</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Тип</th>
                                    <th>Капацитет (Заетост)</th>
                                    <th>Цена на ден (престой)</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['rooms'] as $r): ?>
                                    <tr>
                                        <td><strong>Стая №<?php echo htmlspecialchars($r['room_number']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $types = ['regular' => 'Обикновена стая', 'icu' => 'Интензивно отделение', 'operating' => 'Операционна зала'];
                                            echo $types[$r['type']] ?? $r['type']; 
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $r['occupied']; ?> / <?php echo $r['capacity']; ?> легла запълнени
                                            <div style="width:100%; height:6px; background:#f1f5f9; border-radius:3px; margin-top:6px; overflow:hidden;">
                                                <div style="height:100%; width:<?php echo round(($r['occupied'] / $r['capacity']) * 100); ?>%; background:<?php echo $r['occupied'] == $r['capacity'] ? 'var(--danger)' : 'var(--accent)'; ?>;"></div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo number_format($r['price_per_day'], 2); ?> лв.</strong></td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditRoom(<?php echo htmlspecialchars(json_encode($r)); ?>)">✏️</button>
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Изтриване на стаята?')">
                                                    <input type="hidden" name="action" value="delete_room">
                                                    <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Дежурства -->
                <section id="shifts-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📅 График за Дежурства на Лекари</h1>
                            <p class="page-subtitle">Въвеждане и планиране на сутрешни, следобедни и нощни дежурства</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addShiftModal')">➕ Добави Дежурство</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Лекар</th>
                                    <th>Отделение</th>
                                    <th>Дата</th>
                                    <th>Смяна</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['shifts'] as $s): ?>
                                    <tr>
                                        <td><strong>👨‍⚕️ <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($s['dept_name'] ?? 'Обща база'); ?></td>
                                        <td><?php echo $s['shift_date']; ?></td>
                                        <td>
                                            <?php 
                                            $shiftLabels = ['morning' => '🌅 Сутрешна смяна', 'afternoon' => '🌇 Следобедна смяна', 'night' => '🌃 Нощно дежурство'];
                                            echo $shiftLabels[$s['shift_type']] ?? $s['shift_type'];
                                            ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <form action="actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да премахнете дежурството от графика?')">
                                                <input type="hidden" name="action" value="delete_shift">
                                                <input type="hidden" name="shift_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Премахни</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Потребители -->
                <section id="users-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🔑 Потребителски Акаунти и Роли</h1>
                            <p class="page-subtitle">Добавяне, редакция на роли и промяна на потребителски пароли</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addUserModal')">➕ Нов Потребител</button>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Потребителско име</th>
                                    <th>Системна Роля</th>
                                    <th>Дата на създаване</th>
                                    <th style="text-align:right;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['users'] as $u): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                $badges = ['admin'=>'danger', 'director'=>'warning', 'doctor'=>'primary', 'nurse'=>'primary', 'maintenance'=>'warning', 'patient'=>'accent'];
                                                echo $badges[$u['role']] ?? 'primary'; 
                                            ?>">
                                                <?php echo $roleNames[$u['role']] ?? $u['role']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $u['created_at']; ?></td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">✏️</button>
                                                <form action="actions.php" method="POST" onsubmit="return confirm('Сигурни ли сте, че желаете да изтриете потребителя?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" <?php echo $u['id'] == $userId ? 'disabled' : ''; ?>>🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Болница и Директор Настройки -->
                <section id="hospital-sec" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">⚙️ Настройки на Болницата & Директора</h1>
                            <p class="page-subtitle">Въвеждане, редактиране и изтриване на данни за административната база</p>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px;">
                        <!-- Болница -->
                        <div class="chart-card">
                            <h3 class="chart-title">🏢 Детайли на Лечебното Заведение</h3>
                            <form action="actions.php" method="POST">
                                <input type="hidden" name="action" value="edit_hospital">
                                <div class="form-group">
                                    <label class="form-label">Име на болницата</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($hospital['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Адрес на болницата</label>
                                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($hospital['address']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Запиши промените</button>
                            </form>
                        </div>

                        <!-- Директор -->
                        <div class="chart-card">
                            <h3 class="chart-title">👨‍💼 Данни за Директора на Болницата</h3>
                            <form action="actions.php" method="POST">
                                <input type="hidden" name="action" value="edit_director">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Име</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($data['director']['first_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Фамилия</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($data['director']['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Телефон за връзка</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($data['director']['phone']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Имейл адрес</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['director']['email']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-accent">Актуализирай Директор</button>
                            </form>
                        </div>
                    </div>
                </section>

            <!-- ========================================================================= -->
            <!-- 2. ИЗГЛЕД: ДИРЕКТОР -->
            <!-- ========================================================================= -->
            <?php elseif ($role === 'director'): ?>
                
                <!-- Таб: Директорски Анализ -->
                <section id="director-stats" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📊 Информационно Табло на Директора</h1>
                            <p class="page-subtitle">Статистика, анализи и общ преглед на болничните звена</p>
                        </div>
                    </div>

                    <!-- Статистически филтър -->
                    <div class="chart-card" style="margin-bottom:32px;">
                        <h3 class="chart-title" style="margin-bottom:12px;">🔍 Филтриране на медицинската статистика</h3>
                        <form action="dashboard.php" method="GET" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
                            <div class="form-group" style="margin-bottom:0; flex-grow:1;">
                                <label class="form-label">По отделениe</label>
                                <select name="filter_dept_id" class="form-control">
                                    <option value="">Всички отделения</option>
                                    <?php foreach($data['departments'] as $dp): ?>
                                        <option value="<?php echo $dp['id']; ?>" <?php echo (isset($_GET['filter_dept_id']) && $_GET['filter_dept_id'] == $dp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dp['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0; flex-grow:1;">
                                <label class="form-label">По лекуващ лекар</label>
                                <select name="filter_doc_id" class="form-control">
                                    <option value="">Всички лекари</option>
                                    <?php foreach($data['doctors'] as $dc): ?>
                                        <option value="<?php echo $dc['id']; ?>" <?php echo (isset($_GET['filter_doc_id']) && $_GET['filter_doc_id'] == $dc['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dc['first_name'] . ' ' . $dc['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Приложи Филтър</button>
                            <a href="dashboard.php" class="btn btn-secondary">Изчисти</a>
                        </form>
                    </div>

                    <!-- Резултати от филтрирана статистика -->
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Приети и лекуващи се пациенти</span>
                                <span class="stats-value"><?php echo intval($data['stats']['admitted_count']); ?></span>
                            </div>
                            <div class="stats-icon primary">🏥</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Излекувани & изписани</span>
                                <span class="stats-value"><?php echo intval($data['stats']['cured_count']); ?></span>
                            </div>
                            <div class="stats-icon accent">✓</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Общо обслужени пациенти</span>
                                <span class="stats-value"><?php echo intval($data['stats']['total_count']); ?></span>
                            </div>
                            <div class="stats-icon warning">👥</div>
                        </div>
                    </div>
                </section>

                <!-- Таб: Всички Пациенти -->
                <section id="director-pats" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👥 Всички Пациенти на Болницата</h1>
                            <p class="page-subtitle">Пълен списък с данни за заболявания, лекари и стаи</p>
                        </div>
                    </div>
                    
                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Идентификатор</th>
                                    <th>Име на пациент</th>
                                    <th>Заболяване</th>
                                    <th>Лекуващ Лекар</th>
                                    <th>Отделение</th>
                                    <th>Стая</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['patients'] as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['unique_patient_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?><br><span style="font-size:11px; color:var(--text-muted);"><?php echo htmlspecialchars($p['phone']); ?></span></td>
                                        <td><?php echo htmlspecialchars($p['illness']); ?></td>
                                        <td><?php echo htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']); ?></td>
                                        <td><?php echo htmlspecialchars($p['dept_name']); ?></td>
                                        <td>Стая №<?php echo htmlspecialchars($p['room_number']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $p['status'] === 'cured' ? 'accent' : 'primary'; ?>">
                                                <?php echo $p['status'] === 'cured' ? 'Излекуван' : 'Лекува се'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Лекари -->
                <section id="director-docs" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🩺 Списък с Медицински Специалисти</h1>
                            <p class="page-subtitle">Информация за всички зачислени лекари в отделенията</p>
                        </div>
                    </div>
                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>УИН</th>
                                    <th>Име на лекар</th>
                                    <th>Квалификация</th>
                                    <th>Назначено Отделение</th>
                                    <th>Контакти</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['doctors'] as $d): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($d['unique_doc_number']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($d['qualification']); ?></td>
                                        <td><?php echo htmlspecialchars($d['dept_name'] ?? 'Не е назначен'); ?></td>
                                        <td><?php echo htmlspecialchars($d['phone']); ?> | <?php echo htmlspecialchars($d['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Отделения -->
                <section id="director-depts" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🏢 Болнични Отделения</h1>
                            <p class="page-subtitle">Всички налични сектори в болницата</p>
                        </div>
                    </div>
                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Име на отделениe</th>
                                    <th>Директор / Ръководител</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['departments'] as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                        <td>👨‍⚕️ <?php echo $d['head_doctor_id'] ? htmlspecialchars($d['doc_first'] . ' ' . $d['doc_last']) : '<span style="color:var(--text-muted);">Не е назначен</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Персонал -->
                <section id="director-staff" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👔 Помощен персонал в болницата</h1>
                            <p class="page-subtitle">Списък на медицинските сестри и отдела за поддръжка</p>
                        </div>
                    </div>
                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Име</th>
                                    <th>Роля</th>
                                    <th>Отделение</th>
                                    <th>Телефон</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['staff'] as $s): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $s['role'] === 'nurse' ? 'primary' : 'warning'; ?>">
                                                <?php echo $s['role'] === 'nurse' ? 'Медицинска сестра' : 'Поддръжка'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($s['dept_name'] ?? 'Обща база'); ?></td>
                                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Стаи -->
                <section id="director-rooms" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🚪 Капацитет на болничните стаи</h1>
                            <p class="page-subtitle">Визуализация на леглата за настаняване, интензивно и операционни зали</p>
                        </div>
                    </div>
                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Стая №</th>
                                    <th>Тип стая</th>
                                    <th>Капацитет (Заетост)</th>
                                    <th>Цена на ден</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['rooms'] as $r): ?>
                                    <tr>
                                        <td><strong>Стая №<?php echo htmlspecialchars($r['room_number']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $types = ['regular' => 'Обикновена', 'icu' => 'Интензивно отделение', 'operating' => 'Операционна зала'];
                                            echo $types[$r['type']] ?? $r['type']; 
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $r['occupied']; ?> / <?php echo $r['capacity']; ?> легла запълнени
                                            <div style="width:100%; height:6px; background:#f1f5f9; border-radius:3px; margin-top:6px; overflow:hidden;">
                                                <div style="height:100%; width:<?php echo round(($r['occupied'] / $r['capacity']) * 100); ?>%; background:var(--primary);"></div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo number_format($r['price_per_day'], 2); ?> лв.</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <!-- ========================================================================= -->
            <!-- 3. ИЗГЛЕД: ЛЕКАР -->
            <!-- ========================================================================= -->
            <?php elseif ($role === 'doctor'): ?>
                
                <!-- Таб: Моите Пациенти -->
                <section id="doc-mypatients" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👥 Пациенти, които лекувам лично</h1>
                            <p class="page-subtitle">Въвеждане, редактиране и изписване на вашите пациенти</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('addPatientModal')">➕ Нов Пациент</button>
                    </div>

                    <!-- Моите статистики -->
                    <div class="stats-grid" style="margin-bottom:24px;">
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Общо лекувани от мен</span>
                                <span class="stats-value"><?php echo $data['stats']['total']; ?></span>
                            </div>
                            <div class="stats-icon primary">👥</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">В момента на лечение</span>
                                <span class="stats-value"><?php echo $data['stats']['admitted']; ?></span>
                            </div>
                            <div class="stats-icon danger">🏥</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Излекувани от мен</span>
                                <span class="stats-value"><?php echo $data['stats']['cured']; ?></span>
                            </div>
                            <div class="stats-icon accent">✓</div>
                        </div>
                    </div>

                    <div class="card-table-wrap">
                        <div class="table-header-toolbar">
                            <div class="search-input-wrap">
                                <span class="search-icon">🔍</span>
                                <input type="text" class="form-control search-control" placeholder="Търсене сред моите пациенти..." onkeyup="filterTable(this, 'myPatientsTable')">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table" id="myPatientsTable">
                                <thead>
                                    <tr>
                                        <th>Идентификатор</th>
                                        <th>Пациент</th>
                                        <th>Телефон</th>
                                        <th>Диагноза / Заболяване</th>
                                        <th>Активно лечение</th>
                                        <th>Стая</th>
                                        <th>Дата на приемане</th>
                                        <th>Статус</th>
                                        <th style="text-align:right;">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['my_patients'])): ?>
                                        <tr>
                                            <td colspan="9" style="text-align:center; color:var(--text-muted); padding:32px;">
                                                В момента нямате зачислени пациенти за лечение.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data['my_patients'] as $p): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($p['unique_patient_number']); ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($p['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($p['illness']); ?></td>
                                                <td><span style="font-size:12px;"><?php echo htmlspecialchars($p['treatment']); ?></span></td>
                                                <td>Стая №<?php echo htmlspecialchars($p['room_number'] ?? 'Няма'); ?></td>
                                                <td><?php echo $p['admission_date']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $p['status'] === 'cured' ? 'accent' : 'primary'; ?>">
                                                        <?php echo $p['status'] === 'cured' ? 'Излекуван' : 'Лекува се'; ?>
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div style="display:inline-flex; gap:6px;">
                                                        <?php if ($p['status'] === 'admitted'): ?>
                                                            <form action="actions.php" method="POST">
                                                                <input type="hidden" name="action" value="cure_patient">
                                                                <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                                                <button type="submit" class="btn btn-accent btn-sm" title="Отбележи като излекуван">✓</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="bill.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" title="Сметка за лечение" target="_blank">🧾</a>
                                                        <button class="btn btn-secondary btn-sm" onclick="openEditPatient(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️</button>
                                                        <form action="actions.php" method="POST" onsubmit="return confirm('Изтриване на пациента?')">
                                                            <input type="hidden" name="action" value="delete_patient">
                                                            <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Таб: Лични дежурства -->
                <section id="doc-myshifts" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📅 Моят График за Дежурства</h1>
                            <p class="page-subtitle">Вашите предстоящи смени в болницата</p>
                        </div>
                    </div>
                    <div class="card-table-wrap" style="max-width:600px;">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Смяна</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['my_shifts'])): ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; color:var(--text-muted); padding:24px;">Нямате предстоящи планирани дежурства.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['my_shifts'] as $s): ?>
                                        <tr>
                                            <td><strong><?php echo $s['shift_date']; ?></strong></td>
                                            <td>
                                                <?php 
                                                $shL = ['morning' => '🌅 Сутрешна смяна (06:00 - 14:00)', 'afternoon' => '🌇 Следобедна смяна (14:00 - 22:00)', 'night' => '🌃 Нощно дежурство (22:00 - 06:00)'];
                                                echo $shL[$s['shift_type']] ?? $s['shift_type'];
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <!-- ========================================================================= -->
            <!-- 4. ИЗГЛЕД: МЕДИЦИНСКА СЕСТРА -->
            <!-- ========================================================================= -->
            <?php elseif ($role === 'nurse'): ?>
                
                <!-- Таб: Пациенти в отделението -->
                <section id="nurse-patients" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">👥 Пациенти в отделение "<?php echo htmlspecialchars($data['department_name']); ?>"</h1>
                            <p class="page-subtitle">Грижа за настанените пациенти и наблюдение на лечението</p>
                        </div>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Идентификатор</th>
                                    <th>Име</th>
                                    <th>Заболяване</th>
                                    <th>Лекуващ Лекар</th>
                                    <th>Стая</th>
                                    <th>Постъпил на</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['dept_patients'])): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">Няма пациенти в отделението в момента.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['dept_patients'] as $p): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['unique_patient_number']); ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong><br><?php echo htmlspecialchars($p['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($p['illness']); ?><br><span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($p['treatment']); ?></span></td>
                                            <td>👨‍⚕️ <?php echo htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']); ?></td>
                                            <td>Стая №<?php echo htmlspecialchars($p['room_number']); ?></td>
                                            <td><?php echo $p['admission_date']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $p['status'] === 'cured' ? 'accent' : 'primary'; ?>">
                                                    <?php echo $p['status'] === 'cured' ? 'Изписан' : 'Активен'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Таб: Дежурства на лекарите в отделението -->
                <section id="nurse-shifts" class="tab-content">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📅 Дежурства на Лекарите в "<?php echo htmlspecialchars($data['department_name']); ?>"</h1>
                            <p class="page-subtitle">Вижте графика на лекуващите лекари за координация</p>
                        </div>
                    </div>
                    <div class="card-table-wrap" style="max-width:700px;">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Лекар</th>
                                    <th>Дата</th>
                                    <th>Смяна</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['dept_shifts'])): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">Няма планирани дежурства.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['dept_shifts'] as $s): ?>
                                        <tr>
                                            <td><strong>👨‍⚕️ <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                                            <td><?php echo $s['shift_date']; ?></td>
                                            <td>
                                                <?php 
                                                $sh = ['morning'=>'🌅 Сутрешна смяна', 'afternoon'=>'🌇 Следобедна', 'night'=>'🌃 Нощна'];
                                                echo $sh[$s['shift_type']] ?? $s['shift_type'];
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <!-- ========================================================================= -->
            <!-- 5. ИЗГЛЕД: ПОДДРЪЖКА -->
            <!-- ========================================================================= -->
            <?php elseif ($role === 'maintenance'): ?>
                
                <!-- Таб: Статус на стаите -->
                <section id="maint-rooms" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">🚪 Статус и Заетост на Болничните Стаи</h1>
                            <p class="page-subtitle">Следене на леглата, операционните зали и капацитета на интензивните легла</p>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Общо операционни зали</span>
                                <span class="stats-value">
                                    <?php 
                                    $op = 0; foreach ($data['rooms'] as $r) if ($r['type'] === 'operating') $op++;
                                    echo $op;
                                    ?>
                                </span>
                            </div>
                            <div class="stats-icon primary">⚡</div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-info">
                                <span class="stats-label">Стаи за интензивно (ICU)</span>
                                <span class="stats-value">
                                    <?php 
                                    $icu = 0; foreach ($data['rooms'] as $r) if ($r['type'] === 'icu') $icu++;
                                    echo $icu;
                                    ?>
                                </span>
                            </div>
                            <div class="stats-icon warning">🚨</div>
                        </div>
                    </div>

                    <div class="card-table-wrap">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Стая</th>
                                    <th>Тип</th>
                                    <th>Легла (Заетост)</th>
                                    <th>Статус поддръжка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['rooms'] as $r): ?>
                                    <tr>
                                        <td><strong>Стая №<?php echo htmlspecialchars($r['room_number']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $types = ['regular' => 'Обикновена лечебна стая', 'icu' => 'Интензивно отделение (ОАИЛ)', 'operating' => 'Операционен сектор'];
                                            echo $types[$r['type']] ?? $r['type']; 
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $r['occupied']; ?> / <?php echo $r['capacity']; ?></strong> настанени легла
                                        </td>
                                        <td>
                                            <?php if ($r['occupied'] == $r['capacity']): ?>
                                                <span class="badge badge-danger">Запълнена изцяло</span>
                                            <?php elseif ($r['occupied'] > 0): ?>
                                                <span class="badge badge-warning">Частично заета</span>
                                            <?php else: ?>
                                                <span class="badge badge-accent">Свободна / Чиста</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <!-- ========================================================================= -->
            <!-- 6. ИЗГЛЕД: ПАЦИЕНТ -->
            <!-- ========================================================================= -->
            <?php elseif ($role === 'patient'): ?>
                
                <!-- Таб: Личен Картон -->
                <section id="patient-card" class="tab-content active">
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">📄 Моят Болничен Картон</h1>
                            <p class="page-subtitle">Вашите лични медицински данни, лекуващ лекар и сметка за престой</p>
                        </div>
                    </div>

                    <?php if (!$data['patient_record']): ?>
                        <div class="alert alert-danger">
                            <span>⚠️ Данните за вашия болничен картон все още не са попълнени от лекуващ лекар или администратор.</span>
                        </div>
                    <?php else: 
                        $p = $data['patient_record'];
                        $adm = new DateTime($p['admission_date']);
                        $dis = $p['discharge_date'] ? new DateTime($p['discharge_date']) : new DateTime();
                        $days = $adm->diff($dis)->days;
                        if ($days == 0) $days = 1;
                        $roomBill = $days * $p['price_per_day'];
                        $totalBill = $roomBill + $p['treatment_cost'];
                    ?>
                        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:32px;">
                            
                            <!-- Лични & Медицински данни -->
                            <div class="chart-card">
                                <div style="border-bottom:1px solid var(--border-color); padding-bottom:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                                    <h3 class="chart-title" style="margin-bottom:0;">🩺 Медицинско досие</h3>
                                    <span class="badge badge-primary">ИД: <?php echo htmlspecialchars($p['unique_patient_number']); ?></span>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Име на пациент</p>
                                        <p style="font-weight:600; font-size:16px;"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></p>
                                    </div>
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Телефон</p>
                                        <p style="font-weight:600;"><?php echo htmlspecialchars($p['phone']); ?></p>
                                    </div>
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Имейл адрес</p>
                                        <p style="font-weight:600;"><?php echo htmlspecialchars($p['email'] ?: 'Липсва'); ?></p>
                                    </div>
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Настанен в стая</p>
                                        <p style="font-weight:600;">Стая №<?php echo htmlspecialchars($p['room_number'] ?? 'Не е назначен'); ?></p>
                                    </div>
                                </div>

                                <div style="background:#f8fafc; padding:16px; border-radius:8px; margin-bottom:20px;">
                                    <p style="font-size:12px; color:var(--text-muted); font-weight:600;">ЗАБОЛЯВАНЕ / ДИАГНОЗА</p>
                                    <p style="font-size:15px; font-weight:700; color:var(--primary-dark); margin-top:4px;"><?php echo htmlspecialchars($p['illness']); ?></p>
                                </div>

                                <div style="background:#f8fafc; padding:16px; border-radius:8px; margin-bottom:24px;">
                                    <p style="font-size:12px; color:var(--text-muted); font-weight:600;">ПРОВЕЖДАНО ЛЕЧЕНИЕ</p>
                                    <p style="font-size:14px; margin-top:4px;"><?php echo nl2br(htmlspecialchars($p['treatment'])); ?></p>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; border-top:1px solid var(--border-color); padding-top:20px;">
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Лекуващ лекар</p>
                                        <p style="font-weight:600; color:var(--primary);">👨‍⚕️ <?php echo htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']); ?></p>
                                        <p style="font-size:11px; color:var(--text-muted);">Контакт: <?php echo htmlspecialchars($p['doc_phone']); ?></p>
                                    </div>
                                    <div>
                                        <p style="font-size:12px; color:var(--text-muted);">Дата на постъпване</p>
                                        <p style="font-weight:600;"><?php echo $p['admission_date']; ?></p>
                                        <p style="font-size:11px; color:var(--text-muted);">Изписан на: <?php echo $p['discharge_date'] ?: 'Все още на лечение'; ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Сметка / Финансова информация -->
                            <div class="chart-card" style="align-self:start;">
                                <h3 class="chart-title">🧾 Сметка за лечение</h3>
                                <div style="background:var(--primary-light); color:var(--primary-dark); padding:20px; border-radius:12px; text-align:center; margin-bottom:20px;">
                                    <span style="font-size:13px; font-weight:600; text-transform:uppercase;">Дължима сума</span>
                                    <h2 style="font-size:32px; font-weight:800; font-family:var(--font-heading); margin-top:4px;"><?php echo number_format($totalBill, 2); ?> лв.</h2>
                                    <span style="font-size:11px; font-weight:500;">(на база <?php echo $days; ?> дни престой)</span>
                                </div>

                                <div style="display:flex; flex-direction:column; gap:10px; font-size:13px; margin-bottom:24px; border-bottom:1px solid var(--border-color); padding-bottom:16px;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span class="text-muted">Престой в стая (<?php echo $days; ?> дни x <?php echo number_format($p['price_per_day'], 2); ?> лв.)</span>
                                        <strong><?php echo number_format($roomBill, 2); ?> лв.</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span class="text-muted">Разходи за лечение</span>
                                        <strong><?php echo number_format($p['treatment_cost'], 2); ?> лв.</strong>
                                    </div>
                                </div>

                                <a href="bill.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-primary btn-block" target="_blank">
                                    🖨️ Разпечатай Фактура
                                </a>
                            </div>

                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <!-- ========================================== -->
    <!-- МОДАЛНИ ПРОЗОРЦИ ЗА АДМИНИСТРАТОР/ЛЕКАР -->
    <!-- ========================================== -->

    <!-- Модал: Добавяне на Пациент -->
    <div class="modal-overlay" id="addPatientModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">🏥 Регистриране на нов пациент</h3>
                <button class="modal-close" onclick="closeModal('addPatientModal')">&times;</button>
            </div>
            <form action="actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_patient">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Име *</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Име" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Фамилия *</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Фамилия" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Телефон *</label>
                            <input type="text" name="phone" class="form-control" placeholder="Телефон" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Имейл</label>
                            <input type="email" name="email" class="form-control" placeholder="Имейл">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Диагноза / Заболяване *</label>
                        <input type="text" name="illness" class="form-control" placeholder="Въведете диагноза на пациента" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Провеждано лечение</label>
                        <textarea name="treatment" class="form-control" rows="3" placeholder="Опишете лечението..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Отделение</label>
                            <select name="department_id" class="form-control">
                                <option value="">Изберете отделение</option>
                                <?php 
                                $depts = ($role === 'admin') ? $data['departments'] : $data['departments'];
                                foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Стая</label>
                            <select name="room_id" class="form-control">
                                <option value="">Изберете стая</option>
                                <?php foreach ($data['rooms'] as $r): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo $r['occupied'] >= $r['capacity'] ? 'disabled style="color:var(--text-muted);"' : ''; ?>>
                                        Стая №<?php echo htmlspecialchars($r['room_number']); ?> (<?php 
                                            $rm = ['regular'=>'обикновена', 'icu'=>'интензивно', 'operating'=>'операционна'];
                                            echo $rm[$r['type']] ?? $r['type'];
                                        ?>) [Свободни: <?php echo $r['capacity'] - $r['occupied']; ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <?php if ($role === 'admin'): ?>
                            <div class="form-group">
                                <label class="form-label">Лекуващ лекар</label>
                                <select name="doctor_id" class="form-control">
                                    <option value="">Изберете лекар</option>
                                    <?php foreach ($data['doctors'] as $d): ?>
                                        <option value="<?php echo $d['id']; ?>">👨‍⚕️ <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">Първоначална такса за лечение (лв.)</label>
                            <input type="number" name="treatment_cost" step="0.01" class="form-control" value="0.00">
                        </div>
                    </div>

                    <div style="background:#f1f5f9; padding:12px; border-radius:8px; margin-top:16px;">
                        <p style="font-size:12px; font-weight:600; margin-bottom:8px; color:#475569;">Създаване на потребителски акаунт (Опционално):</p>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:11px;">Потребителско име</label>
                                <input type="text" name="username" class="form-control" style="padding:8px 12px; font-size:13px;">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:11px;">Парола</label>
                                <input type="password" name="password" class="form-control" style="padding:8px 12px; font-size:13px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPatientModal')">Отказ</button>
                    <button type="submit" class="btn btn-primary">Регистрирай</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модал: Редактиране на Пациент -->
    <div class="modal-overlay" id="editPatientModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Редактиране на картон на пациент</h3>
                <button class="modal-close" onclick="closeModal('editPatientModal')">&times;</button>
            </div>
            <form action="actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_patient">
                    <input type="hidden" name="patient_id" id="edit_pat_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Име *</label>
                            <input type="text" name="first_name" id="edit_pat_first" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Фамилия *</label>
                            <input type="text" name="last_name" id="edit_pat_last" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Телефон *</label>
                            <input type="text" name="phone" id="edit_pat_phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Имейл</label>
                            <input type="email" name="email" id="edit_pat_email" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Диагноза / Заболяване *</label>
                        <input type="text" name="illness" id="edit_pat_illness" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Провеждано лечение</label>
                        <textarea name="treatment" id="edit_pat_treatment" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Отделение</label>
                            <select name="department_id" id="edit_pat_dept" class="form-control">
                                <option value="">Изберете отделение</option>
                                <?php 
                                $depts = ($role === 'admin') ? $data['departments'] : $data['departments'];
                                foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Стая</label>
                            <select name="room_id" id="edit_pat_room" class="form-control">
                                <option value="">Изберете стая</option>
                                <?php foreach ($data['rooms'] as $r): ?>
                                    <option value="<?php echo $r['id']; ?>">
                                        Стая №<?php echo htmlspecialchars($r['room_number']); ?> (<?php echo $r['type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <?php if ($role === 'admin'): ?>
                            <div class="form-group">
                                <label class="form-label">Лекуващ лекар</label>
                                <select name="doctor_id" id="edit_pat_doc" class="form-control">
                                    <option value="">Изберете лекар</option>
                                    <?php foreach ($data['doctors'] as $d): ?>
                                        <option value="<?php echo $d['id']; ?>">👨‍⚕️ <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">Разходи за лечение (лв.)</label>
                            <input type="number" name="treatment_cost" id="edit_pat_cost" step="0.01" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPatientModal')">Отказ</button>
                    <button type="submit" class="btn btn-accent">Запиши промените</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модали за Администратор: Добавяне на Лекар, Стая, Отделение, Персонал, Дежурство, Потребител -->
    <?php if ($role === 'admin'): ?>
        
        <!-- Добавяне на Лекар -->
        <div class="modal-overlay" id="addDoctorModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">🩺 Добавяне на лекар</h3>
                    <button class="modal-close" onclick="closeModal('addDoctorModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_doctor">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Име *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Телефон *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Имейл</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Специалност / Квалификация *</label>
                            <input type="text" name="qualification" class="form-control" placeholder="напр. Кардиолог - доцент" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Отделение</label>
                            <select name="department_id" class="form-control">
                                <option value="">Изберете отделение</option>
                                <?php foreach($data['departments'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="background:#f1f5f9; padding:12px; border-radius:8px; margin-top:16px;">
                            <p style="font-size:12px; font-weight:600; margin-bottom:8px; color:#475569;">Акаунт за вход в системата:</p>
                            <div class="form-row">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" style="font-size:11px;">Потребителско име</label>
                                    <input type="text" name="username" class="form-control" style="padding:8px; font-size:13px;">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" style="font-size:11px;">Парола</label>
                                    <input type="password" name="password" class="form-control" style="padding:8px; font-size:13px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addDoctorModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Запиши</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Редактиране на Лекар -->
        <div class="modal-overlay" id="editDoctorModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">✏️ Редактиране на лекар</h3>
                    <button class="modal-close" onclick="closeModal('editDoctorModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_doctor">
                        <input type="hidden" name="doctor_id" id="edit_doc_id">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Име *</label>
                                <input type="text" name="first_name" id="edit_doc_first" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" name="last_name" id="edit_doc_last" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Телефон *</label>
                                <input type="text" name="phone" id="edit_doc_phone" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Имейл</label>
                                <input type="email" name="email" id="edit_doc_email" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Квалификация *</label>
                            <input type="text" name="qualification" id="edit_doc_qual" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Отделение</label>
                            <select name="department_id" id="edit_doc_dept" class="form-control">
                                <option value="">Изберете отделение</option>
                                <?php foreach($data['departments'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editDoctorModal')">Отказ</button>
                        <button type="submit" class="btn btn-accent">Запази</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Добавяне на Стая -->
        <div class="modal-overlay" id="addRoomModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">🚪 Нова Стая</h3>
                    <button class="modal-close" onclick="closeModal('addRoomModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_room">
                        <div class="form-group">
                            <label class="form-label">Номер на стаята *</label>
                            <input type="text" name="room_number" class="form-control" placeholder="напр. 101" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Тип на стаята *</label>
                            <select name="type" class="form-control" required>
                                <option value="regular">Обикновена</option>
                                <option value="icu">Интензивно отделение (ICU)</option>
                                <option value="operating">Операционна зала</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Капацитет (Легла) *</label>
                                <input type="number" name="capacity" class="form-control" value="2" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Цена за ден престой (лв.) *</label>
                                <input type="number" name="price_per_day" class="form-control" value="50.00" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Добави</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Редактиране на Стая -->
        <div class="modal-overlay" id="editRoomModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">🚪 Редактиране на стая</h3>
                    <button class="modal-close" onclick="closeModal('editRoomModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_room">
                        <input type="hidden" name="room_id" id="edit_room_id">
                        <div class="form-group">
                            <label class="form-label">Номер на стаята *</label>
                            <input type="text" name="room_number" id="edit_room_num" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Тип на стаята *</label>
                            <select name="type" id="edit_room_type" class="form-control" required>
                                <option value="regular">Обикновена</option>
                                <option value="icu">Интензивно отделение (ICU)</option>
                                <option value="operating">Операционна зала</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Капацитет (Легла) *</label>
                                <input type="number" name="capacity" id="edit_room_cap" class="form-control" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Цена за ден престой (лв.) *</label>
                                <input type="number" name="price_per_day" id="edit_room_price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editRoomModal')">Отказ</button>
                        <button type="submit" class="btn btn-accent">Запази</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Добавяне на Отделение -->
        <div class="modal-overlay" id="addDeptModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">🏢 Ново Отделение</h3>
                    <button class="modal-close" onclick="closeModal('addDeptModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_department">
                        <div class="form-group">
                            <label class="form-label">Име на отделението *</label>
                            <input type="text" name="name" class="form-control" placeholder="напр. Кардиология" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ръководител (Лекар)</label>
                            <select name="head_doctor_id" class="form-control">
                                <option value="">Изберете лекар за ръководител</option>
                                <?php foreach($data['doctors'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addDeptModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Създай</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Редактиране на Отделение -->
        <div class="modal-overlay" id="editDeptModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">✏️ Редактиране на отделение</h3>
                    <button class="modal-close" onclick="closeModal('editDeptModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_department">
                        <input type="hidden" name="department_id" id="edit_dept_id">
                        <div class="form-group">
                            <label class="form-label">Име на отделението *</label>
                            <input type="text" name="name" id="edit_dept_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ръководител (Лекар)</label>
                            <select name="head_doctor_id" id="edit_dept_head" class="form-control">
                                <option value="">Изберете лекар за ръководител</option>
                                <?php foreach($data['doctors'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editDeptModal')">Отказ</button>
                        <button type="submit" class="btn btn-accent">Запази</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Добавяне на Персонал -->
        <div class="modal-overlay" id="addStaffModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">👔 Нов служител</h3>
                    <button class="modal-close" onclick="closeModal('addStaffModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Име *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Роля *</label>
                                <select name="staff_role" class="form-control" required>
                                    <option value="nurse">Медицинска сестра</option>
                                    <option value="maintenance">Поддръжка / Техник</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Телефон *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Отделение (за сестри)</label>
                            <select name="department_id" class="form-control">
                                <option value="">Изберете отделение</option>
                                <?php foreach($data['departments'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="background:#f1f5f9; padding:12px; border-radius:8px; margin-top:16px;">
                            <p style="font-size:12px; font-weight:600; margin-bottom:8px; color:#475569;">Потребителски акаунт за вход:</p>
                            <div class="form-row">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" style="font-size:11px;">Потребителско име</label>
                                    <input type="text" name="username" class="form-control" style="padding:8px; font-size:13px;">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" style="font-size:11px;">Парола</label>
                                    <input type="password" name="password" class="form-control" style="padding:8px; font-size:13px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addStaffModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Добави</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Редактиране на Персонал -->
        <div class="modal-overlay" id="editStaffModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">✏️ Редактиране на служител</h3>
                    <button class="modal-close" onclick="closeModal('editStaffModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_staff">
                        <input type="hidden" name="staff_id" id="edit_staff_id">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Име *</label>
                                <input type="text" name="first_name" id="edit_staff_first" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" name="last_name" id="edit_staff_last" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Телефон *</label>
                                <input type="text" name="phone" id="edit_staff_phone" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Отделение</label>
                                <select name="department_id" id="edit_staff_dept" class="form-control">
                                    <option value="">Изберете отделение</option>
                                    <?php foreach($data['departments'] as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editStaffModal')">Отказ</button>
                        <button type="submit" class="btn btn-accent">Запази</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Добавяне на Дежурство -->
        <div class="modal-overlay" id="addShiftModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">📅 Ново Дежурство</h3>
                    <button class="modal-close" onclick="closeModal('addShiftModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_shift">
                        <div class="form-group">
                            <label class="form-label">Лекар *</label>
                            <select name="doctor_id" class="form-control" required>
                                <option value="">Изберете лекар</option>
                                <?php foreach($data['doctors'] as $d): ?>
                                    <option value="<?php echo $d['id']; ?>">👨‍⚕️ <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Дата на дежурство *</label>
                            <input type="date" name="shift_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Смяна *</label>
                            <select name="shift_type" class="form-control" required>
                                <option value="morning">Сутрешна смяна (06:00 - 14:00)</option>
                                <option value="afternoon">Следобедна смяна (14:00 - 22:00)</option>
                                <option value="night">Нощно дежурство (22:00 - 06:00)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addShiftModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Планирай</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Добавяне на Потребител -->
        <div class="modal-overlay" id="addUserModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">🔑 Нов потребител</h3>
                    <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-group">
                            <label class="form-label">Потребителско име *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Парола *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Роля *</label>
                            <select name="user_role" class="form-control" required>
                                <option value="admin">Администратор</option>
                                <option value="director">Директор</option>
                                <option value="doctor">Лекар</option>
                                <option value="nurse">Медицинска сестра</option>
                                <option value="maintenance">Поддръжка</option>
                                <option value="patient">Пациент</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Отказ</button>
                        <button type="submit" class="btn btn-primary">Запиши</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Редактиране на Потребител -->
        <div class="modal-overlay" id="editUserModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">✏️ Редактиране на потребителски акаунт</h3>
                    <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
                </div>
                <form action="actions.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group">
                            <label class="form-label">Потребителско име *</label>
                            <input type="text" name="username" id="edit_user_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Нова парола (Оставете празна, ако няма да променяте)</label>
                            <input type="password" name="password" class="form-control" placeholder="Нова парола">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Роля *</label>
                            <select name="user_role" id="edit_user_role" class="form-control" required>
                                <option value="admin">Администратор</option>
                                <option value="director">Директор</option>
                                <option value="doctor">Лекар</option>
                                <option value="nurse">Медицинска сестра</option>
                                <option value="maintenance">Поддръжка</option>
                                <option value="patient">Пациент</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Отказ</button>
                        <button type="submit" class="btn btn-accent">Запази</button>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <!-- Включване на скриптове -->
    <script src="script.js"></script>
</body>
</html>
