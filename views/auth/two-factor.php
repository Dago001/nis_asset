<?php
/**
 * Two-Factor Authentication View
 */
$error = isset($_GET['error']) ? $_GET['error'] : (isset($_SESSION['error']) ? $_SESSION['error'] : '');
$success = isset($_GET['success']) ? $_GET['success'] : (isset($_SESSION['success']) ? $_SESSION['success'] : '');
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication | NIS Asset Management System</title>
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
            animation: slideIn 0.5s ease-out;
            box-sizing: border-box;
            max-height: calc(100vh - 30px);
            overflow-y: auto;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.1rem;
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

        .login-header h3 {
            color: var(--primary-light);
            font-size: 1rem;
            font-weight: 500;
        }

        .login-header p {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .qr-section {
            background: #F7FAF8;
            border: 1px dashed var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .qr-code {
            width: clamp(130px, 45vw, 160px);
            height: clamp(130px, 45vw, 160px);
            margin: 0 auto 0.75rem;
            background: #ffffff;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .qr-code img,
        #qrcodeCanvas canvas {
            max-width: 100%;
            max-height: 100%;
            height: auto;
            display: block;
        }

        #qrcodeCanvas {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .secret-key {
            font-size: 0.75rem;
            color: #555;
            background: #fff;
            padding: 4px 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            display: inline-block;
            font-family: monospace;
            word-break: break-all;
            margin-top: 0.25rem;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: #444;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .otp-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .otp-input-wrapper i {
            position: absolute;
            left: 12px;
            color: #777;
            font-size: 0.9rem;
            z-index: 1;
        }

        .otp-input-wrapper input {
            width: 100%;
            padding: 0.7rem 0.7rem 0.7rem 38px;
            border: 1px solid #D7E3DC;
            border-radius: 6px;
            font-size: 1.1rem;
            letter-spacing: 2px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }

        .otp-input-wrapper input:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 2px rgba(32, 112, 39, 0.1);
            background: white;
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
            margin-top: 0.5rem;
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
            text-align: center;
        }

        .btn-cancel:hover {
            color: #d9534f;
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

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .system-info {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 0.8rem;
        }

        @media (max-width: 480px) {
            .login-page {
                padding: 10px;
            }

            .login-container {
                padding: 1.25rem;
                max-width: 300px;
                max-height: calc(100vh - 20px);
            }

            .login-header h2 {
                font-size: 1.05rem;
            }

            .login-header h3 {
                font-size: 0.88rem;
            }

            .otp-input-wrapper input {
                font-size: 1rem;
                padding: 0.6rem 0.6rem 0.6rem 34px;
            }

            .btn-verify {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }

        /* Very small phones (e.g. older/entry-level Android, iPhone SE-class) */
        @media (max-width: 360px) {
            .login-page {
                padding: 8px;
            }

            .login-container {
                max-width: 100%;
                padding: 1rem;
                border-radius: 10px;
            }

            .login-header {
                margin-bottom: 0.85rem;
            }

            .logo-img {
                max-width: 42px;
            }

            .qr-section {
                padding: 0.8rem 0.6rem !important;
                margin-bottom: 0.85rem;
            }

            .form-group {
                margin-bottom: 0.85rem;
            }

            .qr-code {
                width: clamp(120px, 40vw, 150px);
                height: clamp(120px, 40vw, 150px);
            }
        }

        /* Short viewports (landscape phones): keep the card fully reachable
           without letting the page itself scroll off-center. */
        @media (max-height: 560px) {
            .login-page {
                align-items: flex-start;
                padding-top: 20px;
            }

            .login-container {
                max-height: calc(100vh - 40px);
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo-white.png" alt="NIS Logo" class="logo-img" onerror="this.style.display='none'">
                <h2>Nigeria Immigration Service</h2>
                <h3>Asset Management System</h3>
                <p>Works & Logistics Directorate</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/auth/two-factor" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <?php if ($isFirstTime): ?>
                    <div class="qr-section" style="text-align: center; padding: 1rem 0.85rem;">
                        <h4 style="font-size: 0.92rem; color: var(--primary-color); margin-bottom: 0.35rem; font-weight: 600;">
                            <i class="fas fa-qrcode" style="color: var(--primary-light);"></i> Step 2 of 2: Set Up Google Authenticator
                        </h4>
                        <p style="font-size: 0.78rem; color: #555; margin-bottom: 0.7rem; line-height: 1.4;">
                            Open Google Authenticator on your mobile device and scan the QR code below:
                        </p>

                        <!-- Scannable QR Code Container (sized responsively via the .qr-code class) -->
                        <div class="qr-code">
                            <div id="qrcodeCanvas"></div>
                            <img id="qrCodeFallback" src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?php echo urlencode($qrCodeUrl); ?>" alt="Scan QR Code" style="display: none;">
                        </div>

                        <div style="margin-top: 8px;">
                            <button type="button" id="toggleManualKey" style="background: none; border: none; color: var(--primary-light); font-size: 0.76rem; font-weight: 600; cursor: pointer; text-decoration: underline;">
                                Show manual setup key
                            </button>
                            <div id="manualKeyBox" style="display: none; margin-top: 8px;">
                                <span class="secret-key" id="totpSecret"><?php echo htmlspecialchars(chunk_split($secret, 4, ' ')); ?></span>
                                <button type="button" id="copySecret" style="margin-left: 6px; font-size: 0.75rem; padding: 2px 8px; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #555; margin-bottom: 1.25rem; line-height: 1.4; text-align: center;">
                        <i class="" style="color: var(--primary-light); font-size: 1.1rem; margin-right: 4px;"></i>
                        Enter the 6-digit Google Authenticator code.
                    </p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="code">
                        <i class=""></i> Authenticator Code
                    </label>
                    <div class="otp-input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="text" id="code" name="code" required 
                               pattern="[0-9]*" inputmode="numeric" maxlength="6" 
                               placeholder="000000" autofocus autocomplete="one-time-code">
                    </div>
                </div>

                <button type="submit" class="btn-verify">
                    <i class="fas fa-shield-alt"></i> Verify & Login
                </button>
            </form>

            <a href="<?php echo BASE_URL; ?>/auth/logout" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Cancel and Sign Out
            </a>

            <div class="system-info">
                <p>&copy; <?php echo date('Y'); ?> Nigeria Immigration Service</p>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/qrcode.min.js"></script>
    <script>
        // Generate QR code if element exists
        const canvasContainer = document.getElementById('qrcodeCanvas');
        if (canvasContainer) {
            const qrUri = <?php echo json_encode($qrCodeUrl ?? ''); ?>;
            if (qrUri && typeof QRCode !== 'undefined') {
                try {
                    new QRCode(canvasContainer, {
                        text: qrUri,
                        width: 160,
                        height: 160,
                        colorDark: "#134617",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (err) {
                    const fb = document.getElementById('qrCodeFallback');
                    if (fb) fb.style.display = 'block';
                }
            } else {
                const fb = document.getElementById('qrCodeFallback');
                if (fb) fb.style.display = 'block';
            }
        }

        const toggleManualBtn = document.getElementById('toggleManualKey');
        if (toggleManualBtn) {
            toggleManualBtn.addEventListener('click', function() {
                const box = document.getElementById('manualKeyBox');
                if (box) {
                    const isHidden = box.style.display === 'none';
                    box.style.display = isHidden ? 'block' : 'none';
                    this.textContent = isHidden ? 'Hide manual setup key' : 'Show manual setup key';
                }
            });
        }

        // Copy the TOTP setup key to the clipboard.
        var copyBtn = document.getElementById('copySecret');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var key = (document.getElementById('totpSecret').textContent || '').replace(/\s+/g, '');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(key).then(function () {
                        copyBtn.textContent = 'Copied';
                        setTimeout(function () { copyBtn.textContent = 'Copy key'; }, 1500);
                    });
                }
            });
        }

        // Restrict input to numbers only and auto-submit
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                // Trigger button click to initiate submission safely
                document.querySelector('.btn-verify').click();
            }
        });
        
        // Prevent keyup from doing double submits
        document.getElementById('code').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.btn-verify').click();
            }
        });
        
        // Change submit button status on form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('.btn-verify');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            // Disable button asynchronously to prevent browser aborting form submission
            setTimeout(() => {
                btn.disabled = true;
            }, 10);
        });

        window.addEventListener('load', function() {
            const loginPage = document.querySelector('.login-page');
            const bgImage = new Image();
            bgImage.src = '<?php echo BASE_URL; ?>/assets/images/tech_building.png';
            bgImage.onerror = function() {
                loginPage.style.background = 'linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%)';
            };
        });
    </script>
</body>
</html>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
