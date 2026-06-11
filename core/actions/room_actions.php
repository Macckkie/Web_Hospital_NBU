<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/room_actions.php
if ($action === 'add_room' || $action === 'edit_room' || $action === 'delete_room') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    if ($action === 'add_room') {
        $room_number = trim($_POST['room_number']);
        $type = $_POST['type'];
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price_per_day']);

        if (!empty($room_number) && $capacity > 0 && $price >= 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type, capacity, price_per_day) VALUES (?, ?, ?, ?)");
                $stmt->execute([$room_number, $type, $capacity, $price]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Стаята беше добавена успешно!', 'rooms');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка: Стаята с този номер вероятно вече съществува.', 'rooms');
            }
        } else {
            redirectWithMessage('danger', 'Невалидни данни за стаята.', 'rooms');
        }
    }

    if ($action === 'edit_room') {
        $id = intval($_POST['room_id']);
        $room_number = trim($_POST['room_number']);
        $type = $_POST['type'];
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price_per_day']);

        if ($id > 0 && !empty($room_number) && $capacity > 0 && $price >= 0) {
            try {
                $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, type = ?, capacity = ?, price_per_day = ? WHERE id = ?");
                $stmt->execute([$room_number, $type, $capacity, $price, $id]);
                logActivity($pdo, $userId, $action, "Операцията беше успешна.");
                redirectWithMessage('success', 'Стаята беше актуализирана успешно!', 'rooms');
            } catch (Exception $e) {
                redirectWithMessage('danger', 'Грешка при актуализиране на стаята.', 'rooms');
            }
        }
    }

    if ($action === 'delete_room') {
        $id = intval($_POST['room_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Стаята беше изтрита успешно!', 'rooms');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Не можете да изтриете стая, в която в момента има настанени пациенти.', 'rooms');
        }
    }
}
?>
