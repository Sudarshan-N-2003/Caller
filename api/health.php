<?php
// api/health.php — Simple health check
session_start();
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'session_active' => !empty($_SESSION['user_id']),
];

// Try DB connection
try {
    require_once __DIR__ . '/../includes/config.php';
    $db = getDB();
    $result = $db->query("SELECT 1 as test")->fetch();
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'error: ' . $e->getMessage();
}

echo json_encode($health, JSON_PRETTY_PRINT);
