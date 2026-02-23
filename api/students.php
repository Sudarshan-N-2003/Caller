<?php
// api/students.php

session_start();

/* ───────────── CORS (Render + Sessions Safe) ───────────── */
header('Content-Type: application/json');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

/* ───────────── AUTH CHECK ───────────── */
if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':    listStudents(); break;
    case 'summary': summaryStats(); break;
    case 'add':     addStudent(); break;
    case 'assign':  assignStudent(); break;
    case 'export':  exportExcel(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

/* ───────────── LIST STUDENTS ───────────── */
function listStudents() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();

    $stmt = $db->query("
        SELECT s.*, u.name as assigned_name
        FROM students s
        LEFT JOIN users u ON u.id = s.assigned_to
        ORDER BY s.created_at DESC
    ");

    jsonResponse($stmt->fetchAll());
}

/* ───────────── SUMMARY ───────────── */
function summaryStats() {
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();

    $stmt = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='callback' THEN 1 ELSE 0 END) as callback,
            SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned
        FROM students
    ");

    jsonResponse($stmt->fetch());
}

/* ───────────── ADD STUDENT ───────────── */
function addStudent() {

    if (!in_array($_SESSION['role'], ['admin','office'])) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['mobile'])) {
        jsonResponse(['error' => 'Name and mobile required'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO students
        (name, mobile, present_college, college_type, address, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        sanitize($data['name']),
        sanitize($data['mobile']),
        sanitize($data['present_college'] ?? ''),
        $data['college_type'] ?? 'Other',
        sanitize($data['address'] ?? ''),
        $_SESSION['user_id']
    ]);

    jsonResponse(['success' => true]);
}

/* ───────────── ASSIGN STUDENT ───────────── */
function assignStudent() {

    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $student_id = intval($data['student_id'] ?? 0);
    $user_id    = intval($data['user_id'] ?? 0);

    if (!$student_id || !$user_id) {
        jsonResponse(['error' => 'Required fields missing'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("
        UPDATE students
        SET assigned_to = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$user_id, $student_id]);

    jsonResponse(['success' => true]);
}

/* ───────────── EXPORT CSV ───────────── */
function exportExcel() {

    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $db = getDB();

    $stmt = $db->query("
        SELECT name, mobile, present_college, college_type, address, status
        FROM students
        ORDER BY created_at DESC
    ");

    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=\"students_export_'.date('Ymd').'.csv\"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Mobile','College','Type','Address','Status']);

    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }

    fclose($out);
    exit;
}
