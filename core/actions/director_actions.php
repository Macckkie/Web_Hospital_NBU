<?php
require_once 'action_helper.php';
/** @var string $action */
/** @var string $role */
/** @var int $userId */
/** @var PDO $pdo */
// core/actions/director_actions.php
if ($action === 'edit_director') {
    if ($role !== 'admin') redirectWithMessage('danger', 'Нямате достъп до тази операция.');

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($email)) {
        try {
            $stmt = $pdo->query("SELECT id FROM director_info LIMIT 1");
            $exists = $stmt->fetch();

            if ($exists) {
                $update = $pdo->prepare("UPDATE director_info SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
                $update->execute([$first_name, $last_name, $phone, $email, $exists['id']]);
            } else {
                $insert = $pdo->prepare("INSERT INTO director_info (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)");
                $insert->execute([$first_name, $last_name, $phone, $email]);
            }
            logActivity($pdo, $userId, $action, "Операцията беше успешна.");
            redirectWithMessage('success', 'Данните за директора бяха успешно актуализирани!', 'director');
        } catch (Exception $e) {
            redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'director');
        }
    } else {
        redirectWithMessage('danger', 'Моля, попълнете всички полета за директора.', 'director');
    }
}
?>
