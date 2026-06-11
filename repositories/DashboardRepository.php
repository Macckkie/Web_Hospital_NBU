<?php
/** @var PDO $pdo */
// repositories/DashboardRepository.php

class DashboardRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAdminData() {
        $data = [];

        $directorStmt = $this->pdo->query("SELECT * FROM director_info LIMIT 1");
        $data['director'] = $directorStmt->fetch() ?: ['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => ''];

        $roomsStmt = $this->pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number");
        $data['rooms'] = $roomsStmt->fetchAll();

        $deptsStmt = $this->pdo->query("SELECT d.*, doc.first_name as doc_first, doc.last_name as doc_last FROM departments d LEFT JOIN doctors doc ON d.head_doctor_id = doc.id ORDER BY d.name");
        $data['departments'] = $deptsStmt->fetchAll();

        $docsStmt = $this->pdo->query("SELECT d.*, dept.name as dept_name, u.username FROM doctors d LEFT JOIN departments dept ON d.department_id = dept.id LEFT JOIN users u ON d.user_id = u.id ORDER BY d.first_name");
        $data['doctors'] = $docsStmt->fetchAll();

        $patsStmt = $this->pdo->query("SELECT p.*, d.first_name as doc_first, d.last_name as doc_last, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id ORDER BY p.status, p.admission_date DESC");
        $data['patients'] = $patsStmt->fetchAll();

        $staffStmt = $this->pdo->query("SELECT s.*, dept.name as dept_name, u.username FROM staff s LEFT JOIN departments dept ON s.department_id = dept.id LEFT JOIN users u ON s.user_id = u.id ORDER BY s.role, s.first_name");
        $data['staff'] = $staffStmt->fetchAll();

        $shiftsStmt = $this->pdo->query("SELECT s.*, doc.first_name, doc.last_name, dept.name as dept_name FROM doctor_shifts s JOIN doctors doc ON s.doctor_id = doc.id LEFT JOIN departments dept ON doc.department_id = dept.id ORDER BY s.shift_date DESC, s.shift_type");
        $data['shifts'] = $shiftsStmt->fetchAll();

        $usersStmt = $this->pdo->query("SELECT * FROM users ORDER BY role, username");
        $data['users'] = $usersStmt->fetchAll();

        $statsDeptStmt = $this->pdo->query("SELECT dept.name, COUNT(p.id) as count FROM departments dept LEFT JOIN patients p ON p.department_id = dept.id GROUP BY dept.id");
        $data['stats_departments'] = $statsDeptStmt->fetchAll();

        $statsDocStmt = $this->pdo->query("SELECT CONCAT(doc.first_name, ' ', doc.last_name) as doc_name, COUNT(p.id) as count FROM doctors doc LEFT JOIN patients p ON p.doctor_id = doc.id GROUP BY doc.id");
        $data['stats_doctors'] = $statsDocStmt->fetchAll();

        $statsStatusStmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM patients GROUP BY status");
        $data['stats_status'] = $statsStatusStmt->fetchAll();

        return $data;
    }

    public function getDirectorData($filterDeptId = null, $filterDocId = null) {
        $data = [];
        $data['departments'] = $this->pdo->query("SELECT d.*, doc.first_name as doc_first, doc.last_name as doc_last FROM departments d LEFT JOIN doctors doc ON d.head_doctor_id = doc.id ORDER BY d.name")->fetchAll();
        $data['doctors'] = $this->pdo->query("SELECT d.*, dept.name as dept_name FROM doctors d LEFT JOIN departments dept ON d.department_id = dept.id ORDER BY d.first_name")->fetchAll();
        $data['patients'] = $this->pdo->query("SELECT p.*, d.first_name as doc_first, d.last_name as doc_last, dept.name as dept_name, r.room_number FROM patients p LEFT JOIN doctors d ON p.doctor_id = d.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id ORDER BY p.status, p.admission_date DESC")->fetchAll();
        $data['staff'] = $this->pdo->query("SELECT s.*, dept.name as dept_name FROM staff s LEFT JOIN departments dept ON s.department_id = dept.id ORDER BY s.role, s.first_name")->fetchAll();
        $data['rooms'] = $this->pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();

        $statsQuery = "SELECT 
            SUM(CASE WHEN status = 'admitted' THEN 1 ELSE 0 END) as admitted_count,
            SUM(CASE WHEN status = 'cured' THEN 1 ELSE 0 END) as cured_count,
            COUNT(*) as total_count 
            FROM patients WHERE 1=1";
        
        $params = [];
        if ($filterDeptId) {
            $statsQuery .= " AND department_id = ?";
            $params[] = $filterDeptId;
        }
        if ($filterDocId) {
            $statsQuery .= " AND doctor_id = ?";
            $params[] = $filterDocId;
        }

        $statsStmt = $this->pdo->prepare($statsQuery);
        $statsStmt->execute($params);
        $data['stats'] = $statsStmt->fetch() ?: ['admitted_count' => 0, 'cured_count' => 0, 'total_count' => 0];

        return $data;
    }

