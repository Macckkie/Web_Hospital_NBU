<?php
/** @var array $data */
// includes/nurse_dashboard.php - Изгледи за медицинска сестра (пациенти и графици в нейното отделение)
?>
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
                            <td><?php echo htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']); ?></td>
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
                            <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                            <td><?php echo $s['shift_date']; ?></td>
                            <td>
                                <?php 
                                $sh = ['morning'=>'Сутрешна смяна', 'afternoon'=>'Следобедна', 'night'=>'Нощна'];
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
