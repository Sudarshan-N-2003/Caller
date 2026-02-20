<?php
// =============================================================================
//  includes/config.php
//  AdmissionConnect — Central Configuration
//
//  Handles:
//    - .env file loading (local development)
//    - Environment variable reading (Render / Docker / production)
//    - Neon PostgreSQL connection via PDO
//    - Base URL auto-detection (works on any host)
//    - Shared helper functions used across all API files
//
//  Used by:  api/auth.php, api/users.php, api/students.php, api/feedback.php
//            pages/admin.php, pages/telecaller.php, index.php
// =============================================================================


// ── 1. LOAD .env FILE (local development only) ────────────────────────────────
//
//  On Render/Docker, environment variables are set natively — no .env needed.
//  Locally, place your credentials in the .env file at the project root.
//  Variables already set in the environment (e.g. by Render) are NEVER overwritten.

$_envFile = dirname(__DIR__) . '/.env';

if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip blank lines and comments
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        // Only process lines that contain KEY=VALUE
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        // Strip optional surrounding quotes from value
        $value = trim($value, '"\'');
        // Never overwrite a variable already set by the real environment
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

unset($_envFile, $lines, $line, $key, $value);


// ── 2. DATABASE CONFIGURATION (Neon PostgreSQL) ──────────────────────────────
//
//  All values come from environment variables.
//  Set them in:
//    - Local:      .env file (project root)
//    - Render:     Dashboard → Your Service → Environment
//    - Docker:     docker-compose.yml env_file or environment section
//
//  Neon always requires SSL — sslmode=require is hardcoded in the DSN.

define('DB_HOST', getenv('DB_HOST') ?: 'ep-your-endpoint.us-east-2.aws.neon.tech');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'neondb');
define('DB_USER', getenv('DB_USER') ?: 'neondb_owner');
define('DB_PASS', getenv('DB_PASS') ?: '');


// ── 3. APPLICATION SETTINGS ───────────────────────────────────────────────────

define('APP_NAME', getenv('APP_NAME') ?: 'AdmissionConnect');

// Session name — keeps sessions isolated from other PHP apps on the same server
define('SESSION_NAME', 'ac_session');


// ── 4. BASE URL AUTO-DETECTION ────────────────────────────────────────────────
//
//  This is injected into every page as a JS constant so all fetch() calls use
//  the correct absolute URL regardless of host, port, or subdomain.
//
//  Priority order:
//    1. APP_URL env var (most reliable — set this on Render)
//    2. X-Forwarded-Proto header (set by Render's proxy layer)
//    3. HTTPS server var (set by Apache/Nginx)
//    4. Falls back to http://hostname

if (getenv('APP_URL')) {
    // Explicitly configured — most reliable, always use this on Render
    define('BASE_URL', rtrim(getenv('APP_URL'), '/'));
} else {
    // Auto-detect from the current request
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        // Render and most reverse proxies set this header
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host);
}


// ── 5. DATABASE CONNECTION ────────────────────────────────────────────────────
//
//  Returns a singleton PDO instance.
//  Called by all API files when a DB query is needed.
//  Uses prepared statements throughout — no raw SQL interpolation.

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Neon requires SSL — sslmode=require is mandatory
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        DB_HOST,
        DB_PORT,
        DB_NAME
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


// ── 6. SHARED HELPER FUNCTIONS ────────────────────────────────────────────────

/**
 * Send a JSON response and stop execution.
 * Used by all API endpoints.
 *
 * @param array $data  Data to encode as JSON
 * @param int   $code  HTTP status code (200, 400, 401, 403, 500...)
 */
function jsonResponse(array $data, int $code = 200): void
{
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
