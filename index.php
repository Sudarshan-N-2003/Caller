<?php
ini_set('display_errors',1);
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
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0e1a;--card:#111827;--border:#1e2d45;
  --accent:#3b82f6;--accent2:#06b6d4;
  --text:#e2e8f0;--muted:#64748b;--radius:14px;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif}
body{display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}

/* Background */
body::before{
  content:'';
  position:fixed;
  inset:0;
  background:radial-gradient(ellipse 80% 60% at 20% 30%,rgba(59,130,246,.12),transparent 60%),
             radial-gradient(ellipse 60% 80% at 80% 70%,rgba(6,182,212,.08),transparent 60%);
  pointer-events:none; /* IMPORTANT */
}

.login-wrap{width:100%;max-width:420px;padding:1rem}
.brand{text-align:center;margin-bottom:2rem}
.brand-icon{
  width:64px;height:64px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.8rem;margin:0 auto .8rem auto;
}
.brand h1{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:700;
  background:linear-gradient(90deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.brand p{color:var(--muted);font-size:.9rem;margin-top:.3rem}

.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:2rem;
}

.tabs{display:flex;margin-bottom:1.5rem}
.tabs button{
  flex:1;padding:.7rem;background:none;border:none;color:var(--muted);
  border-bottom:2px solid transparent;cursor:pointer
}
.tabs button.active{color:var(--accent);border-color:var(--accent)}

.panel{display:none}
.panel.active{display:block}

label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:.4rem}
input{
  width:100%;padding:.75rem 1rem;background:#0d1525;
  border:1px solid var(--border);
  border-radius:8px;color:var(--text);
  margin-bottom:1.1rem;
}

.btn{
  width:100%;padding:.85rem;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  border:none;border-radius:8px;color:#fff;
  font-weight:700;cursor:pointer
}

.alert{display:none;padding:.75rem;border-radius:8px;margin-bottom:1rem}
.alert.show{display:block}
.alert.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3)}
.alert.success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3)}

.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  display:none;align-items:center;justify-content:center;
}
.modal-overlay.show{display:flex}
.modal{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:2rem;width:90%;max-width:380px;
}
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
    <div class="tabs">
      <button class="active" onclick="switchTab('login',this)">Sign In</button>
      <button onclick="switchTab('forgot',this)">Forgot Password</button>
    </div>

    <!-- LOGIN -->
    <div id="panel-login" class="panel active">
      <div class="alert error" id="login-err"></div>
      <label>Email</label>
      <input type="email" id="login-email">
      <label>Password</label>
      <input type="password" id="login-pass">
      <button class="btn" onclick="doLogin()">Sign In</button>
    </div>

    <!-- FORGOT -->
    <div id="panel-forgot" class="panel">
      <div class="alert error" id="forgot-err"></div>
      <div class="alert success" id="forgot-ok"></div>
      <label>Email</label>
      <input type="email" id="f-email">
      <label>Date of Birth</label>
      <input type="date" id="f-dob">
      <label>New Password</label>
      <input type="password" id="f-pass">
      <label>Confirm Password</label>
      <input type="password" id="f-confirm">
      <button class="btn" onclick="doForgot()">Reset Password</button>
    </div>
  </div>
</div>

<!-- Set Password Modal -->
<div class="modal-overlay" id="set-pass-modal">
  <div class="modal">
    <div class="alert error" id="sp-err"></div>
    <label>New Password</label>
    <input type="password" id="sp-pass">
    <label>Confirm Password</label>
    <input type="password" id="sp-confirm">
    <button class="btn" onclick="doSetPassword()">Set Password</button>
  </div>
</div>

<script>
let tempUserId=null;

function switchTab(tab,btn){
  document.querySelectorAll('.tabs button').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-'+tab).classList.add('active');
}

async function api(endpoint,data){
  const res=await fetch(endpoint,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    credentials:'same-origin',
    body:JSON.stringify(data)
  });
  return res.json();
}

async function doLogin(){
  const email=document.getElementById('login-email').value.trim();
  const pass=document.getElementById('login-pass').value;
  const box=document.getElementById('login-err');
  box.classList.remove('show');

  const data=await api('/api/auth.php?action=login',{email,password:pass});

  if(data.require_set_password){
    tempUserId=data.user_id;
    document.getElementById('set-pass-modal').classList.add('show');
  }else if(data.success){
    window.location.href=data.user.role==='admin'
      ?'/pages/admin.php'
      :'/pages/telecaller.php';
  }else{
    box.textContent=data.error||'Login failed';
    box.classList.add('show');
  }
}

async function doSetPassword(){
  const pass=document.getElementById('sp-pass').value;
  const confirm=document.getElementById('sp-confirm').value;
  const box=document.getElementById('sp-err');
  box.classList.remove('show');

  const data=await api('/api/auth.php?action=set_password',{
    user_id:tempUserId,password:pass,confirm_password:confirm
  });

  if(data.success){
    window.location.href=data.role==='admin'
      ?'/pages/admin.php'
      :'/pages/telecaller.php';
  }else{
    box.textContent=data.error||'Error';
    box.classList.add('show');
  }
}

async function doForgot(){
  const data=await api('/api/auth.php?action=forgot_password',{
    email:document.getElementById('f-email').value,
    dob:document.getElementById('f-dob').value,
    password:document.getElementById('f-pass').value,
    confirm_password:document.getElementById('f-confirm').value
  });

  if(data.success){
    document.getElementById('forgot-ok').textContent="Password reset!";
    document.getElementById('forgot-ok').classList.add('show');
  }else{
    document.getElementById('forgot-err').textContent=data.error||'Error';
    document.getElementById('forgot-err').classList.add('show');
  }
}
</script>

</body>
</html>
