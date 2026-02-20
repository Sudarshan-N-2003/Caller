# AdmissionConnect — Telecaller College Admission System

## 🚀 Quick Deploy Guide

### Step 1: Set up Neon PostgreSQL Database
1. Go to https://neon.tech and create a free account
2. Create a new project
3. Open the **SQL Editor**
4. Copy and paste the entire contents of `schema.sql`
5. Click **Run** — this creates all tables and indexes
6. Copy your connection details (you'll need these next)

### Step 2: Create Admin User
**After running schema.sql**, create the default admin:

**Option A — Via Browser (easiest):**
```
https://your-app.onrender.com/setup_admin.php
```

**Option B — Command Line:**
```bash
php setup_admin.php
```

This creates:
- **Email:** `admin@college.com`
- **Password:** `Admin@123`

⚠️ **Change this password immediately after first login!**

### Step 3: Configure Environment Variables

**For Render:**
1. Go to Render Dashboard → Your Service → **Environment**
2. Add these variables:

```
DB_HOST     = ep-your-endpoint.us-east-2.aws.neon.tech
DB_PORT     = 5432
DB_NAME     = neondb
DB_USER     = neondb_owner
DB_PASS     = your_neon_password
APP_URL     = https://your-app.onrender.com
```

**For Local Development:**
1. Rename `.env` to `.env` (if not already)
2. Fill in your Neon credentials
3. Run: `php -S localhost:8080 router.php`

### Step 4: Deploy on Render
1. Push code to GitHub
2. Render → New → Web Service → Connect repo
3. **Environment:** PHP
4. **Start Command:** `php -S 0.0.0.0:$PORT router.php`
5. Add environment variables (see Step 3)
6. Deploy!

---

## 🐳 Docker (Local)

```bash
# 1. Fill .env with Neon credentials
# 2. Start container
docker-compose up --build

# 3. Access at http://localhost:8080
# 4. Run setup: http://localhost:8080/setup_admin.php
```

---

## 👥 User Roles

| Role | Access |
|------|--------|
| **Admin** | Full access — manage users, students, view all data, export |
| **Telecaller** | View assigned students, make calls, submit feedback |
| **Office** | Add new students |

---

## 🔐 Password Flows

**First Login (New Users):**
1. Admin creates user → system generates password like `TCJoh123!`
2. Admin shares password with user
3. User logs in → forced to set new password
4. Future logins use new password

**Forgot Password:**
1. Click "Forgot Password" on login page
2. Enter: Email + Date of Birth + New Password + Confirm
3. DOB must match account (security verification)
4. Password resets instantly

---

## ✨ Key Features

✅ Auto-assign students to least-loaded telecaller
✅ "Call Now" → opens phone dialer → auto-opens feedback form  
✅ Date/time auto-captured in feedback (read-only)
✅ Callback reminders — triggers on scheduled date
✅ Admin dashboard with telecaller performance cards
✅ Export student list to CSV/Excel
✅ Fully responsive (mobile/tablet/desktop)
✅ Docker support with Apache + PHP 8.2

---

## 📂 Project Structure

```
telecaller/
├── index.php              Login page
├── setup_admin.php        Create default admin (run once)
├── router.php             URL routing for php -S
├── schema.sql             Database tables
├── .env                   Local credentials (git-ignored)
├── api/
│   ├── auth.php           Login/logout/passwords
│   ├── users.php          User CRUD
│   ├── students.php       Student CRUD + assignment
│   └── feedback.php       Call feedback + reminders
├── includes/
│   └── config.php         DB connection + helpers
└── pages/
    ├── admin.php          Admin dashboard
    └── telecaller.php     Telecaller dashboard
```

---

## 🛠️ Troubleshooting

**"Invalid credentials" error:**
- Run `setup_admin.php` to create the admin user
- Check that `schema.sql` was run in Neon first

**"Network error" on login:**
- Verify all DB_* environment variables are set correctly
- Test connection: `psql "postgresql://user:pass@host/db?sslmode=require"`

**"Database connection failed":**
- Check `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in Render environment
- Neon requires SSL — this is already handled in `config.php`

---

## 📝 License

MIT — Free to use for any purpose
