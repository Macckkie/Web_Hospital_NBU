-- Скрипт за създаване на базата данни и попълване с начални данни за Web_Hospital_NBU

CREATE DATABASE IF NOT EXISTS `hospital_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hospital_db`;

-- 1. Таблица с информация за болницата
CREATE TABLE IF NOT EXISTS `hospital_info` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Таблица с потребители (за вход в системата)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'director', 'doctor', 'nurse', 'maintenance', 'patient') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Таблица за стаите
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(10) UNIQUE NOT NULL,
    `type` ENUM('regular', 'operating', 'icu') NOT NULL,
    `capacity` INT NOT NULL,
    `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 50.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Таблица за отделениата (Ръководителят е външен ключ към лекарите, задава се на второ четене)
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `head_doctor_id` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Таблица за директора на болницата
CREATE TABLE IF NOT EXISTS `director_info` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Таблица за лекарите
CREATE TABLE IF NOT EXISTS `doctors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE DEFAULT NULL,
    `unique_doc_number` VARCHAR(20) UNIQUE NOT NULL, -- УИН
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `qualification` VARCHAR(100) NOT NULL,
    `department_id` INT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавяне на чужд ключ за ръководител на отделение (circular dependency resolution)
ALTER TABLE `departments` ADD CONSTRAINT `fk_departments_head_doctor`
FOREIGN KEY (`head_doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL;

-- 7. Таблица за пациентите
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE DEFAULT NULL,
    `unique_patient_number` VARCHAR(20) UNIQUE NOT NULL, -- Уникален номер
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `illness` VARCHAR(255) NOT NULL,
    `treatment` TEXT NOT NULL,
    `doctor_id` INT DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `room_id` INT DEFAULT NULL,
    `admission_date` DATE NOT NULL,
    `discharge_date` DATE DEFAULT NULL,
    `status` ENUM('admitted', 'cured') NOT NULL DEFAULT 'admitted',
    `treatment_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Таблица за допълнителния персонал (медицински сестри и поддръжка)
CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE DEFAULT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `role` ENUM('nurse', 'maintenance') NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `department_id` INT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Таблица за график за дежурства на лекарите
CREATE TABLE IF NOT EXISTS `doctor_shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `shift_date` DATE NOT NULL,
    `shift_type` ENUM('morning', 'afternoon', 'night') NOT NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==================== ВЪВЕЖДАНЕ НА ДЕМО ДАННИ ====================

-- Попълване на информация за болницата
INSERT INTO `hospital_info` (`name`, `address`) VALUES
('Университетска болница "Здраве" - НБУ', 'гр. София, ул. Монтевидео №21');

-- Попълване на директора
INSERT INTO `director_info` (`first_name`, `last_name`, `phone`, `email`) VALUES
('Проф. д-р Димитър', 'Георгиев', '0888 123 456', 'director@health-nbu.bg');

-- Създаване на потребители за вход в системата (паролите са криптирани с password_hash('password123', PASSWORD_DEFAULT))
-- 'admin123', 'director123', 'doctor123', 'doctor456', 'nurse123', 'maint123', 'patient123', 'patient456'
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$Y1s4C5jZ/NpxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'admin'),
(2, 'director', '$2y$10$vU8Hq4V9l2T19s6TzTqIxe2WdJ4GZtJ/R.t/V7w7Z1d2X.qL.gSJy', 'director'),
(3, 'dr.ivanov', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'doctor'),
(4, 'dr.petrova', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'doctor'),
(5, 'nurse.stoyanova', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'nurse'),
(6, 'maint.petrov', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'maintenance'),
(7, 'patient.dimitrov', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'patient'),
(8, 'patient.georgieva', '$2y$10$tZ3s7jZ9nPxXhU/TvxVSeYkGfR4wUo.x6fO7xW4y7D5d3fX.oD7C', 'patient');

-- Въвеждане на стаи
INSERT INTO `rooms` (`id`, `room_number`, `type`, `capacity`, `price_per_day`) VALUES
(1, '101', 'regular', 3, 40.00),
(2, '102', 'regular', 2, 45.00),
(3, '201', 'icu', 2, 120.00),
(4, '202', 'icu', 1, 150.00),
(5, '301', 'operating', 1, 250.00),
(6, '302', 'operating', 1, 250.00);

-- Въвеждане на отделения
INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Кардиология'),
(2, 'Хирургия'),
(3, 'Интензивно отделение (ОАИЛ)');

-- Въвеждане на лекари
INSERT INTO `doctors` (`id`, `user_id`, `unique_doc_number`, `first_name`, `last_name`, `phone`, `email`, `qualification`, `department_id`) VALUES
(1, 3, 'DOC10001', 'Д-р Иван', 'Иванов', '0877 111 222', 'dr.ivanov@health-nbu.bg', 'Кардиолог - доцент', 1),
(2, 4, 'DOC10002', 'Д-р Мария', 'Петрова', '0877 333 444', 'dr.petrova@health-nbu.bg', 'Хирург - главен асистент', 2);

-- Актуализиране на ръководителите на отделенията
UPDATE `departments` SET `head_doctor_id` = 1 WHERE `id` = 1;
UPDATE `departments` SET `head_doctor_id` = 2 WHERE `id` = 2;

-- Въвеждане на допълнителен персонал
INSERT INTO `staff` (`id`, `user_id`, `first_name`, `last_name`, `role`, `phone`, `department_id`) VALUES
(1, 5, 'Елена', 'Стоянова', 'nurse', '0899 444 555', 1),
(2, 6, 'Петър', 'Петров', 'maintenance', '0899 666 777', 3);

-- Въвеждане на дежурства на лекарите (май и юни 2026 г.)
INSERT INTO `doctor_shifts` (`doctor_id`, `shift_date`, `shift_type`) VALUES
(1, '2026-05-31', 'morning'),
(2, '2026-05-31', 'night'),
(1, '2026-06-01', 'afternoon'),
(2, '2026-06-02', 'morning'),
(1, '2026-06-03', 'night'),
(2, '2026-06-04', 'afternoon');

-- Въвеждане на пациенти
-- 1. Георги Димитров (приет в Кардиология, лекува се от Д-р Иванов, стая 101, приет преди 5 дни, изписан днес)
-- 2. Анна Георгиева (приета в Хирургия, лекува се от Д-р Петрова, стая 102, приета преди 2 дни, все още там)
INSERT INTO `patients` (`id`, `user_id`, `unique_patient_number`, `first_name`, `last_name`, `phone`, `email`, `illness`, `treatment`, `doctor_id`, `department_id`, `room_id`, `admission_date`, `discharge_date`, `status`, `treatment_cost`) VALUES
(1, 7, 'PAT20001', 'Георги', 'Димитров', '0887 777 888', 'g.dimitrov@nbu.bg', 'Артериална хипертония', 'Медикаментозно лечение и наблюдение', 1, 1, 1, '2026-05-26', '2026-05-31', 'cured', 150.00),
(2, 8, 'PAT20002', 'Анна', 'Георгиева', '0887 999 000', 'a.georgieva@nbu.bg', 'Остър апендицит', 'Апендектомия и следоперативно възстановяване', 2, 2, 2, '2026-05-29', NULL, 'admitted', 680.00);
