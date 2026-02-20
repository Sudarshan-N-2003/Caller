<?php
// check_system.php — System diagnostic tool
// Visit: https://your-app.onrender.com/check_system.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check — AdmissionConnect</title>
    <style>
        body { font-family: monospace; background: #0a0e1a; color: #e2e8f0; padding: 2rem; max-width: 900px; margin: 0 auto; }
        h1 { color: #3b82f6; border-bottom: 2px solid #1e2d45; padding-bottom: 1rem; }
        .check { background: #111827; border: 1px solid #1e2d45; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .check h2 { margin: 0 0 1rem 0; color: #06b6d4; font-size: 1.1rem; }
        .ok { color: #10b981; }
        .error { color: #ef4444; }
        .warn { color: #f59e0b; }
        pre { background: #0d1525; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.85rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin-top: 1rem; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔍 System Diagnostics</h1>
    
    <?php
    require_once __DIR__ . '/includes/config.php';
    
    // ── CHECK 1: Environment Variables ─────────────────────────
    echo '<div class="check">';
    echo '<h2>1️⃣ Environment Variables</h2>';
    
    $env_ok = true;
    $env_vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    
    foreach ($env_vars as $var) {
        $val = getenv($var) ?: constant($var);
        if (!$val || strpos($val, 'your-') !== false) {
            echo "<div class='error'>❌ $var not set or using placeholder</div>";
            $env_ok = false;
        } else {
            $masked = ($var === 'DB_PASS') ? str_repeat('*', strlen($val)) : $val;
            echo "<div class='ok'>✅ $var = $masked</div>";
        }
    }
    
    echo '<div style="margin-top:1rem">';
    echo '<strong>BASE_URL:</strong> ' . BASE_URL . '<br>';
    echo '<strong>APP_NAME:</strong> ' . APP_NAME;
    echo '</div>';
    echo '</div>';
    
    // ── CHECK 2: Database Connection ────────────────────────────
    echo '<div class="check">';
    echo '<h2>2️⃣ Database Connection</h2>';
    
    try {
        $db = getDB();
        echo "<div class='ok'>✅ Connected to PostgreSQL</div>";
        
        $version = $db->query("SELECT version()")->fetchColumn();
        echo "<pre>$version</pre>";
        
        $db_ok = true;
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database connection failed</div>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        $db_ok = false;
    }
    echo '</div>';
    
    // ── CHECK 3: Tables Exist ────────────────────────────────────
    if ($db_ok) {
        echo '<div class="check">';
        echo '<h2>3️⃣ Database Tables</h2>';
        
        try {
            $tables = $db->query("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                ORDER BY table_name
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            $required = ['users', 'students', 'feedback', 'reminders'];
            $missing = array_diff($required, $tables);
            
            if ($missing) {
                echo "<div class='error'>❌ Missing tables: " . implode(', ', $missing) . "</div>";
                echo "<div class='warn'>⚠️ Run schema.sql in Neon SQL Editor</div>";
            } else {
                echo "<div class='ok'>✅ All required tables exist</div>";
                echo "<pre>" . implode("\n", $tables) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Could not check tables</div>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        echo '</div>';
        
        // ── CHECK 4: Admin User ──────────────────────────────────
        echo '<div class="check">';
        echo '<h2>4️⃣ Admin User Status</h2>';
        
        try {
            $stmt = $db->query("SELECT id, name, email, role FROM users WHERE role='admin' ORDER BY id LIMIT 1");
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo "<div class='ok'>✅ Admin user exists</div>";
                echo "<pre>";
                echo "ID:    " . $admin['id'] . "\n";
                echo "Name:  " . htmlspecialchars($admin['name']) . "\n";
                echo "Email: " . htmlspecialchars($admin['email']) . "\n";
                echo "Role:  " . $admin['role'];
                echo "</pre>";
                
                echo "<div style='margin-top:1rem'>";
                echo "<strong>Default credentials:</strong><br>";
                echo "Email: admin@college.com<br>";
                echo "Password: Admin@123";
                echo "</div>";
                
            } else {
                echo "<div class='error'>❌ No admin user found</div>";
                echo "<div class='warn'>⚠️ You need to run setup_admin.php</div>";
                echo "<a href='setup_admin.php' class='btn'>Run Setup Admin</a>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Could not check users table</div>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        echo '</div>';
        
        // ── CHECK 5: Row Counts ───────────────────────────────────
        echo '<div class="check">';
        echo '<h2>5️⃣ Data Summary</h2>';
        
        try {
            $counts = [
                'users'     => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'students'  => $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
                'feedback'  => $db->query("SELECT COUNT(*) FROM feedback")->fetchColumn(),
                'reminders' => $db->query("SELECT COUNT(*) FROM reminders")->fetchColumn(),
            ];
            
            echo "<pre>";
            foreach ($counts as $table => $count) {
                echo str_pad($table, 12) . ": $count rows\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Could not query counts</div>";
        }
        echo '</div>';
    }
    
    // ── FINAL STATUS ─────────────────────────────────────────────
    echo '<div class="check">';
    echo '<h2>✅ Next Steps</h2>';
    
    if (!$env_ok) {
        echo "<div class='warn'>1. Set environment variables in Render dashboard</div>";
    }
    if (!$db_ok) {
        echo "<div class='warn'>2. Check DB credentials and Neon connection</div>";
    } elseif ($missing ?? false) {
        echo "<div class='warn'>3. Run schema.sql in Neon SQL Editor</div>";
    } elseif (empty($admin)) {
        echo "<div class='warn'>4. Run setup_admin.php to create admin user</div>";
    } else {
        echo "<div class='ok'>✅ System is ready! You can login now.</div>";
        echo "<a href='/' class='btn'>Go to Login</a>";
    }
    echo '</div>';
    ?>
    
</body>
</html>
