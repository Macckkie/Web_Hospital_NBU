// js/script.js - Клиентски интерактивни контроли за Web_Hospital_NBU

document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------
    // 1. АКТИВИРАНЕ НА ТАБОВЕТЕ В SIDEBAR (С ПОДДРЪЖКА НА ХЕШ / ЛОКАЛНО СТОРИДЖ)
    // -------------------------------------------------------------
    const sidebarLinks = document.querySelectorAll('.sidebar-link[data-target]');
    const tabContents = document.querySelectorAll('.tab-content');

    function switchTab(targetId) {
        if (!targetId) return;

        // Премахваме активния клас от менюто
        sidebarLinks.forEach(link => {
            if (link.getAttribute('data-target') === targetId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Показваме само избрания таб
        tabContents.forEach(content => {
            if (content.id === targetId) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });

        // Запазваме активния таб в localStorage
        localStorage.setItem('activeHospitalTab', targetId);
    }

    // Клик събития за линковете в менюто
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            switchTab(target);
            // Променяме URL хеша без презареждане
            window.location.hash = target;
        });
    });

    // Автоматично отваряне на таб според URL Хеш или localStorage
    let initialTab = '';
    if (window.location.hash) {
        initialTab = window.location.hash.substring(1);
    } else {
        initialTab = localStorage.getItem('activeHospitalTab');
    }

    // Ако имаме намерен таб в списъка, го отваряме
    if (initialTab && document.getElementById(initialTab)) {
        switchTab(initialTab);
    }

    // Автоматично скриване на системното известие след 5 секунди
    const systemAlert = document.getElementById('system-alert');
    if (systemAlert) {
        setTimeout(() => {
            systemAlert.style.transition = 'opacity 0.5s ease';
            systemAlert.style.opacity = '0';
            setTimeout(() => {
                systemAlert.style.display = 'none';
            }, 500);
        }, 5000);
    }
});

// -------------------------------------------------------------
// 2. ОТВАРЯНЕ И ЗАТВАРЯНЕ НА МОДАЛНИ ДИАЛОЗИ
// -------------------------------------------------------------
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Спира скрола на бодито
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto'; // Пуска скрола на бодито
    }
}

// Затваряне на модал при кликване извън неговата кутия
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// -------------------------------------------------------------
// 3. ФУНКЦИИ ЗА ДИНАМИЧНО ЗАРЕЖДАНЕ НА ДАННИ В МОДАЛИТЕ ЗА РЕДАКЦИЯ
// -------------------------------------------------------------

// Редакция на Пациент
function openEditPatient(patient) {
    document.getElementById('edit_pat_id').value = patient.id;
    document.getElementById('edit_pat_first').value = patient.first_name;
    document.getElementById('edit_pat_last').value = patient.last_name;
    document.getElementById('edit_pat_phone').value = patient.phone;
    document.getElementById('edit_pat_email').value = patient.email;
    document.getElementById('edit_pat_illness').value = patient.illness;
    document.getElementById('edit_pat_treatment').value = patient.treatment;
    
    // Селектиране на отделение, стая, лекар
    if (document.getElementById('edit_pat_dept')) {
        document.getElementById('edit_pat_dept').value = patient.department_id || '';
    }
    if (document.getElementById('edit_pat_room')) {
        document.getElementById('edit_pat_room').value = patient.room_id || '';
    }
    if (document.getElementById('edit_pat_doc')) {
        document.getElementById('edit_pat_doc').value = patient.doctor_id || '';
    }
    if (document.getElementById('edit_pat_cost')) {
        document.getElementById('edit_pat_cost').value = patient.treatment_cost || '0.00';
    }

    openModal('editPatientModal');
}

// Редакция на Лекар
function openEditDoctor(doctor) {
    document.getElementById('edit_doc_id').value = doctor.id;
    document.getElementById('edit_doc_first').value = doctor.first_name;
    document.getElementById('edit_doc_last').value = doctor.last_name;
    document.getElementById('edit_doc_phone').value = doctor.phone;
    document.getElementById('edit_doc_email').value = doctor.email;
    document.getElementById('edit_doc_qual').value = doctor.qualification;
    document.getElementById('edit_doc_dept').value = doctor.department_id || '';

    openModal('editDoctorModal');
}

// Редакция на Персонал (Сестри и Поддръжка)
function openEditStaff(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_staff_first').value = staff.first_name;
    document.getElementById('edit_staff_last').value = staff.last_name;
    document.getElementById('edit_staff_phone').value = staff.phone;
    document.getElementById('edit_staff_dept').value = staff.department_id || '';

    openModal('editStaffModal');
}

// Редакция на Отделение
function openEditDept(dept) {
    document.getElementById('edit_dept_id').value = dept.id;
    document.getElementById('edit_dept_name').value = dept.name;
    document.getElementById('edit_dept_head').value = dept.head_doctor_id || '';

    openModal('editDeptModal');
}

// Редакция на Стая
function openEditRoom(room) {
    document.getElementById('edit_room_id').value = room.id;
    document.getElementById('edit_room_num').value = room.room_number;
    document.getElementById('edit_room_type').value = room.type;
    document.getElementById('edit_room_cap').value = room.capacity;
    document.getElementById('edit_room_price').value = room.price_per_day;

    openModal('editRoomModal');
}

// Редакция на Потребителски акаунт
function openEditUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_user_name').value = user.username;
    document.getElementById('edit_user_role').value = user.role;

    openModal('editUserModal');
}

// -------------------------------------------------------------
// 4. ТЪРСЕНЕ И ФИЛТРИРАНЕ НА ТАБЛИЦИ В РЕАЛНО ВРЕМЕ
// -------------------------------------------------------------
function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const trs = table.getElementsByTagName('tr');

    // Преминаваме през всички редове (без заглавния)
    for (let i = 1; i < trs.length; i++) {
        let match = false;
        const tds = trs[i].getElementsByTagName('td');
        
        for (let j = 0; j < tds.length - 1; j++) { // пропускаме последната колона с бутони
            if (tds[j]) {
                const textValue = tds[j].textContent || tds[j].innerText;
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    match = true;
                    break;
                }
            }
        }
        
        if (match) {
            trs[i].style.display = "";
        } else {
            trs[i].style.display = "none";
        }
    }
}
