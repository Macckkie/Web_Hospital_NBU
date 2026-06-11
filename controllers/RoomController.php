<?php
require_once 'BaseController.php';

class RoomController extends BaseController {

    public function addRoom() {
        $this->requireRole('admin');
        $room_number = trim($_POST['room_number']);
        $type = $_POST['type'];
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price_per_day']);

        if (!empty($room_number) && $capacity > 0 && $price >= 0) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO rooms (room_number, type, capacity, price_per_day) VALUES (?, ?, ?, ?)");
                $stmt->execute([$room_number, $type, $capacity, $price]);
                $this->redirectWithMessage('success', 'Стаята беше добавена успешно!', 'rooms');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: Стаята с този номер вероятно вече съществува.', 'rooms');
            }
        } else {
            $this->redirectWithMessage('danger', 'Невалидни данни за стаята.', 'rooms');
        }
    }

    public function editRoom() {
        $this->requireRole('admin');
        $id = intval($_POST['room_id']);
        $room_number = trim($_POST['room_number']);
        $type = $_POST['type'];
        $capacity = intval($_POST['capacity']);
        $price = floatval($_POST['price_per_day']);

        if ($id > 0 && !empty($room_number) && $capacity > 0 && $price >= 0) {
            try {
                $stmt = $this->pdo->prepare("UPDATE rooms SET room_number = ?, type = ?, capacity = ?, price_per_day = ? WHERE id = ?");
                $stmt->execute([$room_number, $type, $capacity, $price, $id]);
                $this->redirectWithMessage('success', 'Стаята беше актуализирана успешно!', 'rooms');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка при актуализиране на стаята.', 'rooms');
            }
        }
    }

    public function deleteRoom() {
        $this->requireRole('admin');
        $id = intval($_POST['room_id']);
        try {
            $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $this->redirectWithMessage('success', 'Стаята беше изтрита успешно!', 'rooms');
        } catch (Exception $e) {
            $this->redirectWithMessage('danger', 'Не можете да изтриете стая, в която в момента има настанени пациенти.', 'rooms');
        }
    }
}
?>
