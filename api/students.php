<?php
// api/students.php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':          addStudent(); break;
    case 'bulk_add':     bulkAddStudents(); break;
    case 'list':         listStudents(); break;
    case 'my_list':      myStudentList(); break;
    case 'detail':       studentDetail(); break;
    case 'assign':       assignStudent(); break;
    case 'auto_assign':  autoAssign(); break;
    case 'export':       exportExcel(); break;
    case 'summary':      summaryStats(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

/**
 * Add a single student with auto-assignment to least-loaded telecaller
 */
function addStudent() {
    if (!in_array($_SESSION['role'], ['admin','office'])) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $name    = sanitize($data['name'] ?? '');
    $mobile  = sanitize($data['mobile'] ?? '');
    $college = sanitize($data['present_college'] ?? '');
    $ctype   = $data['college_type'] ?? 'Other';
    $address = sanitize($data['address'] ?? '');

    if (!$name || !$mobile) {
        jsonResponse(['error' => 'Name and Mobile are required'], 400);
    }

    $db = getDB();

    // Get least-loaded telecaller for auto-assignment
    $stmt = $db->query(
        "SELECT u.id FROM users u
         LEFT JOIN students s ON s.assigned_to = u.id
         WHERE u.role = 'telecaller'
         GROUP BY u.id
         ORDER BY COUNT(s.id) ASC
         LIMIT 1"
    );
    $telecaller = $stmt->fetchColumn();

    // If no telecallers exist, leave unassigned
    $assigned_to = $telecaller ?: null;

    $stmt = $db->prepare(
        "INSERT INTO students (name, mobile, present_college, college_type, address, assigned_to, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $name,
        $mobile,
        $college,
        $ctype,
        $address,
        $assigned_to,
        $_SESSION['user_id']
    ]);

    jsonResponse([
        'success' => true,
        'id' => $db->lastInsertId(),
        'assigned_to' => $assigned_to
    ]);
}

/**
 * Bulk add students with round-robin auto-assignment
 */
function bulkAddStudents() {
    if (!in_array($_SESSION['role'], ['admin','office'])) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $students = $data['students'] ?? [];

    if (!is_array($students) || empty($students)) {
        jsonResponse(['error' => 'No students provided'], 400);
    }

    $db = getDB();

    // Get all telecallers ordered by current assignment count (least-loaded first)
    $stmt = $db->query(
        "SELECT u.id FROM users u
         LEFT JOIN students s ON s.assigned_to = u.id
         WHERE u.role = 'telecaller'
         GROUP BY u.id
         ORDER BY COUNT(s.id) ASC"
    );
    $telecallers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$telecallers) {
        jsonResponse(['error' => 'No telecallers available for assignment'], 400);
    }

    // Begin transaction for atomic bulk insert
    $db->beginTransaction();
    
    try {
        $insertStmt = $db->prepare(
            "INSERT INTO students (name, mobile, present_college, college_type, address, assigned_to, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $added = 0;
        $tcIndex = 0;

        foreach ($students as $s) {
            $name    = sanitize($s['name'] ?? '');
            $mobile  = sanitize($s['mobile'] ?? '');
            $college = sanitize($s['present_college'] ?? '');
            $ctype   = $s['college_type'] ?? 'Other';
            $address = sanitize($s['address'] ?? '');

            if (!$name || !$mobile) continue; // Skip invalid rows

            // Validate college type
            if (!in_array($ctype, ['PU', 'Diploma', 'Other'])) {
                $ctype = 'Other';
            }

            // Round-robin assignment to telecallers
            $assigned_to = $telecallers[$tcIndex % count($telecallers)];
            $tcIndex++;

            $insertStmt->execute([
                $name,
                $mobile,
                $college,
                $ctype,
                $address,
                $assigned_to,
                $_SESSION['user_id']
            ]);
            $added++;
        }

        $db->commit();
        jsonResponse(['success' => true, 'added' => $added]);

    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * List all students (admin/office view)
 */
function listStudents() {
    if (!in_array($_SESSION['role'], ['admin','office'])) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();
    
    $search = sanitize($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $assigned = $_GET['assigned_to'] ?? '';

    $sql = "SELECT s.*, u.name as assigned_name,
                   (SELECT f.call_status FROM feedback f WHERE f.student_id = s.id ORDER BY f.created_at DESC LIMIT 1) as last_feedback
            FROM students s
            LEFT JOIN users u ON u.id = s.assigned_to
            WHERE 1=1";

    $params = [];

    if ($search) {
        $sql .= " AND (s.name LIKE ? OR s.mobile LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status) {
        $sql .= " AND s.status = ?";
        $params[] = $status;
    }

    if ($assigned) {
        $sql .= " AND s.assigned_to = ?";
        $params[] = $assigned;
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonResponse($stmt->fetchAll());
}

/**
 * List students assigned to current telecaller
 */
function myStudentList() {
    if ($_SESSION['role'] !== 'telecaller') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT s.*, 
                (SELECT f.call_status FROM feedback f WHERE f.student_id = s.id ORDER BY f.created_at DESC LIMIT 1) as last_feedback,
                (SELECT COUNT(*) FROM reminders r WHERE r.student_id = s.id AND r.telecaller_id = ? AND r.reminder_date = CURRENT_DATE AND r.is_notified = FALSE) as has_reminder_today
         FROM students s
         WHERE s.assigned_to = ?
         ORDER BY s.created_at DESC"
    );
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    
    $students = $stmt->fetchAll();
    
    // Count reminders due today
    $reminderStmt = $db->prepare(
        "SELECT COUNT(*) FROM reminders WHERE telecaller_id = ? AND reminder_date = CURRENT_DATE AND is_notified = FALSE"
    );
    $reminderStmt->execute([$_SESSION['user_id']]);
    $reminderCount = $reminderStmt->fetchColumn();

    jsonResponse([
        'students' => $students,
        'reminder_count' => $reminderCount
    ]);
}

/**
 * Get detailed info about a student
 */
function studentDetail() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'Student ID required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT s.*, u.name as assigned_name
         FROM students s
         LEFT JOIN users u ON u.id = s.assigned_to
         WHERE s.id = ?"
    );
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(['error' => 'Student not found'], 404);
    }

    jsonResponse($student);
}

