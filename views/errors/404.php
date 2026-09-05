<?php
/**
 * 404 Error Page — Standalone screen styled matching Login Page
 */
http_response_code(404);
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/nis_ams');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found | NIS Asset Management System</title>
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

        .error-page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: url('<?php echo BASE_URL; ?>/assets/images/tech_building.png') center/cover no-repeat fixed;
            position: relative;
        }

        .error-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
        }

        .error-container {
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
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

        .logo-img {
            max-width: 60px;
            height: auto;
            margin-bottom: 0.5rem;
        }

        .error-header h2 {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .error-badge {
            display: inline-block;
            font-size: 3.5rem;
            font-weight: 800;
            color: #d32f2f;
            line-height: 1;
            margin: 1rem 0 0.5rem;
        }

        .error-title {
            color: var(--primary-color);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .error-message {
            color: #555;
            font-size: 0.92rem;
            line-height: 1.5;
            margin-bottom: 1.75rem;
        }

        .error-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none !important;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(19, 70, 23, 0.3);
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: rgba(19, 70, 23, 0.08);
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-container">
            <div class="error-header">
                <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo.png" alt="NIS Logo" class="logo-img">
                <h2>NIS Asset Management</h2>
            </div>
            
            <div class="error-badge">404</div>
            <h3 class="error-title">Page Not Found</h3>
            <p class="error-message">
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>

            <div class="error-actions">
                <a href="<?php echo BASE_URL; ?>/auth/login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-outline">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html>
