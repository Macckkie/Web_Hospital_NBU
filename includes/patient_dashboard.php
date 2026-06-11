<?php
// includes/patient_dashboard.php - Медицински картон, лекуващ лекар и сметка за престой на Пациента
?>
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
                        <p style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($p['doc_first'] . ' ' . $p['doc_last']); ?></p>
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
                <h3 class="chart-title">Сметка за лечение</h3>
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
