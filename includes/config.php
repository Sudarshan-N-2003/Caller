<?php

// ─────────────────────────────────────────────
// Show errors temporarily for debugging
// ⚠️ REMOVE in production
// ─────────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─────────────────────────────────────────────
// Load .env (Local development only)
// ─────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ─────────────────────────────────────────────
// Required Environment Variables
// ─────────────────────────────────────────────
$required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];

foreach ($required as $var) {
    if (!getenv($var)) {
        die("Missing required environment variable: $var");
    }
}

// ─────────────────────────────────────────────
// Database Constants
// ─────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// ─────────────────────────────────────────────
// Base URL
// ─────────────────────────────────────────────
function getBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http';

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

define('BASE_URL', getenv('APP_URL') ?: getBaseUrl());

// ─────────────────────────────────────────────
// Database Connection (PDO - PostgreSQL)
// ─────────────────────────────────────────────
function getDB(): PDO
{
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
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {

            http_response_code(500);
            header('Content-Type: application/json');

            echo json_encode([
                'error'  => 'Database connection failed.',
                'detail' => 'Check DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables.'
            ]);

            exit;
        }
    }

    return $pdo;
}

// ─────────────────────────────────────────────
// JSON Response Helper
// ─────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ─────────────────────────────────────────────
// Authentication Required
// session_start() must be called in each file
// ─────────────────────────────────────────────
function authRequired(): array
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized. Please log in.'], 401);
    }

    return $_SESSION;
}

// ─────────────────────────────────────────────
// Admin Required
// ─────────────────────────────────────────────
function adminRequired(): array
{
    $session = authRequired();

    if (($session['role'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Forbidden. Admin access required.'], 403);
    }

    return $session;
}

// ─────────────────────────────────────────────
// Role Check Helper
// Example: roleAllowed(['admin', 'staff'])
// ─────────────────────────────────────────────
function roleAllowed(array $roles): bool
{
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

// ─────────────────────────────────────────────
// Sanitize Output for HTML
// ─────────────────────────────────────────────
function sanitize(string $val): string
{
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
