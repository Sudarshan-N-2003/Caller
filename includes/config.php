<?php

// Show errors for now (REMOVE after everything works)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─────────────────────────────────────────────
// REQUIRED ENV VARIABLES (Render)
// ─────────────────────────────────────────────
$required = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'];

foreach ($required as $var) {
    if (!getenv($var)) {
        die("Missing environment variable: $var");
    }
}

define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// ─────────────────────────────────────────────
// BASE URL (Render only)
// ─────────────────────────────────────────────
define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST']);

// ─────────────────────────────────────────────
// DATABASE CONNECTION
// ─────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function authRequired(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION;
}

function adminRequired(): array {
    $session = authRequired();
    if (($session['role'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    return $session;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
