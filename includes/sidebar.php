<?php
// includes/sidebar.php - Динамично странично меню според ролята
?>
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
