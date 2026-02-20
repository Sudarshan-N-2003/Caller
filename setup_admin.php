<?php
// setup_admin.php — Run this ONCE after deploying to create the default admin user
// Usage: php setup_admin.php  OR  visit in browser: https://your-app.com/setup_admin.php

require_once __DIR__ . '/includes/config.php';

// Default admin credentials
$email    = 'admin@college.com';
$password = 'Admin@123';  // User must change this on first login
$name     = 'Super Admin';
$phone    = '9999999999';
$gender   = 'Other';
$dob      = '1990-01-01';

try {
    $db = getDB();
    
    // Check if admin already exists
    $check = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $check->execute([$email]);
    $existing = $check->fetch();
    
    if ($existing) {
        echo "✅ Admin user already exists: {$existing['email']} (ID: {$existing['id']})\n";
        echo "Default credentials:\n";
        echo "  Email: admin@college.com\n";
        echo "  Password: Admin@123\n\n";
        echo "⚠️  Change the password after first login!\n";
        exit;
    }
    
    // Generate bcrypt hash
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $db->prepare("
        INSERT INTO users (name, email, phone, gender, dob, role, password_hash, system_password, is_first_login)
        VALUES (?, ?, ?, ?, ?, 'admin', ?, ?, FALSE)
        RETURNING id
    ");
    
    $stmt->execute([
        $name,
        $email,
        $phone,
        $gender,
        $dob,
        $hash,
        $password
    ]);
    
    $row = $stmt->fetch();
    $userId = $row['id'];
    
    echo "✅ Admin user created successfully!\n\n";
    echo "═══════════════════════════════════════\n";
    echo "  Login credentials:\n";
    echo "═══════════════════════════════════════\n";
    echo "  Email:    admin@college.com\n";
    echo "  Password: Admin@123\n";
    echo "  User ID:  {$userId}\n";
    echo "═══════════════════════════════════════\n\n";
    echo "⚠️  IMPORTANT: Change this password after first login!\n\n";
    echo "Next steps:\n";
    echo "  1. Go to: " . BASE_URL . "\n";
    echo "  2. Login with the credentials above\n";
    echo "  3. Change the password immediately\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
    echo "Make sure:\n";
    echo "  1. You've run schema.sql in Neon SQL Editor first\n";
    echo "  2. Your DB_HOST, DB_NAME, DB_USER, DB_PASS are correct\n";
    echo "  3. Neon database is accessible from this server\n";
    exit(1);
}
