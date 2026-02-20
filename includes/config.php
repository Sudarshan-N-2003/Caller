<?php

// Show errors temporarily for debugging (remove later in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─────────────────────────────────────────────
// Load .env (for local development only)
// ─────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;

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
// Database Configuration (NO FAKE DEFAULTS)
// ─────────────────────────────────────────────
$required = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'];

foreach ($required as $var) {
    if (!getenv($var)) {
        die("Missing required environment variable: $var");
    }
}

define('DB_HOST', getenv('DB_HOST') ?: 'ep-your-endpoint.us-east-2.aws.neon.tech');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'neondb');
define('DB_USER', getenv('DB_USER') ?: 'neondb_owner');
define('DB_PASS', getenv('DB_PASS') ?: '');


// ─────────────────────────────────────────────
// Base URL
// ─────────────────────────────────────────────
function getBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

define('BASE_URL', getenv('APP_URL') ?: getBaseUrl());

// ─────────────────────────────────────────────
// Database Connection
// ─────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_HOST, DB_PORT, DB_NAME
        );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Return arrays by column name
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Use native prepared statements
            PDO::ATTR_PERSISTENT         => false,                    // No persistent connections (safer on Render)
        ]);
    } catch (PDOException $e) {
        // Return a clean JSON error — never expose raw exception to browser
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error'  => 'Database connection failed.',
            'detail' => 'Check DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables.',
        ]);
        exit;
    }

    return $pdo;
}

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Require a valid logged-in session.
 * Sends 401 and stops if no session exists.
 * Returns the full $_SESSION array on success.
 *
 * NOTE: session_start() must be called at the TOP of each API file
 *       before this function is used.
 */
function authRequired(): array
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized. Please log in.'], 401);
    }
    return $_SESSION;
}

/**
 * Require admin role.
 * Calls authRequired() first, then checks role.
 * Sends 403 and stops if user is not an admin.
 */
function adminRequired(): array
{
    $session = authRequired();
    if ($session['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden. Admin access required.'], 403);
    }
    return $session;
}

/**
 * Sanitize a string for safe HTML output.
 * Use on any user-supplied string before echoing it into HTML.
 */
function sanitize(string $val): string
{
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

/**
 * Return true if the current user's role is in the allowed list.
 * Usage: roleAllowed(['admin', 'office'])
 */
function roleAllowed(array $roles): bool
{
    return in_array($_SESSION['role'] ?? '', $roles, true);
}
