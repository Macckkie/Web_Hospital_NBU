<?php
// includes/director_dashboard.php - Изгледи и статистически анализи за Директор
?>
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
