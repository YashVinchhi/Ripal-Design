# Admin Panel — CIE-1 Study Guide
> Read this top-to-bottom before your exam. Every section explains **what the file does**, **how the code works**, and **what to say when asked**.

---

## TABLE OF CONTENTS
1. [Project Overview — What is This Website?](#1-project-overview)
2. [Folder Structure of the Admin Panel](#2-folder-structure)
3. [Core Infrastructure Files](#3-core-infrastructure-files)
   - [includes/init.php](#31-includesinit-php)
   - [includes/config.php](#32-includesconfigphp)
   - [includes/db.php](#33-includesdbphp)
   - [includes/auth.php](#34-includesauthphp)
   - [Common/header.php](#35-commonheaderphp)
4. [Admin Pages — File by File](#4-admin-pages)
   - [user_management.php](#41-user_managementphp)
   - [add_user.php](#42-add_userphp)
   - [provision_temp_user.php](#43-provision_temp_userphp)
   - [project_management.php](#44-project_managementphp)
   - [payment_gateway.php](#45-payment_gatewayphp)
   - [leave_management.php](#46-leave_managementphp)
   - [file_viewer.php](#47-file_viewerphp)
5. [Design System — Colors, Fonts, Tailwind](#5-design-system)
6. [Form Validation — How It Works](#6-form-validation)
7. [Authentication & Role Guard — How Login Protection Works](#7-authentication--role-guard)
8. [Database Connection Pattern](#8-database-connection-pattern)
9. [Common Questions Faculty May Ask + Answers](#9-common-questions--answers)
10. [Quick-Reference Code Snippets](#10-quick-reference-code-snippets)

---

## 1. Project Overview

**Website Name:** Ripal Design  
**Type:** Architecture / Interior Design firm website  
**Purpose:** A multi-role web application for an architecture firm to manage projects, workers, clients, and finances.

**Three types of users:**
| Role | What they can do |
|------|-----------------|
| **Admin** | Full control — manage users, projects, payments, leave |
| **Worker** | View assigned projects, submit ratings |
| **Client** | Upload drawings, view project progress, submit revisions |

**Your responsibility (Member 3 — Admin):**
- Design the admin dashboard
- Design all admin pages with proper UI
- Add form validation on all admin forms
- Ensure admin-only pages redirect non-admins away

---

## 2. Folder Structure

```
admin/
  add_user.php          ← Form to create a new user account
  provision_temp_user.php ← Generate temporary credentials for a worker/employee
  user_management.php   ← List, view, edit, delete all users
  project_management.php← View and create projects (project cards grid)
  payment_gateway.php   ← Financial transactions ledger
  leave_management.php  ← Approve/reject employee leave requests
  file_viewer.php       ← View project PDF/blueprint files

includes/
  init.php      ← MASTER bootstrap — loads everything
  config.php    ← BASE_URL, BASE_PATH constants
  db.php        ← Database (PDO) connection
  auth.php      ← Login check and role check functions
  
Common/
  header.php    ← HTML <head> assets + navigation bar (used by admin pages)
  header_alt.php← Alternate nav header (sidebar slide-out)
  footer.php    ← Footer included at the bottom of pages
```

---

## 3. Core Infrastructure Files

### 3.1 `includes/init.php`

**What it does:** This is the FIRST line in almost every admin page. It loads all other core files in the correct order.

```php
require_once __DIR__ . '/../includes/init.php';
```

**Loading order inside init.php:**
1. `config.php` → sets up `BASE_URL`, `BASE_PATH`, `PROJECT_ROOT` constants
2. `db.php` → creates `$pdo` (database connection)
3. `auth.php` → loads helper functions like `require_role()`, `is_logged_in()`
4. `util.php` → utility/helper functions
5. Starts PHP session with `session_start()`

**Why use one bootstrap file?** So you don't have to write 4 separate `require_once` calls on every page — just one line does everything.

---

### 3.2 `includes/config.php`

**What it does:** Detects where the application is installed and defines constants for building URLs.

**Key constants:**
- `BASE_URL` → e.g., `http://localhost/TheFinal_Thefinal2`
- `BASE_PATH` → e.g., `/TheFinal_Thefinal2`
- `PROJECT_ROOT` → Absolute filesystem path

**How it auto-detects the path:**
```php
// It checks the current script's folder name
$parts = explode('/', $scriptPath);
$lastPart = $parts[count($parts) - 1];

// If we're inside admin/, dashboard/, public/, etc., it removes that folder
$appFolders = ['public', 'dashboard', 'admin', 'client', 'worker'];
if (in_array($lastPart, $appFolders)) {
    array_pop($parts); // removes the last folder to get root
}
```

**Why this matters:** Any link we write as `BASE_PATH . '/admin/user_management.php'` will work whether the site is at `localhost/mysite/` or at `localhost/` — the path adjusts automatically.

---

### 3.3 `includes/db.php`

**What it does:** Creates a **PDO** (PHP Data Objects) connection to the MySQL database.

**PDO = the safe, modern way to talk to a database in PHP.**

```php
$dsn = "mysql:host=localhost;port=3306;dbname=ripal_db;charset=utf8mb4";
$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
```

**Key PDO options used:**
| Option | What it means |
|--------|--------------|
| `ERRMODE_EXCEPTION` | Throw exceptions on SQL errors (so we can catch them) |
| `DEFAULT_FETCH_MODE => FETCH_ASSOC` | Results come back as named arrays, not numbered |
| `EMULATE_PREPARES => false` | Use real prepared statements (prevents SQL injection) |

**Fallback / Demo Mode:**  
If the database is unavailable (connection fails), `$pdo` is set to `null`. Pages then use **hardcoded static data** so the UI still works for demo/CIE purposes.

```php
// Example fallback in dashboard.php:
if (isset($pdo) && $pdo instanceof PDO) {
    // Load from database
} else {
    // Use static demo data
    $projects = [['id' => 1, 'name' => 'Shanti Sadan'], ...];
}
```

**Two helper functions:**
```php
db_connected(); // returns true/false
get_db();       // returns $pdo or null
```

---

### 3.4 `includes/auth.php`

**What it does:** Provides all authentication-related functions. This is how we protect admin pages.

**Functions defined:**

#### `require_login()`
Checks if `$_SESSION['user']` exists. If the user is not logged in → **redirects to login page and exits**.

#### `require_role('admin')`
First calls `require_login()`, then checks if the logged-in user's role matches. If not → **redirects to homepage**.

```php
// HOW ADMIN PAGES ARE PROTECTED:
require_once __DIR__ . '/../includes/init.php';
require_role('admin');   // ← This one line protects the entire page
```

#### `current_user()`
Returns the `$_SESSION['user']` array (has username, role, etc.)

#### `is_logged_in()`
Returns `true` or `false`.

#### `has_role('admin')`
Returns `true` if the logged-in user has that specific role.

**How session-based auth works:**
1. User logs in via `public/login.php`
2. On successful login, `$_SESSION['user']` is set with the user's data
3. Every protected page calls `require_role('admin')` which checks that session variable
4. If session is empty or role doesn't match → user is kicked to login or home

---

### 3.5 `Common/header.php`

**What it does:** Loads all CSS/JS libraries for every page and renders the navigation bar.

**Libraries it loads:**
- **Google Fonts** — Inter (body text) + Playfair Display (headings)
- **Bootstrap 5.3.2** — Grid, utilities
- **Bootstrap Icons** — `bi-*` icon classes
- **Lucide Icons** — `data-lucide="icon-name"` SVG icons (modern, clean)
- **Tailwind CSS** (CDN) — Utility-first CSS framework

**Tailwind custom theme configured here:**
```javascript
tailwind.config = {
  theme: {
    extend: {
      colors: {
        "rajkot-rust": "#94180C",    // Primary brand color (dark red)
        "canvas-white": "#F9FAFB",   // Page background
        "foundation-grey": "#2D2D2D",// Dark text/header color
        "approval-green": "#15803D", // Success states
        "pending-amber": "#B45309",  // Warning states
        "slate-accent": "#334155",   // Workers/secondary
      }
    }
  }
}
```

**Navigation:**  
The nav is a **sidebar slide-out** (hamburger menu). Links are grouped into:
- Dashboard (Dashboard Home, Profile, Review Requests)
- Worker Portal (Worker Dashboard, Assigned Projects, Ratings)
- Administration (Project Portfolio, User Controls, Leave Manager, Financial Gateway)

**How to include it in a page:**
```php
<?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
```
Setting `$HEADER_MODE = 'dashboard'` also loads `admin-responsive.css` for admin-specific mobile styles.

---

## 4. Admin Pages

### 4.1 `user_management.php`

**Purpose:** Shows all registered users in a table. Admin can view, edit, or delete any user.

**What the page contains:**

#### Stats Cards (Top Row)
Four metric cards showing:
- **Total Registry** — 124 total users
- **Active Clients** — 42
- **Field Workers** — 68
- **System Staff** — 14

These are currently static/hardcoded values meant to demonstrate the UI layout.

#### Toolbar
- **Search input** — "Filter identities..." (client-side filter, visual only in current version)
- **Role dropdown** — "All Permissions", Administrators, Design Lead, etc.
- **Apply button** — triggers filter

#### User Table
Columns: Identity Profile | Authorization Level | Signal (Online Status) | Last Sync | Actions

Each row has three action buttons:
1. **Eye icon** → `href="../dashboard/profile.php?user=..."` — View user profile
2. **Settings icon** → `href="add_user.php?id=..."` — Edit user
3. **Trash icon** → `onclick="confirmDelete('Name')"` — Delete with confirmation

**Mobile responsive table:**  
On mobile, each table row becomes a card using CSS:
```css
/* In admin-responsive.css */
/* Rows become stacked blocks on small screens */
tr { display: block; }
td::before { content: attr(data-label); } /* Shows column label */
```
This works because each `<td>` has a `data-label="Column Name"` attribute.

**Delete confirmation:**
```javascript
function confirmDelete(userName) {
    if (confirm('Are you sure you want to remove ' + userName + '?')) {
        // In full implementation: AJAX call or form submission
    }
}
```

**Pagination placeholder:**
A "Initialize Full Registry Scroll" button at the bottom (currently just a visual element, pagination not implemented yet — that's CIE-2).

---

### 4.2 `add_user.php`

**Purpose:** A form for the admin to create a new user account. After successful creation, redirects to `user_management.php`.

**PHP Processing (Server-side):**

```php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');  // Only admins can access
```

**POST form handling:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'client';
    
    // Validation
    if (empty($firstName) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'A user with this email already exists.';
        } else {
            // Hash the password BEFORE saving (security best practice)
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the new user
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $passwordHash, $role]);
            
            $success = 'User created successfully!';
            header("Refresh: 2; url=user_management.php"); // Redirect after 2 seconds
        }
    }
}
```

**Validations performed:**
| Validation | How |
|-----------|-----|
| All fields required | `empty()` check |
| Valid email format | `filter_var($email, FILTER_VALIDATE_EMAIL)` |
| Duplicate email check | `SELECT` query before insert |
| Password minimum length | `minlength="8"` on HTML input + backend empty check |

**Security: Why `password_hash()`?**
- Never store plaintext passwords
- `password_hash($password, PASSWORD_DEFAULT)` uses bcrypt automatically
- Stored hash looks like: `$2y$10$...` (cannot be reversed)
- On login, `password_verify($input, $hash)` is used to check

**Form fields:**
- First Name, Last Name (side by side grid)
- Email Address
- Password (min 8 chars)
- Role select: Client / Worker / Employee / Administrator

**Submit button behavior (JS):**
```javascript
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = 'Initializing Account...'; // Shows loading state
    btn.disabled = true; // Prevents double submission
});
```

---

### 4.3 `provision_temp_user.php`

**Purpose:** More advanced user creation — generates a **temporary random password** for workers or employees and shows the credentials on screen so the admin can share them.

**Special Feature — Demo Mode:**
```php
$DEMO_MODE = (isset($_GET['demo']) && $_GET['demo'] === '1');
if (!$DEMO_MODE) {
    require_role('admin'); // Skip auth check if ?demo=1 in URL
}
```
This allows the UI to be shown in demo without being logged in as admin (useful for CIE presentation).

**Temporary Password Generation:**
```php
$tempPassword = substr(bin2hex(random_bytes(4)), 0, 8);
// random_bytes(4) = 4 cryptographically random bytes
// bin2hex converts to hex string = 8 characters
// Example output: "a3f9bc12"
```
Falls back to `'tmp' . rand(1000, 9999)` if `random_bytes` fails.

**Dynamic Sub-role Selection (jQuery + JavaScript):**
When the admin selects a role, a second dropdown appears dynamically:
```javascript
const options = {
    worker:   ['Master Carpenter', 'Field Site Engineer', 'Electrical Specialist', 