<?php
// bill.php - Генератор на фактури и сметки за лечение за Web_Hospital_NBU
session_start();
require_once 'db.php';

// Проверка дали потребителят е влязъл
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$patientId = intval($_GET['patient_id'] ?? 0);

if ($patientId <= 0) {
    die("Невалиден идентификатор на пациент.");
}

try {
    // Извличане на данните за пациента
    $stmt = $pdo->prepare("SELECT p.*, doc.first_name as doc_first, doc.last_name as doc_last, doc.unique_doc_number, dept.name as dept_name, r.room_number, r.price_per_day, r.type as room_type FROM patients p LEFT JOIN doctors doc ON p.doctor_id = doc.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    if (!$patient) {
        die("Пациентът не бе намерен в системата.");
    }

    // Защита на личните данни: Пациентите могат да преглеждат само собствената си сметка!
    if ($role === 'patient' && intval($patient['user_id']) !== intval($userId)) {
        die("Грешка: Нямате права да преглеждате сметката на друг пациент.");
    }

    // Изчисляване на продължителността на престоя в дни
    $adm = new DateTime($patient['admission_date']);
    $dis = $patient['discharge_date'] ? new DateTime($patient['discharge_date']) : new DateTime();
    $days = $adm->diff($dis)->days;
    if ($days == 0) $days = 1; // Престой под 24 часа се брои за 1 ден

    // Финансови изчисления
    $roomRate = floatval($patient['price_per_day'] ?? 40.00);
    $roomSubtotal = $days * $roomRate;
    $treatmentCost = floatval($patient['treatment_cost'] ?? 0.00);
    $totalBill = $roomSubtotal + $treatmentCost;

    // Извличане на информация за болницата
    $hospitalStmt = $pdo->query("SELECT * FROM hospital_info LIMIT 1");
    $hospital = $hospitalStmt->fetch() ?: ['name' => 'Университетска болница - НБУ', 'address' => 'ул. Монтевидео 21'];

} catch (Exception $e) {
    die("Грешка в базата данни: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фактура - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 40px 20px;
        }
    </style>
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; margin-bottom: 24px;" class="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="dashboard.php" class="btn btn-secondary">← Обратно към Таблото</a>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Разпечатай Сметката</button>
        </div>
    </div>

    <div class="bill-invoice">
        <div class="bill-header">
            <div class="bill-hospital-details">
                <h2>🏥 <?php echo htmlspecialchars($hospital['name']); ?></h2>
                <p>📍 <?php echo htmlspecialchars($hospital['address']); ?></p>
                <p>📞 Тел: 02 / 911 33 27</p>
                <p>✉️ Email: info@hospital-nbu.bg</p>
            </div>
            <div class="bill-meta">
                <h1 class="bill-title">СМЕТКА ЗА ЛЕЧЕНИЕ</h1>
                <p class="bill-number">Документ №: <?php echo str_replace('PAT', 'INV-', $patient['unique_patient_number']) . '-' . date('Ymd'); ?></p>
                <p>Дата на издаване: <?php echo date('d.m.Y г.'); ?></p>
            </div>
        </div>

        <div class="bill-grid">
            <div>
                <h4 class="bill-section-title">Пациент (Получател):</h4>
                <div class="bill-party-name"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                <p>Идентификатор на пациент: <strong><?php echo htmlspecialchars($patient['unique_patient_number']); ?></strong></p>
                <p>Телефон: <?php echo htmlspecialchars($patient['phone']); ?></p>
                <p>Имейл: <?php echo htmlspecialchars($patient['email'] ?: 'Липсва'); ?></p>
            </div>
            <div>
                <h4 class="bill-section-title">Лечение & Настаняване:</h4>
                <p>Отделение: <strong><?php echo htmlspecialchars($patient['dept_name'] ?? 'Не е зачислен'); ?></strong></p>
                <p>Стая на настаняване: <strong>Стая №<?php echo htmlspecialchars($patient['room_number'] ?? 'Липсва'); ?></strong> (<?php 
                    $rmTypes = ['regular'=>'Обикновена стая', 'icu'=>'Интензивни грижи', 'operating'=>'Операционен сектор'];
                    echo $rmTypes[$patient['room_type']] ?? $patient['room_type'];
                ?>)</p>
                <p>Лекуващ Лекар: <strong><?php echo htmlspecialchars($patient['doc_first'] . ' ' . $patient['doc_last'] . ' (УИН: ' . $patient['unique_doc_number'] . ')'); ?></strong></p>
            </div>
        </div>

        <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 32px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 8px; color: var(--primary-dark); font-family: var(--font-heading);">Медицински показатели</h4>
            <p><strong>Поставена Диагноза:</strong> <?php echo htmlspecialchars($patient['illness']); ?></p>
            <p style="margin-top:6px;"><strong>Проведено Лечение:</strong> <?php echo htmlspecialchars($patient['treatment']); ?></p>
            <p style="margin-top:6px;"><strong>Период на хоспитализация:</strong> <?php echo date('d.m.Y', strtotime($patient['admission_date'])); ?> г. – <?php echo $patient['discharge_date'] ? date('d.m.Y', strtotime($patient['discharge_date'])) . ' г.' : 'Все още на лечение (към днешна дата)'; ?></p>
        </div>

        <table class="bill-table">
            <thead>
                <tr>
                    <th>Описание на услугата / разхода</th>
                    <th style="text-align:center;">Количество (Дни)</th>
                    <th style="text-align:right;">Единична Цена</th>
                    <th style="text-align:right;">Обща сума</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Престой в болнична стая №<?php echo htmlspecialchars($patient['room_number']); ?> (<?php echo $patient['room_type'] === 'icu' ? 'Интензивно отделение' : 'Обикновен престой'; ?>)</td>
                    <td style="text-align:center;"><?php echo $days; ?></td>
                    <td style="text-align:right;"><?php echo number_format($roomRate, 2); ?> лв.</td>
                    <td style="text-align:right; font-weight:600;"><?php echo number_format($roomSubtotal, 2); ?> лв.</td>
                </tr>
                <tr>
                    <td>Разходи за проведено медикаментозно, оперативно и клинично лечение по диагноза</td>
                    <td style="text-align:center;">1</td>
                    <td style="text-align:right;"><?php echo number_format($treatmentCost, 2); ?> лв.</td>
                    <td style="text-align:right; font-weight:600;"><?php echo number_format($treatmentCost, 2); ?> лв.</td>
                </tr>
            </tbody>
        </table>

        <div class="bill-total-section">
            <div class="bill-total-box">
                <div class="bill-total-row">
                    <span>Междинна сума:</span>
                    <span><?php echo number_format($totalBill, 2); ?> лв.</span>
                </div>
                <div class="bill-total-row">
                    <span>ДДС (0% за здравни услуги):</span>
                    <span>0.00 лв.</span>
                </div>
                <div class="bill-total-row grand-total">
                    <span>ОБЩО ДЪЛЖИМО:</span>
                    <span><?php echo number_format($totalBill, 2); ?> лв.</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 64px; border-top: 1px solid var(--border-color); padding-top: 24px; font-size: 12px; color: var(--text-muted); display:flex; justify-content:space-between;">
            <div>
                <p>Изготвил: .......................................</p>
                <p style="font-size:10px; margin-top:4px;">(Подпис и печат на лечебното заведение)</p>
            </div>
            <div>
                <p>Пациент: .......................................</p>
                <p style="font-size:10px; margin-top:4px;">(Георги Димитров - потвърдил престоя)</p>
            </div>
        </div>
    </div>
</body>
</html>
