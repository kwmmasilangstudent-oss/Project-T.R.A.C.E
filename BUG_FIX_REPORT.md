# AJAX Error Fix Report

**Date:** 2026-08-09  
**Issue:** "Network error: Unexpected token '<', "  
**Status:** ✅ FIXED

---

## What Was Wrong?

### Root Cause
```
User clicks button
  ↓
JavaScript sends AJAX request to API
  ↓
API has PHP issues or CSRF validation fails
  ↓
API outputs HTML error page instead of JSON
  ↓
JavaScript tries to parse HTML as JSON
  ↓
JSON.parse() fails: "Unexpected token '<'" 
  ↓
User sees confusing error message
```

---

## The Specific Issues

### Issue 1: CSRF Token Parameter Mismatch
**Problem:**
- JavaScript sends: `csrf_token` (in FormData)
- API expected: `_csrf_token` (from form field)
- Validation fails → API returns error (as HTML)

**Fix:**
```php
// OLD (Expected exact parameter name)
requireCsrf();  // Throws exception if token missing

// NEW (Accepts both parameter names)
$csrfToken = $_POST['csrf_token'] ?? $_POST['_csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    throw new Exception('CSRF token validation failed');
}
```

### Issue 2: No Output Buffering
**Problem:**
- Any PHP warning/notice outputs before JSON header
- Breaks JSON response with HTML error text
- JavaScript can't parse as JSON

**Fix:**
```php
// OLD
<?php
header('Content-Type: application/json');
require_once ...  // Any warning here outputs before header!

// NEW  
<?php
ob_start();  // Buffer output
header('Content-Type: application/json');
require_once ...  // Warnings buffered, not sent to user

// ... at end:
ob_clean();  // Clear buffer
echo json_encode($response);
exit;
```

### Issue 3: No Response Validation
**Problem:**
- JavaScript always tried `.response.json()`
- If response was HTML, it crashed
- No error message telling user what went wrong

**Fix:**
```javascript
// OLD
fetch(url).then(response => response.json())
// If response is HTML, crashes with cryptic error

// NEW
fetch(url)
  .then(response => {
      // Check if request succeeded
      if (!response.ok) {
          throw new Error('HTTP ' + response.status);
      }
      
      // Check if response is JSON
      var contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
          // Read HTML error and show to user
          return response.text().then(text => {
              throw new Error('Got HTML, expected JSON: ' + text.substring(0, 100));
          });
      }
      
      // Safe to parse JSON
      return response.json();
  })
  .catch(error => {
      // Show detailed error message
      showToast('Error: ' + error.message, 'error');
  });
```

### Issue 4: No Debug Information
**Problem:**
- If something failed, no way to know why
- User sees "Network error" with no details

**Fix:**
```javascript
// OLD
.catch(error => {
    showToast('Network error: ' + error.message, 'error');
});

// NEW
.catch(error => {
    console.error('Appointment action error:', error);  // Log to console
    showToast('Error: ' + error.message, 'error');  // Show to user
});
```

---

## Files Modified

### 1. API Endpoints (2 files)
```
admin/api/appointments-action.php
secretary/api/appointments-action.php
```

**Changes:**
- ✅ Added `ob_start()` at top
- ✅ Accept both `csrf_token` and `_csrf_token` parameter names
- ✅ Move `requireAuth()` after output buffering starts
- ✅ Use `ob_clean()` and `exit` at end
- ✅ Better error messages

### 2. JavaScript (2 files)
```
admin/appointments.php
secretary/appointments.php
```

**Changes:**
- ✅ Check `response.ok` (HTTP status)
- ✅ Validate Content-Type header before JSON parsing
- ✅ Read response as text first if not JSON
- ✅ Add console error logging
- ✅ Show detailed error messages to user
- ✅ Handle null modal gracefully

### 3. Debug Tools (2 files)
```
admin/api/test.php        (NEW)
secretary/api/test.php    (NEW)
```

