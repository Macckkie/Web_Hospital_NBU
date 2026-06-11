<?php
// includes/sidebar.php - Динамично странично меню според ролята
?>
<aside class="sidebar">
    <div class="sidebar-logo" style="border-bottom: 1px solid #1e293b; padding-bottom: 24px; margin-bottom: 24px;">
        <div class="sidebar-logo-icon" style="background-color: #3b82f6; border-radius: 4px; font-weight: bold;">H</div>
        <div class="sidebar-title" style="font-weight: 600;">
            Болница
            <span style="font-size: 9px; color: #64748b; margin-top: 4px;"><?php echo htmlspecialchars(mb_strimwidth(mb_strtoupper($hospital['name']), 0, 22, "...")); ?></span>
        </div>
    </div>

    <div class="user-profile-badge" style="background: none; padding: 0; margin-bottom: 32px; border-bottom: 1px solid #1e293b; padding-bottom: 24px; border-radius: 0;">
        <div style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Влязъл потребител</div>
        <div class="user-profile-name" style="font-size: 15px; color: #e2e8f0; font-weight: 600; margin-bottom: 2px;"><?php echo htmlspecialchars($fullName); ?></div>
        <div class="user-profile-role" style="font-size: 12px; color: #3b82f6; text-transform: none; font-weight: normal; letter-spacing: 0;">
            <?php 
            $roleNames = [
                'admin' => 'Администратор',
                'director' => 'Директор',
                'doctor' => 'Лекар',
                'nurse' => 'Медицинска сестра',
                'maintenance' => 'Поддръжка',
                'patient' => 'Пациент'
            ];
            echo $roleNames[$role] ?? $role; 
            ?>
        </div>
    </div>

    <nav style="flex-grow: 1;">
        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; padding-left: 16px;">Меню</div>
        <ul class="sidebar-menu">
            <!-- Бутони за Администратор -->
            <?php if ($role === 'admin'): ?>
                <li class="sidebar-link active" data-target="dashboard-overview"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Общ преглед</li>
                <li class="sidebar-link" data-target="patients-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Пациенти</li>
                <li class="sidebar-link" data-target="doctors-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Лекари</li>
                <li class="sidebar-link" data-target="staff-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Персонал</li>
                <li class="sidebar-link" data-target="departments-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Отделения</li>
                <li class="sidebar-link" data-target="rooms-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Стаи</li>
                <li class="sidebar-link" data-target="shifts-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Дежурства</li>
                <li class="sidebar-link" data-target="users-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Потребители</li>
                <li class="sidebar-link" data-target="hospital-sec"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Настройки</li>
            
            <!-- Бутони за Директор -->
            <?php elseif ($role === 'director'): ?>
                <li class="sidebar-link active" data-target="director-stats"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Анализ</li>
                <li class="sidebar-link" data-target="director-pats"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Пациенти</li>
                <li class="sidebar-link" data-target="director-docs"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Лекари</li>
                <li class="sidebar-link" data-target="director-depts"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Отделения</li>
                <li class="sidebar-link" data-target="director-staff"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Служители</li>
                <li class="sidebar-link" data-target="director-rooms"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Стаи</li>

            <!-- Бутони за Лекар -->
            <?php elseif ($role === 'doctor'): ?>
                <li class="sidebar-link active" data-target="doc-mypatients"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Моите Пациенти</li>
                <li class="sidebar-link" data-target="doc-myshifts"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> График дежурства</li>

            <!-- Бутони за Сестра -->
            <?php elseif ($role === 'nurse'): ?>
                <li class="sidebar-link active" data-target="nurse-patients"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Пациенти в отделението</li>
                <li class="sidebar-link" data-target="nurse-shifts"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Дежурства</li>

            <!-- Бутони за Поддръжка -->
            <?php elseif ($role === 'maintenance'): ?>
                <li class="sidebar-link active" data-target="maint-rooms"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Статус на Стаите</li>

            <!-- Бутони за Пациент -->
            <?php elseif ($role === 'patient'): ?>
                <li class="sidebar-link active" data-target="patient-card"><span style="font-size: 10px; margin-right: 8px; color: #94a3b8;">●</span> Моят Картон</li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer" style="padding-top: 24px; margin-top: 16px; border-top: 1px solid #1e293b;">
        <a href="../auth/logout.php" class="sidebar-link" style="color: #e2e8f0; padding: 8px 16px;">
            <svg viewBox="0 0 24 24" style="width:18px; height:18px; fill:currentColor; margin-right:8px; transform: scaleX(-1);">
                <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"/>
            </svg>
            Изход
        </a>
    </div>
</aside>
