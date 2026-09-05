<?php
/**
 * Create (or reset) the first Super Admin Officer account.
 *
 *   php scripts/create_admin.php
 *
 * Prompts for username, email and password. The password is read without
 * echo where the terminal supports it and is stored only as a bcrypt hash.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/../config/init.php';

function prompt($label, $hidden = false) {
    fwrite(STDOUT, $label);
    if ($hidden && stripos(PHP_OS, 'WIN') === false) {
        shell_exec('stty -echo 2>/dev/null');
        $val = rtrim(fgets(STDIN), "\r\n");
        shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, "\n");
        return $val;
    }
    return rtrim(fgets(STDIN), "\r\n");
}

$username = prompt('Username: ');
$email    = prompt('Email: ');
$fullName = prompt('Full name: ');
$password = prompt('Password (min 12 chars, letters + numbers): ', true);
$confirm  = prompt('Confirm password: ', true);

if ($password !== $confirm) {
    exit("Passwords do not match.\n");
}
if ($err = Security::passwordPolicyError($password)) {
    exit($err . "\n");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Invalid email.\n");
}

$pdo = Database::getInstance();

// Ensure the Super Admin role exists.
$roleId = $pdo->query("SELECT id FROM roles WHERE role_name = 'Super Admin Officer'")->fetchColumn();
if (!$roleId) {
    $pdo->prepare("INSERT INTO roles (role_name, description, is_system_role) VALUES ('Super Admin Officer', 'Overall System Administrator', 1)")->execute();
    $roleId = $pdo->lastInsertId();
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$existing = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$existing->execute([$username, $email]);
$userId = $existing->fetchColumn();

if ($userId) {
    $pdo->prepare("UPDATE users SET password_hash = ?, is_active = 1, login_attempts = 0, lockout_until = NULL, updated_at = NOW() WHERE id = ?")
        ->execute([$hash, $userId]);
    echo "Updated existing user #{$userId} password.\n";
} else {
    $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, is_active, created_at, updated_at)
                   VALUES (?, ?, ?, ?, 1, NOW(), NOW())")
        ->execute([$username, $email, $hash, $fullName]);
    $userId = $pdo->lastInsertId();
    echo "Created user #{$userId}.\n";
}

$link = $pdo->prepare("SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = ?");
$link->execute([$userId, $roleId]);
if (!$link->fetchColumn()) {
    $pdo->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())")
        ->execute([$userId, $roleId]);
}

echo "Done. Sign in and you will be prompted to enrol in two-factor authentication.\n";
