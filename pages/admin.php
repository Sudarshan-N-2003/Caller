<?php
// pages/admin.php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/'); exit;
}
$adminName = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — AdmissionConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>

    .tab-nav {
  display: flex;
  border-bottom: 1px solid var(--border);
}

.tab {
  padding: .75rem 1.2rem;
  cursor: pointer;
  font-weight: 600;
  color: var(--muted);
  border-bottom: 2px solid transparent;
  transition: .2s;
}

.tab:hover {
  color: var(--text);
}

.tab.active {
  color: var(--accent);
  border-bottom: 2px solid var(--accent);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0e1a;--sidebar:#0d1220;--card:#111827;--border:#1e2d45;
  --accent:#3b82f6;--accent2:#06b6d4;--success:#10b981;--danger:#ef4444;
  --warn:#f59e0b;--purple:#8b5cf6;
  --text:#e2e8f0;--muted:#64748b;--radius:12px;
  --sidebar-w:240px;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:15px}
a{text-decoration:none;color:inherit}

/* Layout */
.app{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{
  width:var(--sidebar-w);background:var(--sidebar);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;
  transition:transform .3s;
}
.sidebar-brand{
  padding:1.5rem 1.25rem 1rem;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:.75rem;
}
.sidebar-brand .icon{
  width:38px;height:38px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;
}
.sidebar-brand span{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem}

.nav{padding:1rem .75rem;flex:1;overflow-y:auto}
.nav-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.6rem .5rem .3rem;font-weight:600}
.nav-item{
  display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:8px;
  cursor:pointer;color:var(--muted);transition:.15s;margin-bottom:2px;font-size:.9rem;
}
.nav-item:hover{background:rgba(255,255,255,.05);color:var(--text)}
.nav-item.active{background:rgba(59,130,246,.15);color:var(--accent)}
.nav-item .ico{font-size:1.1rem;width:20px;text-align:center}

/* Top Bar */
.topbar{
  position:fixed;top:0;left:var(--sidebar-w);right:0;height:56px;
  background:rgba(10,14,26,.9);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;padding:0 1.5rem;z-index:90;
}
.topbar h2{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700}
.topbar-right{display:flex;align-items:center;gap:1rem}

/* Profile popup */
.profile-btn{
  display:flex;align-items:center;gap:.6rem;cursor:pointer;padding:.4rem .75rem;
  border-radius:8px;background:var(--card);border:1px solid var(--border);transition:.15s;
  position:relative;
}
.profile-btn:hover{border-color:var(--accent)}
.avatar{
  width:32px;height:32px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;
}
.profile-name{font-size:.875rem;font-weight:500}

.profile-popup{
  position:absolute;top:calc(100% + 8px);right:0;background:var(--card);
  border:1px solid var(--border);border-radius:10px;min-width:160px;
  box-shadow:0 10px 30px rgba(0,0,0,.4);display:none;z-index:200;
}
.profile-popup.show{display:block}
.popup-item{
  padding:.65rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.6rem;
  font-size:.875rem;transition:.15s;
}
.popup-item:hover{background:rgba(255,255,255,.05)}
.popup-item.logout{color:var(--danger)}
.popup-divider{border-top:1px solid var(--border);margin:.25rem 0}

/* Main content */
.main{margin-left:var(--sidebar-w);padding-top:56px;flex:1;min-height:100vh}
.page{display:none;padding:1.5rem;animation:fadeIn .2s ease}
.page.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Stats cards */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{
  background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  padding:1.25rem;cursor:pointer;transition:.2s;
}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.stat-label{font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;font-weight:600}
.stat-val{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800}
.stat-card.blue .stat-val{color:var(--accent)}
.stat-card.green .stat-val{color:var(--success)}
.stat-card.red .stat-val{color:var(--danger)}
.stat-card.yellow .stat-val{color:var(--warn)}
.stat-card.purple .stat-val{color:var(--purple)}

/* Section header */
.section-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem}
.section-hdr h3{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700}

