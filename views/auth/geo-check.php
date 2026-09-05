<?php
/**
 * Location Check View — asks the browser for the device's GPS position and
 * auto-submits it to /auth/geo-verify. Reached only mid-login, for an
 * account with geofencing enabled (see AuthController::login()/geoCheck()).
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifying Location | NIS Asset Management System</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/nis-logo.png" type="image/png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fontawesome.min.css?v=6.0.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #134617;
            --primary-light: #207027;
            --secondary-color: #207027;
            --border-color: #D7E3DC;
        }
        [data-theme="dark"] {
            --primary-color: #299631;
            --primary-light: #37bf43;
            --secondary-color: #37bf43;
            --border-color: #2f3832;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: url('<?php echo BASE_URL; ?>/assets/images/tech_building.png') center/cover no-repeat fixed;
            position: relative;
        }

        .login-page::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
        }

        .login-container {
            width: 100%;
            max-width: 340px;
            padding: 1.75rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }

        .logo-img {
            max-width: 50px;
            height: auto;
            margin-bottom: 0.4rem;
        }

        .login-header h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            margin-bottom: 1.25rem;
        }

        .geo-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #F7FAF8;
            border: 1px dashed var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.6rem;
            color: var(--primary-light);
        }

        .geo-icon.spinning i {
            animation: spin 1.2s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .geo-status {
            font-size: 0.88rem;
            color: #555;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .alert {
            padding: 0.6rem 0.8rem;
            margin-bottom: 1.25rem;
            border-radius: 6px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-verify {
            width: 100%;
            padding: 0.7rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(32, 112, 39, 0.3);
        }

        .btn-cancel {
            display: block;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .btn-cancel:hover {
            color: #d9534f;
        }

        .system-info {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 0.8rem;
        }

        noscript .alert-error {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo-white.png" alt="NIS Logo" class="logo-img" onerror="this.style.display='none'">
                <h2>Nigeria Immigration Service</h2>
                <p>This account requires location verification to sign in.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="geo-icon spinning" id="geoIcon">
                <i class="fas fa-location-crosshairs"></i>
            </div>
            <p class="geo-status" id="geoStatus">Requesting your device's location…</p>

            <button type="button" class="btn-verify" id="retryBtn" style="display:none;">
                <i class="fas fa-location-crosshairs"></i> Try Again
            </button>

            <form action="<?php echo BASE_URL; ?>/auth/geo-verify" method="POST" id="geoForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="lat" id="geoLat">
                <input type="hidden" name="lng" id="geoLng">
            </form>

            <noscript>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    JavaScript is required to verify your location for this account.
                </div>
            </noscript>

            <a href="<?php echo BASE_URL; ?>/auth/logout" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Cancel and Sign Out
            </a>

            <div class="system-info">
                <p>&copy; <?php echo date('Y'); ?> Nigeria Immigration Service</p>
            </div>
        </div>
    </div>

    <script>
        const icon = document.getElementById('geoIcon');
        const status = document.getElementById('geoStatus');
        const retryBtn = document.getElementById('retryBtn');
        const form = document.getElementById('geoForm');

        function requestLocation() {
            icon.classList.add('spinning');
            status.textContent = "Requesting your device's location…";
            retryBtn.style.display = 'none';

            if (!('geolocation' in navigator)) {
                fail('Your browser does not support location services. Contact your administrator.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    document.getElementById('geoLat').value = pos.coords.latitude;
                    document.getElementById('geoLng').value = pos.coords.longitude;
                    icon.classList.remove('spinning');
                    status.textContent = 'Location confirmed — signing in…';
                    form.submit();
                },
                function (err) {
                    let msg = 'Could not determine your location.';
                    if (err.code === err.PERMISSION_DENIED) {
                        msg = 'Location access was denied. Please allow location access for this site and try again.';
                    } else if (err.code === err.TIMEOUT) {
                        msg = 'Location request timed out. Please try again.';
                    }
                    fail(msg);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function fail(message) {
            icon.classList.remove('spinning');
            status.textContent = message;
            retryBtn.style.display = 'flex';
        }

        retryBtn.addEventListener('click', requestLocation);

        // Auto-start only on a fresh arrival at this page. A failed attempt
        // (denied permission, timeout, or outside the allowed radius)
        // redirects back to this same URL with an error — auto-retrying
        // there too turned into a rapid geo-check <-> geo-verify loop that
        // hammered the login rate-limiter within seconds. Past the first
        // load, the user has to consciously press "Try Again".
        const hasError = <?php echo json_encode(!empty($error)); ?>;

        window.addEventListener('load', function () {
            if (hasError) {
                // The specific reason is already shown in the alert box
                // above (server-rendered from the session's stored error) —
                // just leave this ready for a manual retry instead of
                // immediately trying (and likely failing) again.
                icon.classList.remove('spinning');
                status.textContent = 'Press below to try again.';
                retryBtn.style.display = 'flex';
            } else {
                requestLocation();
            }

            const loginPage = document.querySelector('.login-page');
            const bgImage = new Image();
            bgImage.src = '<?php echo BASE_URL; ?>/assets/images/tech_building.png';
            bgImage.onerror = function () {
                loginPage.style.background = 'linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%)';
            };
        });
    </script>
</body>
</html>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