/**
 * Manually assign a student to a telecaller
 */
function assignStudent() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $studentId = intval($data['student_id'] ?? 0);
    $telecallerId = intval($data['telecaller_id'] ?? 0);

    if (!$studentId || !$telecallerId) {
        jsonResponse(['error' => 'Student ID and Telecaller ID required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE students SET assigned_to = ? WHERE id = ?");
    $stmt->execute([$telecallerId, $studentId]);

    jsonResponse(['success' => true]);
}

/**
 * Auto-assign all unassigned students
 */
function autoAssign() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();

    // Get unassigned students
    $stmt = $db->query("SELECT id FROM students WHERE assigned_to IS NULL ORDER BY id ASC");
    $unassigned = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$unassigned) {
        jsonResponse(['success' => true, 'assigned' => 0]);
    }

    // Get telecallers (least-loaded first)
    $stmt = $db->query(
        "SELECT u.id FROM users u
         LEFT JOIN students s ON s.assigned_to = u.id
         WHERE u.role = 'telecaller'
         GROUP BY u.id
         ORDER BY COUNT(s.id) ASC"
    );
    $telecallers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$telecallers) {
        jsonResponse(['error' => 'No telecallers available'], 400);
    }

    // Round-robin assignment
    $upd = $db->prepare("UPDATE students SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
    $i = 0;
    
    foreach ($unassigned as $sid) {
        $tc = $telecallers[$i % count($telecallers)];
        $upd->execute([$tc, $sid]);
        $i++;
    }
    
    jsonResponse(['success' => true, 'assigned' => $i]);
}

/**
 * Export students to CSV
 */
function exportExcel() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();
    $stmt = $db->query(
        "SELECT s.name, s.mobile, s.present_college, s.college_type, s.address, s.status,
                u.name as assigned_to, s.created_at,
                (SELECT f.call_status FROM feedback f WHERE f.student_id = s.id ORDER BY f.created_at DESC LIMIT 1) as last_feedback
         FROM students s
         LEFT JOIN users u ON u.id = s.assigned_to
         ORDER BY s.created_at DESC"
    );
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export_'.date('Ymd').'.csv"');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Mobile','College','Type','Address','Status','Assigned To','Created At','Last Feedback']);
    
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    
    fclose($out);
    exit;
}

/**
 * Get summary statistics
 */
function summaryStats() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();
    $stmt = $db->query(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='callback' THEN 1 ELSE 0 END) as callback,
            SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned
         FROM students"
    );
    
    jsonResponse($stmt->fetch());
}