/* Buttons */
.btn{
  padding:.55rem 1.1rem;border:none;border-radius:8px;cursor:pointer;
  font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:500;transition:.15s;
  display:inline-flex;align-items:center;gap:.4rem;
}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-success{background:var(--success);color:#fff}
.btn-success:hover{background:#059669}
.btn-danger{background:var(--danger);color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.btn-sm{padding:.35rem .7rem;font-size:.8rem}
.btn:disabled{opacity:.5;cursor:not-allowed}

/* Table */
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.table-filters{padding:.75rem 1rem;border-bottom:1px solid var(--border);display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.search-input{
  padding:.5rem .85rem;background:#0d1525;border:1px solid var(--border);border-radius:7px;
  color:var(--text);font-size:.875rem;outline:none;min-width:200px;transition:.2s;
}
.search-input:focus{border-color:var(--accent)}
select.filter-sel{
  padding:.5rem .7rem;background:#0d1525;border:1px solid var(--border);border-radius:7px;
  color:var(--text);font-size:.875rem;outline:none;
}

table{width:100%;border-collapse:collapse}
thead{background:#0d1525}
th{padding:.75rem 1rem;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
td{padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid rgba(30,45,69,.5)}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}

/* Badges */
.badge{
  display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:20px;
  font-size:.75rem;font-weight:600;letter-spacing:.02em;
}
.badge-blue{background:rgba(59,130,246,.15);color:#93c5fd}
.badge-green{background:rgba(16,185,129,.15);color:#6ee7b7}
.badge-red{background:rgba(239,68,68,.15);color:#fca5a5}
.badge-yellow{background:rgba(245,158,11,.15);color:#fcd34d}
.badge-gray{background:rgba(100,116,139,.15);color:#94a3b8}
.badge-purple{background:rgba(139,92,246,.15);color:#c4b5fd}

/* Forms */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.form-group{display:flex;flex-direction:column;gap:.35rem;margin-bottom:.1rem}
.form-label{font-size:.82rem;color:var(--muted);font-weight:500}
.form-input{
  padding:.65rem .9rem;background:#0d1525;border:1px solid var(--border);border-radius:8px;
  color:var(--text);font-size:.9rem;outline:none;transition:.2s;
}
.form-input:focus{border-color:var(--accent)}
.form-full{grid-column:1/-1}
select.form-input option{background:#0d1525}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:500;display:none;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  width:90%;max-width:500px;max-height:90vh;overflow-y:auto;animation:fadeIn .2s}
.modal-hdr{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-hdr h3{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700}
.modal-close{background:none;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer;line-height:1;padding:.2rem .4rem;border-radius:4px}
.modal-close:hover{color:var(--text)}
.modal-body{padding:1.5rem}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.75rem}

/* Password reveal */
.pass-reveal{
  background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);
  border-radius:10px;padding:1rem;margin-top:1rem;
}
.pass-reveal p{font-size:.82rem;color:var(--muted);margin-bottom:.4rem}
.pass-code{
  font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:var(--success);
  letter-spacing:.1em;word-break:break-all;
}

/* Alert */
.alert{padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;display:none}
.alert.show{display:block}
.alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}

/* Telecaller cards */
.tc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.tc-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;transition:.2s}
.tc-card:hover{border-color:rgba(59,130,246,.4);transform:translateY(-2px)}
.tc-name{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:.25rem}
.tc-email{font-size:.82rem;color:var(--muted);margin-bottom:.8rem}
.tc-stats{display:flex;gap:.75rem;flex-wrap:wrap}
.tc-stat{text-align:center}
.tc-stat-val{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700}
.tc-stat-lbl{font-size:.72rem;color:var(--muted)}
.progress-bar{height:4px;background:rgba(255,255,255,.08);border-radius:2px;margin-top:.75rem;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));border-radius:2px;transition:.4s}

/* Hamburger */
.hamburger{display:none;background:none;border:none;color:var(--text);font-size:1.4rem;cursor:pointer;padding:.3rem}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}

/* Spinner */
.spin{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:rot .6s linear infinite;vertical-align:middle;margin-right:.3rem}
@keyframes rot{to{transform:rotate(360deg)}}

/* Empty state */
.empty-state{text-align:center;padding:3rem 1rem;color:var(--muted)}
.empty-state .ico{font-size:2.5rem;margin-bottom:.75rem}

/* Responsive */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .topbar{left:0}
  .hamburger{display:block}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .form-grid{grid-template-columns:1fr}
  .form-full{grid-column:1}
  .modal{max-height:95vh}
  .topbar-right .profile-name{display:none}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .tc-grid{grid-template-columns:1fr}
  table{font-size:.78rem}
  th,td{padding:.5rem .6rem}
}
</style>
</head>
<body>

<div class="app">
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="icon">📞</div>
    <span>AdmissionConnect</span>
  </div>
  <nav class="nav">
    <div class="nav-label">Main</div>
    <div class="nav-item active" onclick="showPage('dashboard')" data-page="dashboard"><span class="ico">📊</span> Dashboard</div>
    <div class="nav-label">Students</div>
    <div class="nav-item" onclick="showPage('add-student')" data-page="add-student"><span class="ico">➕</span> Add Student</div>
    <div class="nav-item" onclick="showPage('students')" data-page="students"><span class="ico">👥</span> All Students</div>
    <div class="nav-label">Team</div>
    <div class="nav-item" onclick="showPage('add-user')" data-page="add-user"><span class="ico">👤</span> Add User</div>
    <div class="nav-item" onclick="showPage('view-users')" data-page="view-users"><span class="ico">🗂️</span> View Users</div>
    <div class="nav-label">Reports</div>
    <div class="nav-item" onclick="exportExcel()"><span class="ico">⬇️</span> Export Excel</div>
  </nav>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Top Bar -->
<div class="topbar">
  <div style="display:flex;align-items:center;gap:1rem">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <h2 id="page-title">Dashboard</h2>
  </div>
  <div class="topbar-right">
    <div class="profile-btn" onclick="toggleProfilePopup()">
      <div class="avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
      <span class="profile-name"><?= htmlspecialchars($adminName) ?></span>
      <span style="font-size:.7rem;color:var(--muted)">▼</span>
      <div class="profile-popup" id="profile-popup">
        <div class="popup-item"><span>👤</span> <?= htmlspecialchars($adminName) ?></div>
        <div class="popup-item"><span style="font-size:.7rem;color:var(--muted)">Admin</span></div>
        <div class="popup-divider"></div>
        <div class="popup-item logout" onclick="doLogout()"><span>🚪</span> Logout</div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<main class="main">

<!-- DASHBOARD PAGE -->
<div id="page-dashboard" class="page active">
  <div class="stats-grid" id="stats-grid">
    <div class="stat-card blue"><div class="stat-label">Total Students</div><div class="stat-val" id="s-total">—</div></div>
    <div class="stat-card green"><div class="stat-label">Accepted</div><div class="stat-val" id="s-accepted">—</div></div>
    <div class="stat-card red"><div class="stat-label">Rejected</div><div class="stat-val" id="s-rejected">—</div></div>
    <div class="stat-card yellow"><div class="stat-label">Pending</div><div class="stat-val" id="s-pending">—</div></div>
    <div class="stat-card purple"><div class="stat-label">Callback</div><div class="stat-val" id="s-callback">—</div></div>
    <div class="stat-card" style="cursor:default"><div class="stat-label">Unassigned</div><div class="stat-val" id="s-unassigned" style="color:var(--warn)">—</div></div>
  </div>

  <div class="section-hdr">
    <h3>Telecaller Performance</h3>
    <button class="btn btn-outline btn-sm" onclick="loadDashboard()">🔄 Refresh</button>
  </div>
  <div class="tc-grid" id="tc-grid">
    <div style="color:var(--muted);padding:1rem">Loading...</div>
  </div>
</div>

<!-- ADD STUDENT PAGE -->
<div id="page-add-student" class="page">
  <div style="max-width:900px">
    
    <!-- Tab navigation -->
    <div class="tab-nav" style="position:relative;top:0;background:var(--card);border:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;margin-bottom:0">
      <div class="tab active" onclick="switchStudentTab('single',this)" data-tab="single">➕ Single Student</div>
      <div class="tab" onclick="switchStudentTab('bulk',this)" data-tab="bulk">📄 Bulk Upload (CSV/Excel)</div>
    </div>

    <!-- SINGLE STUDENT TAB -->
    <div id="student-tab-single" class="table-wrap" style="border-radius:0 0 var(--radius) var(--radius);margin-top:0">
      <div style="padding:1.5rem">
        <div class="alert alert-err" id="add-student-err"></div>
        <div class="alert alert-ok" id="add-student-ok"></div>
        <div class="form-grid">
          <div class="form-group form-full">
            <label class="form-label">Full Name *</label>
            <input class="form-input" id="s-name" placeholder="Student full name">
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number *</label>
            <input class="form-input" id="s-mobile" placeholder="10-digit mobile">
          </div>
          <div class="form-group">
            <label class="form-label">College Type</label>
            <select class="form-input" id="s-ctype">
              <option value="PU">PU College</option>
              <option value="Diploma">Diploma</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Present College Name</label>
            <input class="form-input" id="s-college" placeholder="College name">
          </div>
          <div class="form-group form-full">
            <label class="form-label">Address</label>
            <input class="form-input" id="s-address" placeholder="Student address">
          </div>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.75rem">
          <button class="btn btn-primary" id="add-student-btn" onclick="addStudent()">
            ➕ Add Student
          </button>
          <button class="btn btn-outline" onclick="clearStudentForm()">Clear</button>
        </div>
      </div>
    </div>

    <!-- BULK UPLOAD TAB -->
    <div id="student-tab-bulk" class="table-wrap" style="border-radius:0 0 var(--radius) var(--radius);margin-top:0;display:none">
      <div style="padding:1.5rem">
        <div class="alert alert-err" id="bulk-err"></div>
        <div class="alert alert-ok" id="bulk-ok"></div>
        
        <!-- Instructions -->
        <div style="background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.2);border-radius:8px;padding:1rem;margin-bottom:1.5rem">
          <div style="font-weight:600;margin-bottom:.5rem">📋 File Format Requirements:</div>
          <div style="font-size:.85rem;color:var(--muted);line-height:1.6">
            • <strong>Columns (in order):</strong> Name, Mobile, College Type, Present College, Address<br>
            • <strong>College Type:</strong> Must be exactly "PU", "Diploma", or "Other"<br>
            • <strong>Supported format:</strong> .csv (Excel: save as CSV first)<br>
            • <strong>First row:</strong> Can be headers (will be auto-skipped if detected)
          </div>
        </div>

        <!-- Download template -->
        <div style="margin-bottom:1.5rem">
          <button class="btn btn-outline btn-sm" onclick="downloadTemplate()">
            ⬇️ Download CSV Template
          </button>
        </div>

        <!-- File upload -->
        <div class="form-group">
          <label class="form-label">Upload CSV File</label>
          <input type="file" id="bulk-file" accept=".csv" 
                 style="padding:.5rem;background:var(--card);border:1px solid var(--border);border-radius:8px;cursor:pointer"
                 onchange="previewBulkFile()">
        </div>

        <!-- Preview -->
        <div id="bulk-preview" style="display:none;margin-top:1.5rem">
          <div style="font-weight:600;margin-bottom:.75rem">📊 Preview (first 5 rows):</div>
          <div style="overflow-x:auto;background:#0d1525;border-radius:8px;padding:1rem">
            <table style="font-size:.8rem">
              <thead>
                <tr style="color:var(--accent)">
                  <th style="padding:.4rem .75rem">#</th>
                  <th style="padding:.4rem .75rem">Name</th>
                  <th style="padding:.4rem .75rem">Mobile</th>
                  <th style="padding:.4rem .75rem">Type</th>
                  <th style="padding:.4rem .75rem">College</th>
                  <th style="padding:.4rem .75rem">Address</th>
                </tr>
              </thead>
              <tbody id="bulk-preview-body"></tbody>
            </table>
          </div>
          <div style="margin-top:1rem;color:var(--muted);font-size:.85rem" id="bulk-stats"></div>
        </div>

        <!-- Upload button -->
        <div style="margin-top:1.5rem">
          <button class="btn btn-success" id="bulk-upload-btn" onclick="uploadBulk()" disabled>
            📤 Upload Students
          </button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ALL STUDENTS PAGE -->
<div id="page-students" class="page">
  <div class="table-wrap">
    <div class="table-filters">
      <input class="search-input" id="student-search" placeholder="🔍 Search name or mobile..." oninput="loadStudents()">
      <select class="filter-sel" id="student-status-filter" onchange="loadStudents()">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
        <option value="callback">Callback</option>
      </select>
      <select class="filter-sel" id="student-tc-filter" onchange="loadStudents()">
        <option value="">All Telecallers</option>
      </select>
      <button class="btn btn-outline btn-sm" onclick="loadStudents()">🔄</button>
    </div>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Mobile</th><th>College</th><th>Type</th>
          <th>Assigned To</th><th>Status</th><th>Last Feedback</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="students-tbody">
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">Loading...</td></tr>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ADD USER PAGE -->
<div id="page-add-user" class="page">
  <div style="max-width:600px">
    <div class="table-wrap">
      <div class="modal-hdr" style="border-radius:var(--radius) var(--radius) 0 0">
        <h3>👤 Add New User</h3>
      </div>
      <div style="padding:1.5rem">
        <div class="alert alert-err" id="add-user-err"></div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input class="form-input" id="u-name" placeholder="User full name">
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input class="form-input" type="email" id="u-email" placeholder="email@domain.com">
          </div>
          <div class="form-group">
            <label class="form-label">Phone *</label>
            <input class="form-input" id="u-phone" placeholder="Mobile number">
          </div>
          <div class="form-group">
            <label class="form-label">Role *</label>
            <select class="form-input" id="u-role">
              <option value="telecaller">Telecaller</option>
              <option value="office">Office</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Gender *</label>
            <select class="form-input" id="u-gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date of Birth *</label>
            <input class="form-input" type="date" id="u-dob">
          </div>
        </div>
        <button class="btn btn-primary" id="add-user-btn" onclick="addUser()" style="margin-top:1rem">
          ➕ Create User
        </button>

        <!-- Password reveal -->
        <div class="pass-reveal" id="pass-reveal" style="display:none">
          <p>📋 Share this system-generated password with the user:</p>
          <div class="pass-code" id="gen-pass"></div>
          <p style="font-size:.75rem;color:var(--muted);margin-top:.5rem">
            User will be prompted to set a new password on first login.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- VIEW USERS PAGE -->
<div id="page-view-users" class="page">
  <div class="table-wrap">
    <div class="table-filters">
      <input class="search-input" id="user-search" placeholder="🔍 Search users..." oninput="loadUsers()">
      <select class="filter-sel" id="user-role-filter" onchange="loadUsers()">
        <option value="">All Roles</option>
        <option value="admin">Admin</option>
        <option value="telecaller">Telecaller</option>
        <option value="office">Office</option>
      </select>
    </div>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>DOB</th><th>Actions</th></tr>
      </thead>
      <tbody id="users-tbody">
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Loading...</td></tr>
      </tbody>
    </table>
    </div>
  </div>
</div>

</main>
</div>

<!-- Student Detail Modal -->
<div class="modal-bg" id="student-modal">
  <div class="modal" style="max-width:640px">
    <div class="modal-hdr">
      <h3 id="sd-title">Student Details</h3>
      <button class="modal-close" onclick="closeModal('student-modal')">✕</button>
    </div>
    <div class="modal-body" id="sd-body"></div>
  </div>
</div>

<!-- Reassign Modal -->
<div class="modal-bg" id="reassign-modal">
  <div class="modal" style="max-width:380px">
    <div class="modal-hdr">
      <h3>Reassign Student</h3>
      <button class="modal-close" onclick="closeModal('reassign-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ra-student-id">
      <div class="form-group" style="margin-bottom:1rem">
        <label class="form-label">Assign To Telecaller</label>
        <select class="form-input" id="ra-tc-select"></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('reassign-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="doReassign()">Reassign</button>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo rtrim(BASE_URL, "/"); ?>';

/* ───────────── HELPER FETCH (WITH SESSION) ───────────── */
async function apiFetch(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    ...options
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || 'Request failed');
  }

  return res.json();
}

/* ───────────── NAVIGATION ───────────── */
const pageTitles = {
  'dashboard':'Dashboard',
  'add-student':'Add Student',
  'students':'All Students',
  'add-user':'Add User',
  'view-users':'View Users'
};

function showPage(name) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('page-' + name).classList.add('active');
  const navEl = document.querySelector(`[data-page="${name}"]`);
  if (navEl) navEl.classList.add('active');
  document.getElementById('page-title').textContent = pageTitles[name] || name;

  if (name === 'dashboard') loadDashboard();
  if (name === 'students') loadStudents();
  if (name === 'view-users') loadUsers();

  closeSidebar();
}

/* ───────────── SIDEBAR ───────────── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('show');
}

/* ───────────── PROFILE ───────────── */
function toggleProfilePopup() {
  document.getElementById('profile-popup').classList.toggle('show');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.profile-btn')) {
    document.getElementById('profile-popup').classList.remove('show');
  }
});

async function doLogout(){
  await apiFetch(BASE + '/api/auth.php?action=logout', {
    method:'POST'
  });
  window.location.href = BASE + '/';
}

/* ───────────── DASHBOARD ───────────── */
async function loadDashboard() {
  const grid = document.getElementById('tc-grid');
  grid.innerHTML = '<div style="color:var(--muted);padding:2rem;text-align:center">⏳ Loading...</div>';

  try {
    const summary = await apiFetch(BASE + '/api/students.php?action=summary');
    const tcStats = await apiFetch(BASE + '/api/users.php?action=stats');

    document.getElementById('s-total').textContent = summary.total || 0;
    document.getElementById('s-accepted').textContent = summary.accepted || 0;
    document.getElementById('s-rejected').textContent = summary.rejected || 0;
    document.getElementById('s-pending').textContent = summary.pending || 0;
    document.getElementById('s-callback').textContent = summary.callback || 0;
    document.getElementById('s-unassigned').textContent = summary.unassigned || 0;

    if (!tcStats.length) {
      grid.innerHTML = '<div class="empty-state"><div class="ico">👥</div><p>No telecallers yet.</p></div>';
      return;
    }

    grid.innerHTML = tcStats.map(tc=>{
      const total = parseInt(tc.total_assigned)||0;
      const accepted = parseInt(tc.accepted)||0;
      const pct = total ? Math.round((accepted/total)*100) : 0;

      return `
      <div class="tc-card">
        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
          <div>
            <div class="tc-name">${esc(tc.name)}</div>
            <div class="tc-email">${esc(tc.email)}</div>
          </div>
        </div>
        <div class="tc-stats">
          <div class="tc-stat">
            <div class="tc-stat-val" style="color:var(--accent)">${total}</div>
            <div class="tc-stat-lbl">Assigned</div>
          </div>
          <div class="tc-stat">
            <div class="tc-stat-val" style="color:var(--success)">${accepted}</div>
            <div class="tc-stat-lbl">Accepted</div>
          </div>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:${pct}%"></div>
        </div>
        <div style="font-size:.72rem;color:var(--muted);margin-top:.3rem">${pct}% acceptance rate</div>
      </div>`;
    }).join('');

  } catch (e) {
    console.error(e);
    grid.innerHTML = '<div style="color:var(--danger);padding:2rem;text-align:center">❌ Failed to load dashboard</div>';
  }
}

/* ───────────── STUDENTS ───────────── */
async function loadStudents(){
  const tbody = document.getElementById('students-tbody');
  tbody.innerHTML='<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">Loading...</td></tr>';

  try{
    const data = await apiFetch(BASE + '/api/students.php?action=list');

    if(!data.length){
      tbody.innerHTML='<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">No students found</td></tr>';
      return;
    }

    tbody.innerHTML=data.map((s,i)=>`
      <tr>
        <td>${i+1}</td>
        <td><strong>${esc(s.name)}</strong></td>
        <td>${esc(s.mobile)}</td>
        <td>${esc(s.present_college||'—')}</td>
        <td>${s.college_type||'—'}</td>
        <td>${s.assigned_name||'Unassigned'}</td>
        <td>${s.status||'pending'}</td>
        <td>
          <button class="btn btn-outline btn-sm">👁</button>
        </td>
      </tr>
    `).join('');

  }catch(e){
    console.error(e);
    tbody.innerHTML='<tr><td colspan="9" style="text-align:center;color:var(--danger);padding:2rem">Failed to load</td></tr>';
  }
}

/* ───────────── USERS ───────────── */
async function loadUsers(){
  const tbody=document.getElementById('users-tbody');
  tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Loading...</td></tr>';

  try{
    const data = await apiFetch(BASE+'/api/users.php?action=list');

    if(!data.length){
      tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">No users</td></tr>';
      return;
    }

    tbody.innerHTML=data.map((u,i)=>`
      <tr>
        <td>${i+1}</td>
        <td>${esc(u.name)}</td>
        <td>${esc(u.email)}</td>
        <td>${esc(u.phone)}</td>
        <td>${u.role}</td>
        <td>${u.dob||'—'}</td>
        <td></td>
      </tr>
    `).join('');
  }catch(e){
    console.error(e);
    tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:var(--danger);padding:2rem">Failed to load</td></tr>';
  }
}

/* ───────────── ESCAPE HELPER ───────────── */
function esc(s){
  return String(s||'').replace(/[&<>"']/g,m=>({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
  }[m]));
}

/* ───────────── INIT ───────────── */
loadDashboard();

    /* ───────────── STUDENT TAB SWITCH ───────────── */
function switchStudentTab(tab, el) {
  // Remove active class from all tab buttons
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  
  // Hide all tab contents
  document.getElementById('student-tab-single').style.display = 'none';
  document.getElementById('student-tab-bulk').style.display = 'none';

  // Activate selected tab button
  if (el) el.classList.add('active');

  // Show selected content
  if (tab === 'single') {
    document.getElementById('student-tab-single').style.display = 'block';
  } else if (tab === 'bulk') {
    document.getElementById('student-tab-bulk').style.display = 'block';
  }
}
</script>
</body>
</html>
