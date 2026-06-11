<?php
// includes/admin_dashboard.php - Изгледи, раздели и таблици за Администратор
?>
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

    <!-- Дневник на дейностите -->
    <div class="card-table-wrap" style="margin-top: 30px;">
        <h3 class="chart-title" style="padding: 20px 20px 0;">📋 Последни действия в системата (Дневник)</h3>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Време</th>
                        <th>Потребител</th>
                        <th>Действие</th>
                        <th>Детайли</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['activity_logs'])): ?>
                        <?php foreach($data['activity_logs'] as $log): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($log['username'] ?? 'Система'); ?></strong></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><small><?php echo htmlspecialchars($log['details'] ?? '-'); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">Няма записани действия.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
                                        <form action="../core/actions/patient_actions.php" method="POST">
                                            <input type="hidden" name="action" value="cure_patient">
                                            <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-accent btn-sm" title="Маркирай като излекуван">✓</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="bill.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" title="Преглед на сметка" target="_blank">🧾 Сметка</a>
                                    
                                    <button class="btn btn-secondary btn-sm" onclick="openEditPatient(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️</button>
                                    
                                    <form action="../core/actions/patient_actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете картона на пациента?')">
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
                                <form action="../core/actions/doctor_actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете лекаря? Всички негови дежурства ще бъдат изтрити!')">
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
                                <form action="../core/actions/staff_actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да изтриете служителя?')">
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
                                <?php echo htmlspecialchars($d['doc_first'] . ' ' . $d['doc_last']); ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">Няма назначен ръководител</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; gap:6px;">
                                <button class="btn btn-secondary btn-sm" onclick="openEditDept(<?php echo htmlspecialchars(json_encode($d)); ?>)">✏️</button>
                                <form action="../core/actions/department_actions.php" method="POST" onsubmit="return confirm('Наистина ли искате да изтриете отделението?')">
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
                                <form action="../core/actions/room_actions.php" method="POST" onsubmit="return confirm('Изтриване на стаята?')">
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
                        <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['dept_name'] ?? 'Обща база'); ?></td>
                        <td><?php echo $s['shift_date']; ?></td>
                        <td>
                            <?php 
                            $shiftLabels = ['morning' => 'Сутрешна смяна', 'afternoon' => 'Следобедна смяна', 'night' => 'Нощно дежурство'];
                            echo $shiftLabels[$s['shift_type']] ?? $s['shift_type'];
                            ?>
                        </td>
                        <td style="text-align:right;">
                            <form action="../core/actions/shift_actions.php" method="POST" onsubmit="return confirm('Наистина ли желаете да премахнете дежурството от графика?')">
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
                                <form action="../core/actions/user_actions.php" method="POST" onsubmit="return confirm('Сигурни ли сте, че желаете да изтриете потребителя?')">
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

<!-- Таб: Настройки -->
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
            <form action="../core/actions/hospital_actions.php" method="POST">
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
            <form action="../core/actions/director_actions.php" method="POST">
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
