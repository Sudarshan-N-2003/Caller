<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/includes/config.php';

if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . ($role === 'admin'
        ? '/pages/admin.php'
        : '/pages/telecaller.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AdmissionConnect — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* === YOUR ORIGINAL DESIGN (UNCHANGED) === */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0e1a;--card:#111827;--border:#1e2d45;
  --accent:#3b82f6;--accent2:#06b6d4;--success:#10b981;--danger:#ef4444;
  --text:#e2e8f0;--muted:#64748b;--radius:14px;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif}
body{display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
body::before{
  content:'';
  position:fixed;
  inset:0;
  background:radial-gradient(ellipse 80% 60% at 20% 30%,rgba(59,130,246,.12) 0%,transparent 60%),
             radial-gradient(ellipse 60% 80% at 80% 70%,rgba(6,182,212,.08) 0%,transparent 60%);
  pointer-events: none;
}
.login-wrap{width:100%;max-width:420px;padding:1rem}
.brand{text-align:center;margin-bottom:2rem}
.brand-icon{
  width:64px;height:64px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:18px;display:flex;align-items:center;justify-content:center;
  font-size:1.8rem;margin:0 auto .8rem auto;
}
.brand h1{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:700}
.brand p{color:var(--muted);font-size:.9rem;margin-top:.3rem}
.card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius);padding:2rem;
}
label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:.4rem}
input{
  width:100%;padding:.75rem 1rem;background:#0d1525;
  border:1px solid var(--border);border-radius:8px;
  color:var(--text);margin-bottom:1.1rem;
}
.btn{
  width:100%;padding:.85rem;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  border:none;border-radius:8px;color:#fff;font-weight:700;
  cursor:pointer;
}
.alert{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;display:none}
.alert.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3)}
.alert.show{display:block}
.spinner{
  display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<div class="login-wrap">
  <div class="brand">
    <div class="brand-icon">📞</div>
    <h1>AdmissionConnect</h1>
    <p>College Admission Telecalling System</p>
  </div>

  <div class="card">
    <div class="alert error" id="login-err"></div>

    <label>Email</label>
    <input type="email" id="email" placeholder="your@email.com">

    <label>Password</label>
    <input type="password" id="password" placeholder="Enter password">

    <button class="btn" id="login-btn" onclick="login()">Sign In</button>
  </div>
</div>

<script>
async function login() {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const errBox = document.getElementById('login-err');
  const btn = document.getElementById('login-btn');

  errBox.classList.remove('show');

  if (!email || !password) {
    errBox.textContent = "Email and password required";
    errBox.classList.add('show');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in...';

  try {
    const res = await fetch('/api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ email, password })
    });

    const data = await res.json();

    if (data.success) {
      window.location.href = data.user.role === 'admin'
        ? '/pages/admin.php'
        : '/pages/telecaller.php';
    } else {
      errBox.textContent = data.error || "Login failed";
      errBox.classList.add('show');
    }
  } catch (e) {
    errBox.textContent = "Server error. Please try again.";
    errBox.classList.add('show');
  }

  btn.disabled = false;
  btn.innerHTML = "Sign In";
}
</script>

</body>
</html>
