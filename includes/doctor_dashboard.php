<?php
// includes/doctor_dashboard.php - Пациенти, график и статистики за съответния Лекар
?>
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
                                            <form action="../core/actions/patient_actions.php" method="POST">
                                                <input type="hidden" name="action" value="cure_patient">
                                                <input type="hidden" name="patient_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn btn-accent btn-sm" title="Отбележи като излекуван">✓</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="bill.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" title="Сметка за лечение" target="_blank">🧾</a>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditPatient(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️</button>
                                        <form action="../core/actions/patient_actions.php" method="POST" onsubmit="return confirm('Изтриване на пациента?')">
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
                                $shL = ['morning' => 'Сутрешна смяна (06:00 - 14:00)', 'afternoon' => 'Следобедна смяна (14:00 - 22:00)', 'night' => 'Нощно дежурство (22:00 - 06:00)'];
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
