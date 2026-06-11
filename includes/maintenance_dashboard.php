<?php
/** @var array $data */
// includes/maintenance_dashboard.php - Изглед за отдел поддръжка (статус и заетост на леглата в стаите)
?>
<!-- Таб: Статус на стаите -->
<section id="maint-rooms" class="tab-content active">
    <div class="page-header">
        <div>
            <h1 class="page-title">🚪 Статус и Заетост на Болните Стаи</h1>
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
