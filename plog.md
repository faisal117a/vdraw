# Path correction log (plog)

| File | Old Value | New Value | Status |
| :--- | :--- | :--- | :--- |
| `frontend/js/tracking.js` | `/vdraw-anti-auto/api/track.php` | Dynamic: Passed as arg `apiPath` | Fixed |
| `index.php` (Home) | `initTracking('Home')` | `initTracking('Home', 'api/track.php')` | Fixed |
| `frontend/PyViz/index.php` | `initTracking('PyViz')` | `initTracking('PyViz', '../../api/track.php')` | Fixed |
| `frontend/Linear/index.php` | `initTracking('Linear')` | `initTracking('Linear', '../../api/track.php')` | Fixed |
| `frontend/Graph/index.php` | `initTracking('Graph')` | `initTracking('Graph', '../../api/track.php')` | Fixed |
| `frontend/Stats/index.php` | `initTracking('Stats')` | `initTracking('Stats', '../../api/track.php')` | Fixed |
| `frontend/DViz/index.php` | `initTracking('DViz')` | `initTracking('DViz', '../../api/track.php')` | Fixed |
| `auth/Gatekeeper.php` | `/vdraw-anti-auto/user/dashboard/...` | `/user/dashboard/...` | Fixed |
| `frontend/PyViz/forgot_password.php` | `(strpos(... '/vdraw-anti-auto')` | `dirname($_SERVER['PHP_SELF'])` | Fixed |
| `frontend/ads/debug_ads.php` | `(strpos(... '/vdraw-anti-auto')` | `dirname($_SERVER['PHP_SELF'])` | Fixed |
| `auth/MailService.php` | `vdraw.cc` | `$_SERVER['HTTP_HOST']` | Fixed |