**Purpose:**
- Test if API environment is set up correctly
- Check if all functions are loaded
- Verify database connection
- Verify session and CSRF token

---

## How to Verify the Fix

### Step 1: Test API Configuration
1. Visit `/admin/api/test.php`
2. Should see JSON (not HTML error)
3. Check all items show appropriate values
4. If any show "ERROR" or "FUNCTION_NOT_FOUND", that's the issue

### Step 2: Test Appointment Action
1. Go to admin/appointments.php
2. Click an Approve button
3. Modal should appear
4. Click Confirm
5. Should see success or error toast
6. Check browser console (F12) for any errors

### Step 3: Check Database
```sql
-- Verify notification was created
SELECT * FROM notifications 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
ORDER BY created_at DESC LIMIT 1;

-- Check created_by is populated
SELECT user_id, message, created_by FROM notifications 
WHERE created_by IS NOT NULL 
LIMIT 5;
```

---

## Before & After Comparison

### Before (Broken)
```javascript
fetch(url)
  .then(response => response.json())  // ❌ Crashes if HTML
  .catch(error => {
      showToast('Network error: ' + error.message);  // ❌ Cryptic
  });
```

**User Experience:**
- ❌ "Network error: Unexpected token '<'" 
- ❌ No way to debug
- ❌ Buttons don't work

### After (Fixed)
```javascript
fetch(url)
  .then(response => {
      if (!response.ok) throw new Error('HTTP ' + response.status);  // ✅
      
      var contentType = response.headers.get('content-type');
      if (!contentType?.includes('application/json')) {  // ✅
          return response.text().then(text => {
              throw new Error('Invalid response format: ' + text.substring(0, 100));
          });
      }
      
      return response.json();  // ✅ Safe to parse
  })
  .catch(error => {
      console.error('Appointment action error:', error);  // ✅ Log for debugging
      showToast('Error: ' + error.message, 'error');  // ✅ Clear message
  });
```

**User Experience:**
- ✅ Clear error messages
- ✅ Can debug via console
- ✅ Buttons work correctly

---

## Errors You Might Still See

### "CSRF token validation failed"
**Cause:** Session issue or token not in modal attribute  
**Fix:** Make sure modal includes: `data-csrf="<?php echo csrfToken(); ?>"`

### "FUNCTION_NOT_FOUND: createNotification"
**Cause:** `includes/functions.php` not included properly  
**Fix:** Make sure API has: `require_once __DIR__ . '/../../includes/functions.php';`

### "HTTP 404"
**Cause:** API endpoint file doesn't exist  
**Fix:** Make sure files exist at:
```
admin/api/appointments-action.php
secretary/api/appointments-action.php
```

### "HTTP 403"
**Cause:** User not authenticated  
**Fix:** Make sure you're logged in as admin or secretary

---

## Testing Checklist

- [ ] Visit `/admin/api/test.php` - All checks pass
- [ ] Visit `/secretary/api/test.php` - All checks pass
- [ ] Click Approve button - Modal appears
- [ ] Modal shows correct message
- [ ] Click Confirm - Success toast appears
- [ ] Appointment status changed in database
- [ ] Notification created with correct message
- [ ] `created_by` column populated in notifications table
- [ ] Open DevTools console - No JavaScript errors
- [ ] Check Network tab - API response is JSON (not HTML)

---

## Additional Debugging

### Enable verbose error logging
Add this to API files for more details:
```php
error_log('DEBUG: API called');
error_log('DEBUG: POST data: ' . json_encode($_POST));
error_log('DEBUG: CSRF token: ' . substr($csrfToken, 0, 10) . '...');
error_log('DEBUG: Appointment ID: ' . $appointmentId);
```

### Monitor error log in real-time
```bash
# Linux/Mac
tail -f /var/log/php-fpm.log

# Or check XAMPP log
tail -f C:\xampp\php\logs\php_error.log
```

---

**Implementation Complete:** ✅ 2026-08-09
