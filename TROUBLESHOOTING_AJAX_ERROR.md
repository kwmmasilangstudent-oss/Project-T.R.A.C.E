# Troubleshooting: "Unexpected token '<'" Error

## What This Error Means

The JavaScript fetch is getting **HTML instead of JSON** from the API. This usually happens when:

1. ❌ The API endpoint returns a PHP error page (HTML)
2. ❌ The API endpoint returns 404 (not found)
3. ❌ There's a PHP warning/notice being output
4. ❌ The CSRF token validation is failing
5. ❌ The user session is invalid (redirect happening)

---

## How to Debug

### Quick Test: Use the API Test Endpoint

**For Admin:**
1. Visit: `http://your-domain/admin/api/test.php`
2. You should see JSON output with diagnostic checks
3. Should show: session_active, user_id, csrf_token, functions, database status

**For Secretary:**
1. Visit: `http://your-domain/secretary/api/test.php`
2. Same diagnostic checks as admin

**If you see HTML error:** There's a PHP issue  
**If you see JSON with all checks PASS:** API is configured correctly

---

### Detailed Debug Steps

### Step 1: Open Browser DevTools
- **Windows/Linux:** Press `F12`
- **Mac:** Press `Cmd + Option + I`

### Step 2: Go to Network Tab
1. Click "Network" tab
2. Reload the page
3. Click an appointment action button
4. Look for a request named `appointments-action.php`

### Step 3: Check the Response
1. Click on the `appointments-action.php` request
2. Go to "Response" tab
3. Check what the server is returning

**If you see HTML (starting with `<`):**
- There's an error in the API
- Copy the first 200 characters of the response

**If you see JSON:**
- The response is correct
- The error is in JavaScript parsing

---

## Common Causes & Fixes

### Issue 1: API Endpoint Path Wrong
**Error:** 404 Not Found

**Solution:**
Check if the API endpoint exists:
- Admin: `/admin/api/appointments-action.php`
- Secretary: `/secretary/api/appointments-action.php`

Make sure files are created in correct locations:
```
admin/api/appointments-action.php ✓
secretary/api/appointments-action.php ✓
```

### Issue 2: CSRF Token Missing or Invalid
**Error:** JSON response with `"success": false, "message": "CSRF token validation failed"`

**Solution:**
Verify CSRF token is being sent:
1. In DevTools, go to Network tab
2. Click the API request
3. Go to "Request" tab  
4. Look for `csrf_token` in Form Data

If missing, check that the modal has data-csrf attribute:
```html
<div class="rq-actions-cell" data-csrf="<?php echo csrfToken(); ?>">
```

### Issue 3: PHP Error/Warning
**Error:** JSON response contains HTML error message

**Solution:**
Check server error log:
```bash
# On Linux
tail -f /var/log/php-fpm.log
# or
tail -f /var/log/apache2/error.log

# On Windows (XAMPP)
tail -f C:\xampp\php\logs\php_error.log
```

Common PHP errors:
- `Call to undefined function createNotification()` - Missing function include
- `Call to undefined function logAudit()` - Missing function include
- `CSRF token validation failed` - Token mismatch

### Issue 4: User Not Authenticated
**Error:** Redirected to login page (returns HTML)

**Solution:**
Make sure you're logged in as admin or secretary before clicking buttons.

---

## Quick Test

### Test the API Directly
**Using browser address bar:**

1. Open `/admin/api/appointments-action.php`
2. You should see:
```json
{
  "success": false,
  "message": "Invalid request method",
  "status": null,
  "notificationId": null
}
```

**If you see this:** API is working correctly ✓  
**If you see HTML:** There's a PHP error ✗

---

## Step-by-Step Fix

### 1. Check Files Exist
```bash
# Verify files are in right place
ls -la admin/api/appointments-action.php
ls -la secretary/api/appointments-action.php
ls -la includes/confirmation-modal.php
```

### 2. Check File Permissions
```bash
# Files should be readable
chmod 644 admin/api/appointments-action.php
chmod 644 secretary/api/appointments-action.php
```

### 3. Clear Cache
```bash
# Clear browser cache
# Ctrl+Shift+R (or Cmd+Shift+R on Mac)
```

### 4. Check Error Logs
```bash
# Look for any PHP errors
grep "Appointment action error" /var/log/php-fpm.log
```

### 5. Test with Console
1. Open DevTools (F12)
2. Go to Console tab
3. Try this:
```javascript
// Test CSRF token retrieval
console.log('CSRF Token:', document.querySelector('.rq-actions-cell').dataset.csrf);

// Test appointment data
console.log('Appointment ID:', document.querySelector('.rq-act').dataset.appointmentId);
```

---

## Detailed Request/Response Check

### What Should Happen (Correct Flow)

**Browser Console Output:**
```
POST /admin/api/appointments-action.php

Request Headers:
  Content-Type: multipart/form-data
  X-Requested-With: XMLHttpRequest

Request Body:
  appointment_id: 123
  action: approve
  csrf_token: abc123xyz...

Response Status: 200 OK
Response Headers:
  Content-Type: application/json

Response Body:
  {
    "success": true,
    "status": "approved",
    "message": "Appointment updated successfully.",
    "notificationId": 456
  }
```

### What's Wrong (Error Case)

**Browser Console Output:**
```
POST /admin/api/appointments-action.php

Response Status: 200 OK
Response Headers:
  Content-Type: text/html  ← WRONG! Should be application/json

Response Body:
  <html>
  <head><title>Error</title></head>
  <body>
  <h1>Fatal error:</h1>
  <p>Call to undefined function createNotification() in /admin/api/appointments-action.php on line 47</p>
  </body>
  </html>
```

---

## Enable Debug Mode

### Add this to your API files temporarily:

**admin/api/appointments-action.php** (at the very top after opening PHP):
```php
<?php
ob_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ... rest of code
```

This will show PHP errors in the JSON response for debugging.

---

## Still Having Issues?

### Check These Files Are Identical to Originals

1. `includes/functions.php` - Has `createNotification()` and `csrfToken()`
2. `includes/header.php` - Includes all necessary files
3. `admin/api/appointments-action.php` - Complete API endpoint
4. `secretary/api/appointments-action.php` - Complete API endpoint
5. `admin/appointments.php` - Has JavaScript function
6. `secretary/appointments.php` - Has JavaScript function

### Enable Logging
Add this to the API for better debugging:
```php
error_log('API called with action: ' . $action . ', appointment: ' . $appointmentId);
error_log('CSRF token provided: ' . substr($csrfToken, 0, 10) . '...');
```

---

## Contact Support with Debug Info

If the issue persists, provide:
1. The exact error message from DevTools Console tab
2. The Response tab content (first 200 characters)
3. Output of: `echo phpinfo();` (check PHP version, extensions)
4. Server error log tail (last 10 lines)
5. Confirmation that files are in correct locations

---

**Last Updated:** 2026-08-09
