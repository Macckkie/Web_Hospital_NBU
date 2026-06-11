<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/hospital_actions.php
if ($action === 'edit_hospital') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    $name = trim($_POST['name']);
    $address = trim($_POST['address']);

    if (!empty($name) && !empty($address)) {
        try {
            $stmt = $pdo->query("SELECT id FROM hospital_info LIMIT 1");
            $exists = $stmt->fetch();

            if ($exists) {
                $update = $pdo->prepare("UPDATE hospital_info SET name = ?, address = ? WHERE id = ?");
                $update->execute([$name, $address, $exists['id']]);
            } else {
                $insert = $pdo->prepare("INSERT INTO hospital_info (name, address) VALUES (?, ?)");
                $insert->execute([$name, $address]);
            }
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Данните за болницата бяха успешно актуализирани!', 'hospital');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'hospital');
        }
    } else {
        redirectWithMessage('danger', 'Моля, попълнете всички полета за болницата.', 'hospital');
    }
}
?>
