<?php
require_once 'BaseController.php';

class HospitalController extends BaseController {
    
    public function editHospital() {
        $this->requireRole('admin');
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);

        if (!empty($name) && !empty($address)) {
            try {
                $stmt = $this->pdo->query("SELECT id FROM hospital_info LIMIT 1");
                $exists = $stmt->fetch();

                if ($exists) {
                    $update = $this->pdo->prepare("UPDATE hospital_info SET name = ?, address = ? WHERE id = ?");
                    $update->execute([$name, $address, $exists['id']]);
                } else {
                    $insert = $this->pdo->prepare("INSERT INTO hospital_info (name, address) VALUES (?, ?)");
                    $insert->execute([$name, $address]);
                }
                $this->redirectWithMessage('success', 'Данните за болницата бяха успешно актуализирани!', 'hospital');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'hospital');
            }
        } else {
            $this->redirectWithMessage('danger', 'Моля, попълнете всички полета за болницата.', 'hospital');
        }
    }

    public function editDirector() {
        $this->requireRole('admin');
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if (!empty($first_name) && !empty($last_name) && !empty($phone) && !empty($email)) {
            try {
                $stmt = $this->pdo->query("SELECT id FROM director_info LIMIT 1");
                $exists = $stmt->fetch();

                if ($exists) {
                    $update = $this->pdo->prepare("UPDATE director_info SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
                    $update->execute([$first_name, $last_name, $phone, $email, $exists['id']]);
                } else {
                    $insert = $this->pdo->prepare("INSERT INTO director_info (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)");
                    $insert->execute([$first_name, $last_name, $phone, $email]);
                }
                $this->redirectWithMessage('success', 'Данните за директора бяха успешно актуализирани!', 'director');
            } catch (Exception $e) {
                $this->redirectWithMessage('danger', 'Грешка: ' . $e->getMessage(), 'director');
            }
        } else {
            $this->redirectWithMessage('danger', 'Моля, попълнете всички полета за директора.', 'director');
        }
    }
}
?>
