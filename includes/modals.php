<?php
// includes/modals.php - Всички диалогови прозорци (Modals) за CRUD операциите
?>

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
                            <?php foreach ($data['departments'] as $d): ?>
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
                            <?php foreach ($data['departments'] as $d): ?>
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