    public function getDoctorData($doctorId) {
        $data = [];
        if (!$doctorId) return $data;

        $patStmt = $this->pdo->prepare("SELECT p.*, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.doctor_id = ? ORDER BY p.status, p.admission_date DESC");
        $patStmt->execute([$doctorId]);
        $data['my_patients'] = $patStmt->fetchAll();

        $shStmt = $this->pdo->prepare("SELECT * FROM doctor_shifts WHERE doctor_id = ? AND shift_date >= CURRENT_DATE ORDER BY shift_date ASC");
        $shStmt->execute([$doctorId]);
        $data['my_shifts'] = $shStmt->fetchAll();

        $data['rooms'] = $this->pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();
        $data['departments'] = $this->pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

        $curedCnt = 0;
        $admittedCnt = 0;
        foreach ($data['my_patients'] as $p) {
            if ($p['status'] === 'cured') $curedCnt++;
            else $admittedCnt++;
        }
        $data['stats'] = ['admitted' => $admittedCnt, 'cured' => $curedCnt, 'total' => count($data['my_patients'])];

        return $data;
    }

    public function getNurseData($staffId) {
        $data = [];
        $nurseStmt = $this->pdo->prepare("SELECT department_id FROM staff WHERE id = ?");
        $nurseStmt->execute([$staffId]);
        $nurse = $nurseStmt->fetch();
        $deptId = $nurse['department_id'] ?? null;

        if ($deptId) {
            $patStmt = $this->pdo->prepare("SELECT p.*, doc.first_name as doc_first, doc.last_name as doc_last, r.room_number FROM patients p LEFT JOIN doctors doc ON p.doctor_id = doc.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.department_id = ? ORDER BY p.status, p.admission_date DESC");
            $patStmt->execute([$deptId]);
            $data['dept_patients'] = $patStmt->fetchAll();

            $docStmt = $this->pdo->prepare("SELECT d.* FROM doctors d WHERE d.department_id = ?");
            $docStmt->execute([$deptId]);
            $data['dept_doctors'] = $docStmt->fetchAll();

            $shiftStmt = $this->pdo->prepare("SELECT s.*, doc.first_name, doc.last_name FROM doctor_shifts s JOIN doctors doc ON s.doctor_id = doc.id WHERE doc.department_id = ? ORDER BY s.shift_date DESC");
            $shiftStmt->execute([$deptId]);
            $data['dept_shifts'] = $shiftStmt->fetchAll();

            $dNameStmt = $this->pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $dNameStmt->execute([$deptId]);
            $data['department_name'] = $dNameStmt->fetchColumn();
        } else {
            $data['dept_patients'] = [];
            $data['dept_doctors'] = [];
            $data['dept_shifts'] = [];
            $data['department_name'] = 'Невъведено';
        }
        return $data;
    }

    public function getMaintenanceData() {
        $data = [];
        $data['rooms'] = $this->pdo->query("SELECT r.*, (SELECT COUNT(*) FROM patients WHERE room_id = r.id AND status = 'admitted') as occupied FROM rooms r ORDER BY r.room_number")->fetchAll();
        return $data;
    }

    public function getPatientData($userId) {
        $data = [];
        $patStmt = $this->pdo->prepare("SELECT p.*, doc.first_name as doc_first, doc.last_name as doc_last, doc.phone as doc_phone, dept.name as dept_name, r.room_number, r.price_per_day FROM patients p LEFT JOIN doctors doc ON p.doctor_id = doc.id LEFT JOIN departments dept ON p.department_id = dept.id LEFT JOIN rooms r ON p.room_id = r.id WHERE p.user_id = ?");
        $patStmt->execute([$userId]);
        $data['patient_record'] = $patStmt->fetch();
        return $data;
    }
}
?>
