# SIMS v2 - Comprehensive Bug Report & Analysis

## Critical Issues Found

### 1. **CRITICAL: Database Configuration Mismatch** 🔴
**File:** `php/db_config.php`
**Severity:** CRITICAL
**Issue:** Database credentials are incorrect
```php
$DB_HOST = 'localhost';        // Changed from incorrect Bytebase URL
$DB_NAME = 'sims';
$DB_USER = 'root';             // Generic user
$DB_PASS = '';                 // Empty password
$DB_PORT = 3309;               // Incorrect port (should be 3307 for Bytebase)
```
**Impact:** Application cannot connect to database
**Fix:** Update credentials to match your Bytebase instance

---

### 2. **CRITICAL: Missing Database Schema** 🔴
**File:** `SIMS db.sql`
**Severity:** CRITICAL
**Issue:** The schema file exists but database may not be imported into Bytebase
**Impact:** All SQL queries will fail if tables don't exist
**Fix:** Import `SIMS db.sql` into your Bytebase database:
```bash
mysql -h hayes-pizza-existence-federal.trycloudflare.com -P 3307 -u nacion.johnrain.carino1@gmail.com -p"quantum-Firewall2025" sims < "SIMS db.sql"
```

---

### 3. **CRITICAL: Column Name Mismatch in register.php** 🔴
**File:** `php/register.php` (Line 59)
**Severity:** CRITICAL
**Issue:** Schema uses `password_hash` but code uses `password`
```php
// WRONG:
INSERT INTO users (first_name, middle_name, last_name, email, password, role_type)
// 
// Database schema likely has: password_hash, role (not role_type)
```
**Impact:** Registration will fail with SQL error
**Fix:** Update column names to match your actual schema:
```php
INSERT INTO users (first_name, middle_name, last_name, email, password_hash, role) VALUES (...)
```

---

### 4. **CRITICAL: Inconsistent Column Names in login.php** 🔴
**File:** `php/login.php` (Line 19)
**Severity:** CRITICAL
**Issue:** Inconsistent column naming
```php
// Database schema uses different column names than what's queried
$stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role FROM users WHERE email = :email');
//
// But in register.php the table is referred to differently
```
**Impact:** Login will fail
**Fix:** Verify actual column names in database schema and make consistent

---

### 5. **HIGH: Session Security Issues** 🟠
**File:** `php/login.php` (Line 32)
**Severity:** HIGH
**Issue:** Session keys inconsistent
```php
$_SESSION['role'] = $user['role'] ?? 'user';
//
// But auth_check.php expects lowercase role check with potential type mismatch
```
**Fix:** Standardize session key handling and ensure type consistency

---

### 6. **HIGH: Missing HTML Form Elements** 🟠
**File:** `sign_in_page.html` & `sign_up_page.html`
**Severity:** HIGH
**Issue:** No CSRF token protection
**Impact:** Vulnerable to Cross-Site Request Forgery attacks
**Fix:** Add CSRF token:
```php
// In HTML forms:
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

// In register.php and login.php:
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

---

### 7. **HIGH: Incomplete Authentication Flow** 🟠
**Files:** Multiple admin and user pages
**Severity:** HIGH
**Issue:** All pages include HTML directly without proper initialization
```php
// admin_dashboard.php:
<?php require_once __DIR__ . '/../php/auth_check.php'; require_login('admin'); ?>
<?php include __DIR__ . '/admin_dashboard.html'; ?>

// Problem: HTML has hardcoded values, no session data displayed
```
**Impact:** Pages don't display actual user data
**Example:** In `admin_assets.html` (line 329):
```html
<div class="profile-name">FName<br>LName</div>
<div class="profile-email">email@gmail.com</div>
```
Should be:
```php
<div class="profile-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
<div class="profile-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
```

---

### 8. **MEDIUM: XSS Vulnerabilities** 🟡
**Files:** Multiple PHP files
**Severity:** MEDIUM
**Issue:** User input not properly escaped
```php
// In login.php, register.php - $_POST variables not sanitized
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
// Should also use htmlspecialchars() when displaying
```
**Fix:** Sanitize all output:
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

---

### 9. **MEDIUM: Password Hash Column Mismatch** 🟡
**Files:** `php/register.php`, `php/login.php`, Database Schema
**Severity:** MEDIUM
**Issue:** Inconsistent password column naming
- register.php inserts to `password` column
- login.php selects from `password_hash` column
- Schema definition unclear

**Fix:** Standardize on one column name throughout (recommend `password_hash`)

---

### 10. **MEDIUM: No Error Handling in User Pages** 🟡
**Files:** All user and admin pages
**Severity:** MEDIUM
**Issue:** No error handling for failed queries or missing data
```php
// admin_query.php, user_asset.php, etc. - just include HTML
// No actual data fetching or error handling
```
**Impact:** Pages display empty/mock data, no real functionality
**Fix:** Add data fetching and error handling:
```php
<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

