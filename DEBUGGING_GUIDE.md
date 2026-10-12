# Debugging Guide: Browser Developer Tools

This guide helps you debug issues with the "Start Trip" and "Cancel Booking" functionality in your car rental application.

---

## 1. Open Developer Tools

| Browser | Shortcut |
|---------|----------|
| Chrome/Edge | `F12` or `Ctrl+Shift+I` (Windows/Linux), `Cmd+Option+I` (Mac) |
| Firefox | `F12` or `Ctrl+Shift+I` (Windows/Linux), `Cmd+Option+I` (Mac) |

---

## 2. Console Tab - JavaScript Errors

**What to check:**
- Red error messages when clicking buttons
- `BookingActions is not defined` → Script not loaded (check `<script src="...">` in Network tab)
- `CSRF token mismatch` → Token expired or missing
- `Failed to fetch` → Network/CORS issue

**Quick test:**
```javascript
// Run in console to verify module loaded
console.log(typeof BookingActions); // Should be "object"
console.log(BookingActions.init);   // Should be function
```

---

## 3. Network Tab - API Requests

**Steps:**
1. Open **Network** tab
2. Filter by **Fetch/XHR**
3. Click "Start Trip" or "Cancel Booking"
4. Look for request to `api/booking-actions.php`

**Inspect the request:**
- **Headers** → Request Headers → `Content-Type: application/json`
- **Payload** → Should show: `{action: "start_trip", booking_id: 12, csrf_token: "..."}`
- **Response** → Should be JSON: `{success: true, message: "..."}` or `{success: false, message: "..."}`

**Common issues:**

| Status | Cause | Fix |
|--------|-------|-----|
| 404 | Wrong `BASE_URL` path | Check `BASE_URL` in page source matches your install path |
| 403/401 | Session expired / Not logged in | Re-login; check `isLoggedIn()` in PHP |
| 500 | PHP error | Check PHP error log (`/opt/lampp/logs/php_error_log`) |
| 200 but `success: false` | Business logic error | Read `message` in response (e.g., "Booking must be confirmed") |

---

## 4. Common PHP/Database Issues

### Check PHP Error Log
```bash
tail -f /opt/lampp/logs/php_error_log
```
Or view in browser by enabling `display_errors` in `config/database.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Verify Database Connection
```php
// Test in a temporary PHP file
require_once 'config/database.php';
echo $pdo ? "Connected" : "Failed";
var_dump($pdo->query("SELECT 1")->fetch());
```

### Check Booking Exists & Permissions
```sql
-- Run in phpMyAdmin or MySQL CLI
SELECT id, booking_reference, status, client_id, driver_id 
FROM bookings WHERE id = 12;

-- Verify driver assignment
SELECT d.id, d.full_name, u.phone, u.email 
FROM drivers d 
JOIN users u ON d.user_id = u.id 
WHERE d.id = (SELECT driver_id FROM bookings WHERE id = 12);
```

---

## 5. Session & CSRF Debugging

**Check session data in browser console:**
```javascript
// Check if logged in
fetch('<?php echo BASE_URL; ?>api/booking-actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'test', csrf_token: '<?php echo csrf_token(); ?>'})
}).then(r => r.json()).then(console.log);
```

**Verify CSRF token in page source:**
- View Page Source → Search `csrf_token`
- Should match `$_SESSION['csrf_token']`

---

## 6. Quick Fixes Checklist

| Issue | Fix |
|-------|-----|
| Buttons do nothing | Ensure `booking-actions.js` loads before inline script |
| `BookingActions` undefined | Check `<script src=".../assets/js/booking-actions.js"></script>` in HTML |
| "Unauthorized" | User not logged in or wrong role; check `$_SESSION['role']` |
| "Invalid status transition" | Only allowed: Pending→Confirmed/Cancelled, Confirmed→Active/Cancelled, Active→Completed |
| Page reloads instead of AJAX | Check `onclick` handlers call `BookingActions.xxx()` not `location.reload()` |
| SweetAlert2 not showing | Verify `sweetalert2.all.min.js` loads before `booking-actions.js` |

---

## 7. Test API Directly (cURL)

```bash
# Test cancel booking
curl -X POST http://localhost/hayven_carhire/api/booking-actions.php \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -b "SMARTDRIVE_SESSION=your_session_cookie" \
  -d '{"action":"cancel_booking","booking_id":12,"csrf_token":"your_token"}'

# Test start trip
curl -X POST http://localhost/hayven_carhire/api/booking-actions.php \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -b "SMARTDRIVE_SESSION=your_session_cookie" \
  -d '{"action":"start_trip","booking_id":12,"csrf_token":"your_token"}'
```

---

## 8. Key Files Modified

| File | Purpose |
|------|---------|
| `assets/js/booking-actions.js` | Unified JS module with Fetch API, SweetAlert2, dynamic UI updates |
| `client/booking-details.php` | Client view with status badge, action buttons, JS initialization |
| `driver/trip-details.php` | Driver view with Start/End Trip buttons, notes |
| `api/booking-actions.php` | Backend handler with CSRF, validation, status transitions, PDO |
| `assets/css/style.css` | Added spinner animation for loading states |
| `includes/scripts.php` | Updated to load `$extraScripts` array |

---

## 9. Status Flow Reference

```
Client Actions:          Driver Actions:
─────────────           ──────────────
Pending → Confirmed     Confirmed → Active
Pending → Cancelled     Active → Completed
Confirmed → Cancelled
Confirmed → Active      (driver can also do these)
Active → Completed
Completed → (penalty payment)
```

Only **admin** can force any transition. Client/Driver restricted by ownership.

---

## 10. Still Stuck?

1. Check **Console** for JS errors
2. Check **Network** → `booking-actions.php` response
3. Check **PHP error log** for server errors
4. Verify **database records** match expected state
5. Ensure **session** is active (`$_SESSION['user_id']` exists)