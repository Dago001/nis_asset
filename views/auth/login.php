<?php
/**
 * Login Page — standalone screen (no app-shell layout).
 */
$error = '';
$success = $_GET['success'] ?? '';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
} elseif (isset($_GET['timeout'])) {
    $error = 'You were logged out after 10 minutes of inactivity. Please log in again.';
} elseif (!empty($_GET['error'])) {
    $raw = $_GET['error'];
    if (strpos(strtolower($raw), 'permission') !== false || strpos(strtolower($raw), 'authoris') !== false || strpos(strtolower($raw), 'authoriz') !== false) {
        $error = 'Please log in with an authorized account to access this system.';
    } else {
        $error = $raw;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NIS Asset Management System</title>
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
        }
[data-theme="dark"] {
    --primary-color: #299631;
    --primary-light: #37bf43;
    --secondary-color: #37bf43;
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
            max-width: 380px;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideIn 0.5s ease-out;
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
            
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-img {
            max-width: 60px;
            height: auto;
            margin-bottom: 0.5rem;
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

        .form-group {
            margin-bottom: 1.25rem;
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

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon i {
            position: absolute;
            left: 12px;
            color: #777;
            font-size: 0.9rem;
            z-index: 1;
        }

        .input-with-icon input {
            width: 100%;
            padding: 0.7rem 0.7rem 0.7rem 38px;
            border: 1px solid #D7E3DC;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }

        .input-with-icon input:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 2px rgba(32, 112, 39, 0.1);
            background: white;
        }

        .toggle-password-eye {
            left: auto !important;
            right: 12px !important;
            cursor: pointer !important;
            color: #777 !important;
            z-index: 10 !important;
            transition: color 0.2s ease;
        }

        .toggle-password-eye:hover {
            color: var(--primary-color) !important;
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(32, 112, 39, 0.3);
        }

        .alert {
            padding: 0.6rem 0.8rem;
            margin-bottom: 1.25rem;
            border-radius: 6px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .system-info {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 0.8rem;
        }

        .system-info i {
            color: var(--primary-light);
            margin-right: 4px;
        }

        .system-info a {
            color: var(--primary-light);
            text-decoration: none;
        }

        .system-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                max-width: 320px;
            }

            .login-header h2 {
                font-size: 1.1rem;
            }

            .login-header h3 {
                font-size: 0.9rem;
            }

            .btn-login {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo-white.png" alt="NIS Logo" class="logo-img" onerror="this.style.display='none'">
                <h2><?php echo htmlspecialchars(class_exists('Config') ? Config::get('company_name', 'Nigeria Immigration Service') : 'Nigeria Immigration Service'); ?></h2>
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

            <form action="<?php echo BASE_URL; ?>/auth/login" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-id-card"></i> Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="username" name="username" required 
                               minlength="4" maxlength="5" inputmode="numeric" pattern="\d{4,5}"
                               title="Username must be a 4 or 5 digit Service Number without letters or special characters"
                               placeholder="Service Number"
                               oninput="this.value = this.value.replace(/\D/g, '').slice(0, 5)"
                               value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>" autocomplete="username">
                    </div>
                    <small class="form-hint" style="color: #666; font-size: 0.78rem; margin-top: 4px; display: block;"></small>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Enter password"
                               autocomplete="current-password" style="padding-right: 38px;">
                        <i class="fas fa-eye toggle-password-eye" id="togglePassword" title="Toggle Password Visibility"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginSubmitBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="system-info">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(class_exists('Config') ? Config::get('company_name', 'Nigeria Immigration Service') : 'Nigeria Immigration Service'); ?></p>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        const loginForm = document.querySelector('form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const usernameInput = document.getElementById('username');
                const username = usernameInput ? usernameInput.value.trim() : '';
                const password = document.getElementById('password').value;

                if (!username || !password) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }

                if (!/^\d{4,5}$/.test(username)) {
                    e.preventDefault();
                    alert('Username must be a 4 or 5 digit Service Number without special characters or letters.');
                    if (usernameInput) {
                        usernameInput.focus();
                        usernameInput.select();
                    }
                    return false;
                }

                const btn = document.getElementById('loginSubmitBtn');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
                }
            });
        }

        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
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