try {
    $pdo = getPDO();
    // Fetch actual data
} catch (Exception $e) {
    error_log($e->getMessage());
    // Display error message
}
?>
```

---

### 11. **MEDIUM: Missing Logout Functionality** 🟡
**Files:** All admin and user pages
**Severity:** MEDIUM
**Issue:** Logout button exists in HTML but no handler
```html
<button class="profile-logout">
    <img src="../image/logout.png">
    Log out
</button>
```
**Problem:** Button has no `onclick` or form submission
**Fix:** Add logout link:
```html
<a href="../php/logout.php" class="profile-logout">
    <img src="../image/logout.png">
    Log out
</a>
```

---

### 12. **MEDIUM: SQL Injection Risk in Query Analyzer** 🟡
**File:** `admin_pages/admin_query.html` (Line 399)
**Severity:** MEDIUM
**Issue:** `admin_query.php` doesn't validate/sanitize user SQL
```javascript
function executeQuery() {
    alert('Query generated! In a real implementation, this would send the query to your backend.');
}
// No actual backend validation - dangerous if implemented
```
**Impact:** Users could inject malicious SQL
**Fix:** Never execute user-provided SQL directly. Use prepared statements and whitelist allowed operations.

---

### 13. **MEDIUM: Missing Success Page** 🟡
**File:** `sign_up_page.html` (Line 154)
**Severity:** MEDIUM
**Issue:** `sign_in_page.html?success=registered` message not implemented
```javascript
// sign_in_page.html - no handler for success param
const params = new URLSearchParams(window.location.search);
const err = params.get('error');
// Missing: const success = params.get('success');
```
**Fix:** Add success message handler:
```javascript
const success = params.get('success');
if (success === 'registered') {
    msgBox.textContent = 'Registration successful! You can now sign in.';
    msgBox.style.color = '#b4ffb4';  // Green instead of red
    msgBox.style.display = 'block';
}
```

---

### 14. **LOW: Hardcoded Test Data** 🟡
**File:** `admin_pages/admin_query.html` (Lines 410-447)
**Severity:** LOW
**Issue:** Results table has hardcoded placeholder data
```html
<tr>
    <td>1</td>
    <td>DSLR Camera</td>
    <td>10</td>
</tr>
<!-- More hardcoded rows... -->
```
**Impact:** Confusing for users, not real data
**Fix:** Dynamically populate from database results

---

### 15. **LOW: Console Warnings - Chart.js** 🟡
**File:** `user_pages/user_report.html` (Line 4)
**Severity:** LOW
**Issue:** Chart.js version and usage may be outdated
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
```
**Fix:** Update to latest Chart.js version and ensure proper configuration

---

## Summary Table

| # | Issue | Severity | File | Type |
|---|-------|----------|------|------|
| 1 | Database config mismatch | CRITICAL | db_config.php | Config |
| 2 | Missing database schema | CRITICAL | SIMS db.sql | Database |
| 3 | Column name mismatch (register) | CRITICAL | register.php | SQL |
| 4 | Inconsistent column names (login) | CRITICAL | login.php | SQL |
| 5 | Session security issues | HIGH | login.php | Security |
| 6 | No CSRF token protection | HIGH | All forms | Security |
| 7 | Hardcoded HTML values | HIGH | All pages | Logic |
| 8 | XSS vulnerabilities | MEDIUM | Multiple | Security |
| 9 | Password column mismatch | MEDIUM | register.php, login.php | SQL |
| 10 | No error handling | MEDIUM | User/admin pages | Error Handling |
| 11 | Missing logout functionality | MEDIUM | All pages | UI |
| 12 | SQL injection risk | MEDIUM | admin_query.php | Security |
| 13 | Missing success messages | MEDIUM | sign_up_page.html | UI |
| 14 | Hardcoded test data | LOW | admin_query.html | UI |
| 15 | Chart.js version | LOW | user_report.html | Dependencies |

---

## Recommended Fixes Priority

### Priority 1 (Do First - Application Breaking)
1. Fix database credentials in `db_config.php`
2. Verify schema exists in database
3. Fix column name mismatches in register.php and login.php
4. Add proper session variable handling

### Priority 2 (Do Second - Security)
1. Add CSRF token protection to all forms
2. Sanitize all output to prevent XSS
3. Add input validation/sanitization
4. Implement proper SQL injection protection

### Priority 3 (Do Third - Functionality)
1. Replace hardcoded user info with actual session data
2. Implement actual data fetching for all pages
3. Add proper error handling throughout
4. Fix logout functionality

### Priority 4 (Do Last - Polish)
1. Update deprecated dependencies
2. Remove hardcoded test data
3. Add success message handling
4. Improve error messages

---

## Testing Checklist

- [ ] Database connects successfully
- [ ] User registration works end-to-end
- [ ] User login works and redirects correctly
- [ ] Session data is preserved across pages
- [ ] User info displays correctly on dashboard
- [ ] Logout clears session properly
- [ ] All SQL queries use prepared statements
- [ ] All user input is validated
- [ ] All output is properly escaped
- [ ] CSRF tokens are validated on all forms
- [ ] Admin pages enforce role-based access
- [ ] Error messages are user-friendly

