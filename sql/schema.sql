-- sql/schema.sql - Скрипт за създаване на базата данни и попълване с начални данни за Web_Hospital_NBU
-- Този файл е оптимизиран за лесно четене и поддръжка. Всички таблици са подредени логически.

CREATE DATABASE IF NOT EXISTS `hospital_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hospital_db`;

-- ==============================================================================
-- 1. СИСТЕМНИ ТАБЛИЦИ (Потребители и Лог)
-- ==============================================================================


CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50) UNIQUE NOT NULL COMMENT 'Уникално потребителско име за вход',
    `password`   VARCHAR(255) NOT NULL COMMENT 'Криптирана парола (bcrypt)',
    `role`       ENUM('admin', 'director', 'doctor', 'nurse', 'maintenance', 'patient') NOT NULL COMMENT 'Права на достъп',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL COMMENT 'ID на потребителя, извършил действието',
    `action`     VARCHAR(100) NOT NULL COMMENT 'Кратко описание (напр. "Добавен пациент")',
    `details`    TEXT DEFAULT NULL COMMENT 'Допълнителни детайли (напр. име на пациента)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Точно време на събитието',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================================
-- 2. ИНФРАСТРУКТУРА (Болница, Отделения и Стаи)
-- ==============================================================================


CREATE TABLE IF NOT EXISTS `hospital_info` (
    `id`      INT AUTO_INCREMENT PRIMARY KEY,
    `name`    VARCHAR(100) NOT NULL COMMENT 'Име на лечебното заведение',
    `address` VARCHAR(255) NOT NULL COMMENT 'Точен адрес'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `departments` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `name`           VARCHAR(100) NOT NULL COMMENT 'Наименование на отделението',
    `head_doctor_id` INT DEFAULT NULL COMMENT 'ID на лекаря, който е началник на отделението'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `rooms` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `room_number`   VARCHAR(10) UNIQUE NOT NULL COMMENT 'Уникален номер на стаята',
    `type`          ENUM('regular', 'operating', 'icu') NOT NULL COMMENT 'Тип на стаята (обикновена, операционна, интензивно)',
    `capacity`      INT NOT NULL COMMENT 'Брой легла',
    `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 50.00 COMMENT 'Цена на ден в лева'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================================
-- 3. ПЕРСОНАЛ (Директор, Лекари, Сестри и Поддръжка)
-- ==============================================================================


CREATE TABLE IF NOT EXISTS `director_info` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name`  VARCHAR(50) NOT NULL,
    `phone`      VARCHAR(20) NOT NULL,
    `email`      VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `doctors` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNIQUE DEFAULT NULL COMMENT 'Връзка към профила за вход',
    `unique_doc_number` VARCHAR(20) UNIQUE NOT NULL COMMENT 'Уникален идентификационен номер (УИН)',
    `first_name`        VARCHAR(50) NOT NULL,
    `last_name`         VARCHAR(50) NOT NULL,
    `phone`             VARCHAR(20) NOT NULL,
    `email`             VARCHAR(100) NOT NULL,
    `qualification`     VARCHAR(100) NOT NULL COMMENT 'Специалност и титла',
    `department_id`     INT DEFAULT NULL COMMENT 'Към кое отделение принадлежи',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `departments` ADD CONSTRAINT `fk_departments_head_doctor`
FOREIGN KEY (`head_doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `staff` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNIQUE DEFAULT NULL,
    `first_name`    VARCHAR(50) NOT NULL,
    `last_name`     VARCHAR(50) NOT NULL,
    `role`          ENUM('nurse', 'maintenance') NOT NULL,
    `phone`         VARCHAR(20) NOT NULL,
    `department_id` INT DEFAULT NULL COMMENT 'Може да е NULL за персонал по поддръжка',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `doctor_shifts` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id`  INT NOT NULL,
    `shift_date` DATE NOT NULL,
    `shift_type` ENUM('morning', 'afternoon', 'night') NOT NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================================
-- 4. ПАЦИЕНТИ И ЛЕЧЕНИЕ
-- ==============================================================================


CREATE TABLE IF NOT EXISTS `patients` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`               INT UNIQUE DEFAULT NULL COMMENT 'Профил за портала за пациенти',
    `unique_patient_number` VARCHAR(20) UNIQUE NOT NULL COMMENT 'Уникален номер на пациента (ЕГН/ЛНЧ)',
    `first_name`            VARCHAR(50) NOT NULL,
    `last_name`             VARCHAR(50) NOT NULL,
    `phone`                 VARCHAR(20) NOT NULL,
    `email`                 VARCHAR(100) NOT NULL,
    `illness`               VARCHAR(255) NOT NULL COMMENT 'Диагноза',
    `treatment`             TEXT NOT NULL COMMENT 'Назначено лечение',
    `doctor_id`             INT DEFAULT NULL COMMENT 'Лекуващ лекар',
    `department_id`         INT DEFAULT NULL COMMENT 'Отделение',
    `room_id`               INT DEFAULT NULL COMMENT 'Стая, в която е настанен',
    `admission_date`        DATE NOT NULL COMMENT 'Дата на приемане',
    `discharge_date`        DATE DEFAULT NULL COMMENT 'Дата на изписване',
    `status`                ENUM('admitted', 'cured') NOT NULL DEFAULT 'admitted',
    `treatment_cost`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 5. ВЪВЕЖДАНЕ НА ДЕМО ДАННИ
-- ==============================================================================

INSERT INTO `hospital_info` (`name`, `address`) VALUES
('Университетска болница "Здраве" - НБУ', 'гр. София, ул. Монтевидео №21');

INSERT INTO `director_info` (`first_name`, `last_name`, `phone`, `email`) VALUES
('Проф. д-р Димитър', 'Георгиев', '0888 123 456', 'director@health-nbu.bg');


INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'admin'),
(2, 'director', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'director'),
(3, 'dr.ivanov', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'doctor'),
(4, 'dr.petrova', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'doctor'),
(5, 'nurse.stoyanova', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'nurse'),
(6, 'maint.petrov', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'maintenance'),
(7, 'patient.dimitrov', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'patient'),
(8, 'patient.georgieva', '$2y$10$tZdYIgp6FPbh.CvpKyw5Aufr7.zoYVDEDoG77Id7hZwEtTGIdTgwK', 'patient');


INSERT INTO `rooms` (`id`, `room_number`, `type`, `capacity`, `price_per_day`) VALUES
(1, '101', 'regular', 3, 40.00),
(2, '102', 'regular', 2, 45.00),
(3, '201', 'icu', 2, 120.00),
(4, '202', 'icu', 1, 150.00),
(5, '301', 'operating', 1, 250.00),
(6, '302', 'operating', 1, 250.00);

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Кардиология'),
(2, 'Хирургия'),
(3, 'Интензивно отделение (ОАИЛ)');

INSERT INTO `doctors` (`id`, `user_id`, `unique_doc_number`, `first_name`, `last_name`, `phone`, `email`, `qualification`, `department_id`) VALUES
(1, 3, 'DOC10001', 'Д-р Иван', 'Иванов', '0877 111 222', 'dr.ivanov@health-nbu.bg', 'Кардиолог - доцент', 1),
(2, 4, 'DOC10002', 'Д-р Мария', 'Петрова', '0877 333 444', 'dr.petrova@health-nbu.bg', 'Хирург - главен асистент', 2);

UPDATE `departments` SET `head_doctor_id` = 1 WHERE `id` = 1;
UPDATE `departments` SET `head_doctor_id` = 2 WHERE `id` = 2;

INSERT INTO `staff` (`id`, `user_id`, `first_name`, `last_name`, `role`, `phone`, `department_id`) VALUES
(1, 5, 'Елена', 'Стоянова', 'nurse', '0899 444 555', 1),
(2, 6, 'Петър', 'Петров', 'maintenance', '0899 666 777', 3);

INSERT INTO `doctor_shifts` (`doctor_id`, `shift_date`, `shift_type`) VALUES
(1, '2026-05-31', 'morning'),
(2, '2026-05-31', 'night'),
(1, '2026-06-01', 'afternoon'),
(2, '2026-06-02', 'morning'),
(1, '2026-06-03', 'night'),
(2, '2026-06-04', 'afternoon');

INSERT INTO `patients` (`id`, `user_id`, `unique_patient_number`, `first_name`, `last_name`, `phone`, `email`, `illness`, `treatment`, `doctor_id`, `department_id`, `room_id`, `admission_date`, `discharge_date`, `status`, `treatment_cost`) VALUES
(1, 7, 'PAT20001', 'Георги', 'Димитров', '0887 777 888', 'g.dimitrov@nbu.bg', 'Артериална хипертония', 'Медикаментозно лечение и наблюдение', 1, 1, 1, '2026-05-26', '2026-05-31', 'cured', 150.00),
(2, 8, 'PAT20002', 'Анна', 'Георгиева', '0887 999 000', 'a.georgieva@nbu.bg', 'Остър апендицит', 'Апендектомия и следоперативно възстановяване', 2, 2, 2, '2026-05-29', NULL, 'admitted', 680.00);

INSERT INTO `activity_logs` (`user_id`, `action`, `details`) VALUES
(1, 'Системна инициализация', 'Базата данни беше успешно създадена и попълнена с демо данни.');
