# SIMS v2 - Post-Fix Testing & Verification Checklist

## ✅ Critical Fixes Applied - Verification Steps

### Phase 1: Database Connection
- [ ] Database credentials updated in `php/db_config.php`
- [ ] Bytebase instance is running and accessible
- [ ] MySQL port 3307 is open and accessible
- [ ] Test connection: Visit `http://localhost/simsv2/php/test_db.php`

### Phase 2: Database Schema
- [ ] Run `SIMS db.sql` import into Bytebase database
  ```bash
  mysql -h hayes-pizza-existence-federal.trycloudflare.com -P 3307 -u nacion.johnrain.carino1@gmail.com -p sims < SIMS\ db.sql
  ```
- [ ] All tables created (users, employees, departments, assets, requests, audit, query)
- [ ] users table has correct columns: user_id, first_name, last_name, middle_name, email, password, role_type

### Phase 3: Registration Flow
- [ ] Navigate to `http://localhost/simsv2/sign_up_page.html`
- [ ] Fill in test data:
  - [ ] First Name: Test
  - [ ] Middle Name: (optional) User
  - [ ] Last Name: Admin
  - [ ] Email: testadmin@example.com
  - [ ] Password: TestPass123
  - [ ] Confirm Password: TestPass123
- [ ] Click "Create Account"
- [ ] Should see success message: "Registration successful! You can now sign in."
- [ ] Should redirect to sign-in page automatically (or after clicking link)
- [ ] Verify user created in database:
  ```sql
  SELECT * FROM users WHERE email = 'testadmin@example.com';
  ```
- [ ] Password is hashed (not plaintext)
- [ ] role_type is 'user' by default

### Phase 4: Login Flow
- [ ] Navigate to `http://localhost/simsv2/sign_in_page.html`
- [ ] Login with registered account (testadmin@example.com / TestPass123)
- [ ] Should redirect to user dashboard (`http://localhost/simsv2/user_pages/user_dashboard.php`)
- [ ] Page loads without errors
- [ ] Session data is preserved

### Phase 5: Profile Display
- [ ] On dashboard, click "Profile" in sidebar
- [ ] Profile panel expands
- [ ] User name displays correctly: "Test User Admin" (or with middle name inserted)
- [ ] User email displays correctly: testadmin@example.com
- [ ] NOT showing placeholder text (FName, LName, email@gmail.com)

### Phase 6: Navigation & Access
- [ ] Can navigate between user pages (Assets, Request Log, Report)
- [ ] All pages display correct user info in profile
- [ ] Cannot access admin pages (try navigating to admin_dashboard.php)
- [ ] Should redirect to sign-in if accessing admin pages

### Phase 7: Logout Functionality
- [ ] Click "Log out" button in profile panel (or logout link)
- [ ] Should redirect to sign-in page
- [ ] Session is cleared
- [ ] Try accessing user_dashboard.php directly
- [ ] Should redirect to sign-in page (session required)

### Phase 8: Admin Account Setup
- [ ] Register new admin account (if applicable)
- [ ] Manually update in database: `UPDATE users SET role_type = 'admin' WHERE email = '...';`
- [ ] Login with admin account
- [ ] Should redirect to admin dashboard (`http://localhost/simsv2/admin_pages/admin_dashboard.php`)
- [ ] Admin dashboard displays correctly
- [ ] Can navigate to admin pages (Employees, Departments, Assets, etc.)
- [ ] Profile displays correct user info

### Phase 9: Access Control
- [ ] Login as admin user
- [ ] Navigate to admin_dashboard.php - should work
- [ ] Logout, login as regular user
- [ ] Try navigating to admin_dashboard.php - should redirect to sign-in
- [ ] Verify role-based access control works

### Phase 10: Cross-Browser Testing
- [ ] Test in Chrome/Edge
- [ ] Test in Firefox
- [ ] Test responsive design on mobile (F12 -> device toolbar)
- [ ] Profile panel opens/closes correctly
- [ ] All links work correctly

---

## 🐛 Common Issues & Troubleshooting

### Issue: "Database connection failed"
**Solution:**
1. Check Bytebase is running
2. Verify credentials in `php/db_config.php`
3. Check MySQL port 3307 is accessible
4. Test with CLI: 
   ```bash
   mysql -h hayes-pizza-existence-federal.trycloudflare.com -P 3307 -u nacion.johnrain.carino1@gmail.com -p sims
   ```

### Issue: "Login fails even with correct credentials"
**Solution:**
1. Check user exists in database
2. Verify password is hashed correctly
3. Check PHP error logs for SQL errors
4. Ensure database schema is imported

### Issue: "Profile shows FName LName instead of actual name"
**Solution:**
1. Check session variables are being set (add debug code)
2. Verify session file permissions
3. Check PHP can read session data
4. Clear browser cookies and try again

### Issue: "Logout doesn't work"
**Solution:**
1. Check `php/logout.php` exists and is readable
2. Verify session.destroy() is called
3. Check browser can access logout.php
4. Clear browser cookies manually if needed

### Issue: "Getting redirected to sign-in when trying to access dashboard"
**Solution:**
1. Check session.start() is called in auth_check.php
2. Verify login sets $_SESSION['logged_in'] = true
3. Check PHP session handler configuration
4. Try clearing browser cookies and logging in again

---

## 📊 Test Results Template

Copy this template and fill in results:

```
Test Date: __________
Tester: __________
Environment: XAMPP on Windows PowerShell

Database Connection: PASS / FAIL
  Notes: _______________________

Registration Flow: PASS / FAIL
  Notes: _______________________

Login Flow: PASS / FAIL
  Notes: _______________________

Profile Display: PASS / FAIL
  Notes: _______________________

Navigation: PASS / FAIL
  Notes: _______________________

Logout: PASS / FAIL
  Notes: _______________________

Admin Access Control: PASS / FAIL
  Notes: _______________________

Overall Status: PASS / FAIL
```

---

## 🔄 Rollback Instructions (If Needed)

If issues occur, revert changes:

1. **Revert db_config.php:**
   ```bash
   git checkout php/db_config.php
   ```

2. **Revert all PHP files:**
   ```bash
   git checkout admin_pages/*.php user_pages/*.php
   ```

3. **Revert HTML changes:**
   ```bash
   git checkout sign_in_page.html
   ```

---

## 📋 Sign-Off

- [ ] All Phase 1-10 tests passed
- [ ] No critical errors in logs
- [ ] Application ready for development
- [ ] Documentation complete

**Approved by:** _______________  
**Date:** _______________  
**Notes:** _______________________

