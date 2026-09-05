<?php
/**
 * Settings Controller
 * Handles the settings page and a full RESTful API for system settings.
 *
 * RESTful API endpoints (all require Super Admin):
 *   GET    /api/v1/settings          → list all settings grouped
 *   GET    /api/v1/settings/{key}    → get one setting
 *   POST   /api/v1/settings          → create a new setting
 *   PUT    /api/v1/settings/{key}    → update one setting
 *   POST   /api/v1/settings/batch    → bulk-update many settings at once
 *   DELETE /api/v1/settings/{key}    → delete one setting
 *   POST   /api/v1/settings/test-smtp → test SMTP connection
 */
class SettingsController extends Controller {

    private SettingsModel $model;

    public function __construct() {
        $this->model = new SettingsModel();
        $this->requireSuperAdmin();
    }

    // =========================================================
    //  HTML PAGE
    // =========================================================

    /**
     * Render the settings management page.
     */
    public function index(): void {
        $settings = $this->model->getAllSettings();
        $groups   = $this->model->getGroups();

        $this->view('settings/index', compact('settings', 'groups'));
    }

    /**
     * Export all settings as a downloadable JSON file.
     */
    public function export(): void {
        $settings = $this->model->getAllSettings();

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="nis-settings-' . date('Y-m-d') . '.json"');
        header('Cache-Control: no-store');
        echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * POST /settings/save
     * Saves a group of settings sent from the UI (non‑API endpoint).
     * Expected JSON payload:
     *   { "group": "general", "settings": { "key1": "value", "key2": 123 } }
     * Returns clean JSON via jsonApi().
     */
    public function saveGroup(): void {
        $this->enforceJson();
        $this->verifyCsrf();
        $body = $this->readJsonBody();
        $group    = trim($body['group'] ?? '');
        $settings = $body['settings'] ?? [];

        if ($group === '' || !is_array($settings) || empty($settings)) {
            $this->jsonApi(422, ['success' => false, 'message' => 'Invalid payload. Group and settings are required.']);
            return;
        }

        $processed = [];
        foreach ($settings as $key => $value) {
            $key = trim((string)$key);
            if ($key === '' || $key === 'app_url') continue; // controlled by .env, not the DB
            $row = $this->model->getSettingRow($key);
            if ($row) {
                $validation = $this->validateValue($value, $row['data_type']);
                if ($validation['error']) {
                    $this->jsonApi(422, ['success' => false, 'message' => "Invalid value for {$key}: {$validation['message']}"]);
                    return;
                }
                $processed[$key] = [
                    'value'        => $validation['value'],
                    'data_type'    => $row['data_type'],
                    'group'        => $group,
                    'description'  => $row['description'] ?? '',
                    'is_encrypted' => !empty($row['is_encrypted']),
                ];
            } else {
                // If setting does not exist, create a new one with defaults
                $processed[$key] = [
                    'value'        => $value,
                    'data_type'    => 'string',
                    'group'        => $group,
                    'description'  => '',
                    'is_encrypted' => false,
                ];
            }
        }

        if ($this->model->saveSettings($processed)) {
            $this->logSettingsActivity('settings_group_saved', "Group '{$group}' saved via UI");
            $this->jsonApi(200, ['success' => true, 'message' => 'Settings saved.']);
        } else {
            $this->jsonApi(500, ['success' => false, 'message' => 'Failed to save settings.']);
        }
    }

    /**
     * POST /settings/import
     * Import settings from an uploaded JSON file.
     */
    public function import(): void {
        $this->verifyCsrf();

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('settings', ['error' => 'Please upload a valid JSON file.']);
            return;
        }

        $content  = file_get_contents($_FILES['import_file']['tmp_name']);
        $decoded  = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $this->redirect('settings', ['error' => 'Invalid JSON file.']);
            return;
        }

        $processed = [];
        foreach ($decoded as $group => $groupSettings) {
            if (!is_array($groupSettings)) continue;
            foreach ($groupSettings as $row) {
                if (empty($row['setting_key'])) continue;
                $key = $row['setting_key'];
                $processed[$key] = [
                    'value'        => $row['setting_value'] ?? '',
                    'data_type'    => $row['data_type']     ?? 'string',
                    'group'        => $group,
                    'description'  => $row['description']   ?? '',
                    'is_encrypted' => !empty($row['is_encrypted']),
                ];
            }
        }

        if ($this->model->saveSettings($processed)) {
            $this->logSettingsActivity('settings_imported', 'Settings were imported from file');
            $this->redirect('settings', ['success' => 'Settings imported successfully.']);
        } else {
            $this->redirect('settings', ['error' => 'Failed to import settings.']);
        }
    }

    // =========================================================
    //  REST API — GET
    // =========================================================

    /**
     * GET /api/v1/settings
     * Returns all settings, grouped, as JSON.
     */
    public function apiIndex(): void {
        $this->enforceJson();
        $settings = $this->model->getAllSettings();
        $this->jsonApi(200, ['success' => true, 'data' => $settings]);
    }

    /**
     * GET /api/v1/settings/{key}
     * Returns a single setting by key.
     */
    public function apiGet(string $key = ''): void {
        $this->enforceJson();

        $row = $this->model->getSettingRow($key);
        if (!$row) {
            $this->jsonApi(404, ['success' => false, 'message' => "Setting '{$key}' not found."]);
            return;
        }

        $this->jsonApi(200, ['success' => true, 'data' => $row]);
    }

    // =========================================================
    //  REST API — POST (CREATE)
    // =========================================================

    /**
     * POST /api/v1/settings
     * Create a new setting.
     * Body (JSON): { key, value, group, data_type, description, is_encrypted }
     */
    public function apiCreate(): void {
        $this->enforceJson();
        $this->verifyCsrf();

        $body = $this->readJsonBody();

        $key         = trim($body['key']          ?? '');
        $value       = $body['value']              ?? '';
        $group       = trim($body['group']         ?? 'general');
        $dataType    = trim($body['data_type']     ?? 'string');
        $description = trim($body['description']   ?? '');
        $isEncrypted = !empty($body['is_encrypted']);

        // Validate key
        if (empty($key)) {
            $this->jsonApi(422, ['success' => false, 'message' => 'Setting key is required.']);
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $key)) {
            $this->jsonApi(422, ['success' => false, 'message' => 'Invalid key format. Use letters, numbers, dots, underscores.']);
            return;
        }
        if ($this->model->getSettingRow($key)) {
            $this->jsonApi(409, ['success' => false, 'message' => "Setting key '{$key}' already exists."]);
            return;
        }

        // Validate value
        $validation = $this->validateValue($value, $dataType);
        if ($validation['error']) {
            $this->jsonApi(422, ['success' => false, 'message' => $validation['message']]);
            return;
        }

        if ($this->model->saveSetting($key, $validation['value'], $group, $dataType, $description, $isEncrypted)) {
            $this->logSettingsActivity('setting_created', "Created setting: {$key}");
            $row = $this->model->getSettingRow($key);
            $this->jsonApi(201, ['success' => true, 'message' => 'Setting created.', 'data' => $row]);
        } else {
            $this->jsonApi(500, ['success' => false, 'message' => 'Failed to create setting.']);
        }
    }

    // =========================================================
    //  REST API — PUT (UPDATE ONE)
    // =========================================================

    /**
     * PUT /api/v1/settings/{key}
     * Update value (and optionally metadata) of a single setting.
     * Body (JSON): { value, group?, data_type?, description?, is_encrypted? }
     */
    public function apiUpdate(string $key = ''): void {
        $this->enforceJson();
        $this->verifyCsrf();

        if ($key === 'app_url') {
            $this->jsonApi(422, ['success' => false, 'message' => 'app_url is controlled by APP_URL in .env and cannot be changed here.']);
            return;
        }

        $existing = $this->model->getSettingRow($key);
        if (!$existing) {
            $this->jsonApi(404, ['success' => false, 'message' => "Setting '{$key}' not found."]);
            return;
        }

        $body = $this->readJsonBody();

        $value       = array_key_exists('value',       $body) ? $body['value']       : $existing['setting_value'];
        $group       = array_key_exists('group',       $body) ? trim($body['group'])       : $existing['setting_group'];
        $dataType    = array_key_exists('data_type',   $body) ? trim($body['data_type'])   : $existing['data_type'];
        $description = array_key_exists('description', $body) ? trim($body['description']) : ($existing['description'] ?? '');
        $isEncrypted = array_key_exists('is_encrypted', $body) ? !empty($body['is_encrypted']) : (bool)$existing['is_encrypted'];

        $validation = $this->validateValue($value, $dataType);
        if ($validation['error']) {
            $this->jsonApi(422, ['success' => false, 'message' => $validation['message']]);
            return;
        }

        if ($this->model->saveSetting($key, $validation['value'], $group, $dataType, $description, $isEncrypted)) {
            $this->logSettingsActivity('setting_updated', "Updated setting: {$key}");
            $row = $this->model->getSettingRow($key);
            $this->jsonApi(200, ['success' => true, 'message' => 'Setting updated.', 'data' => $row]);
        } else {
            $this->jsonApi(500, ['success' => false, 'message' => 'Failed to update setting.']);
        }
    }

    // =========================================================
    //  REST API — POST /batch (BULK UPDATE)
    // =========================================================

    /**
     * POST /api/v1/settings/batch
     * Bulk-update many settings in one request.
     * Body (JSON): { settings: { key: value, ... }, groups?: {...}, types?: {...}, descriptions?: {...}, encrypted?: {...} }
     */
    public function apiBatch(): void {
        $this->enforceJson();
        $this->verifyCsrf();

        $body     = $this->readJsonBody();
        $settings = $body['settings'] ?? [];

        if (empty($settings) || !is_array($settings)) {
            $this->jsonApi(422, ['success' => false, 'message' => 'No settings provided.']);
            return;
        }

        $processed = [];
        foreach ($settings as $key => $value) {
            if ($key === 'app_url') continue; // controlled by .env, not the DB
            $existing = $this->model->getSettingRow($key);
            if (!$existing) continue; // skip unknown keys in batch update

            $dataType    = $body['types'][$key]        ?? $existing['data_type'];
            $group       = $body['groups'][$key]       ?? $existing['setting_group'];
            $description = $body['descriptions'][$key] ?? ($existing['description'] ?? '');
            $isEncrypted = isset($body['encrypted'][$key])
                           ? (bool)$body['encrypted'][$key]
                           : (bool)$existing['is_encrypted'];

            $validation = $this->validateValue($value, $dataType);
            if ($validation['error']) {
                $this->jsonApi(422, ['success' => false, 'message' => "Invalid value for {$key}: " . $validation['message']]);
                return;
            }

            $processed[$key] = [
                'value'        => $validation['value'],
                'data_type'    => $dataType,
                'group'        => $group,
                'description'  => $description,
                'is_encrypted' => $isEncrypted,
            ];
        }

        if ($this->model->saveSettings($processed)) {
            $this->logSettingsActivity('settings_batch_updated', 'Bulk settings update: ' . count($processed) . ' settings');
            $this->jsonApi(200, ['success' => true, 'message' => count($processed) . ' settings saved successfully.']);
        } else {
            $this->jsonApi(500, ['success' => false, 'message' => 'Failed to save settings.']);
        }
    }

    // =========================================================
    //  REST API — DELETE
    // =========================================================

    /**
     * DELETE /api/v1/settings/{key}
     * Permanently removes a non-protected setting.
     */
    public function apiDelete(string $key = ''): void {
        $this->enforceJson();
        $this->verifyCsrf();

        $protected = ['app_name', 'app_version', 'app_url', 'encryption_key', 'company_name'];
        if (in_array($key, $protected, true)) {
            $this->jsonApi(403, ['success' => false, 'message' => "Cannot delete protected setting '{$key}'."]);
            return;
        }

        if (!$this->model->getSettingRow($key)) {
            $this->jsonApi(404, ['success' => false, 'message' => "Setting '{$key}' not found."]);
            return;
        }

        if ($this->model->deleteSetting($key)) {
            $this->logSettingsActivity('setting_deleted', "Deleted setting: {$key}");
            $this->jsonApi(200, ['success' => true, 'message' => "Setting '{$key}' deleted."]);
        } else {
            $this->jsonApi(500, ['success' => false, 'message' => 'Failed to delete setting.']);
        }
    }

    // =========================================================
    //  REST API — POST /test-smtp
    // =========================================================

    /**
     * POST /api/v1/settings/test-smtp
     * Tests the SMTP connection using current saved settings.
     * Body (JSON): { to: "email@example.com" }
     */
    public function apiTestSmtp(): void {
        $this->enforceJson();
        $this->verifyCsrf();

        $body = $this->readJsonBody();
        $to   = filter_var($body['to'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$to) {
            $this->jsonApi(422, ['success' => false, 'message' => 'A valid recipient email is required.']);
            return;
        }

        $smtpHost = $this->model->getSetting('smtp_host');
        $smtpPort = (int)$this->model->getSetting('smtp_port', 587);
        $smtpUser = $this->model->getSetting('smtp_username');
        $smtpPass = $this->model->getSetting('smtp_password');
        $smtpEnc  = strtolower((string)$this->model->getSetting('smtp_encryption', 'tls'));
        $fromEmail = $this->model->getSetting('from_email', 'noreply@nis.gov.ng');
        $appName   = $this->model->getSetting('app_name', 'NIS AMS');

        if (empty($smtpHost)) {
            $this->jsonApi(400, ['success' => false, 'message' => 'SMTP host is not configured.']);
            return;
        }

        // Attempt connection via socket
        $prefix  = ($smtpEnc === 'ssl') ? 'ssl://' : '';
        $timeout = 10;
        $errno   = 0;
        $errstr  = '';
        $sock = @fsockopen($prefix . $smtpHost, $smtpPort, $errno, $errstr, $timeout);

        if (!$sock) {
            $this->jsonApi(502, ['success' => false, 'message' => "Cannot connect to SMTP server {$smtpHost}:{$smtpPort}. Error: {$errstr}"]);
            return;
        }

        fclose($sock);

        // If PHPMailer is available, do a full send test
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->Port       = $smtpPort;
                $mail->SMTPAuth   = !empty($smtpUser);
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = $smtpEnc === 'ssl'
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->setFrom($fromEmail, $appName);
                $mail->addAddress($to);
                $mail->Subject = 'NIS AMS — SMTP Test';
                $mail->Body    = 'This is a test email confirming your SMTP configuration is working correctly.';
                $mail->send();
                $this->jsonApi(200, ['success' => true, 'message' => "Test email sent to {$to} successfully."]);
            } catch (\Exception $e) {
                $this->jsonApi(502, ['success' => false, 'message' => 'SMTP authentication/send failed: ' . $e->getMessage()]);
            }
            return;
        }

        // No PHPMailer — socket test passed, report that
        $this->jsonApi(200, ['success' => true, 'message' => "SMTP socket connection to {$smtpHost}:{$smtpPort} succeeded. (Full send test requires PHPMailer.)"]);
    }

    // =========================================================
    //  HELPERS
    // =========================================================

    /**
     * Ensure the current user is a Super Admin; abort otherwise.
     */
    private function requireSuperAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
            return;
        }

        $isSuperAdmin = (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true)
            || (class_exists('Auth') && Auth::isSuperAdmin());

        if (!$isSuperAdmin && isset($_SESSION['roles'])) {
            foreach ($_SESSION['roles'] as $role) {
                if (stripos($role, 'Super Admin') !== false) {
                    $isSuperAdmin = true;
                    break;
                }
            }
        }

        if (!$isSuperAdmin) {
            if ($this->isApiRequest()) {
                $this->jsonApi(403, ['success' => false, 'message' => 'Forbidden: Super Admin access required.']);
            } else {
                $_SESSION['error'] = 'You do not have permission to access System Settings.';
                $this->redirect('dashboard');
            }
        }
    }

/**
      * Verify CSRF token from header or body.
      * Uses the same session key as Security::csrfToken().
      */
    private function verifyCsrf(): void {
        // Token name matches Security class = '_csrf_token'
        $tokenKey = '_csrf_token';

        // Check header first (sent by JS fetch), then form POST field (either
        // name in use across this page), then JSON body.
        $body = $this->readJsonBody();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($_POST['_token'] ?? null)
            ?? ($_POST['csrf_token'] ?? null)
            ?? ($body['_token'] ?? null)
            ?? ($body['csrf_token'] ?? '');

        $sessionToken = $_SESSION[$tokenKey] ?? '';

        if (empty($sessionToken) || empty($token) || !hash_equals($sessionToken, $token)) {
            // 419 (the semantically-precise "CSRF token mismatch" code some
            // frameworks use) isn't a registered HTTP status and this
            // server's PHP/Apache combo reports it to clients as a bare 500,
            // masking the real cause. 403 is universally understood and
            // still accurately says "request rejected, not a server error."
            $this->jsonApi(403, [
                'success' => false,
                'message' => 'Security token mismatch. Please reload the page and try again.',
            ]);
        }
    }

    /**
     * Parse the request body as JSON. Falls back to empty array.
     */
    private function readJsonBody(): array {
        static $parsed = null;
        if ($parsed !== null) return $parsed;
        $raw    = file_get_contents('php://input');
        $parsed = json_decode($raw, true) ?? [];
        return $parsed;
    }

    /**
     * Detect API / XHR requests.
     */
    private function isApiRequest(): bool {
        return (
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains($_SERVER['REQUEST_URI'], '/api/')
        );
    }

    /**
     * Enforce JSON Accept / Content-Type on API calls.
     * Also starts output buffering so PHP notices/warnings can't pollute the JSON body.
     */
    private function enforceJson(): void {
        // Start OB so any stray PHP warning/notice is captured and discarded
        if (!ob_get_level()) {
            ob_start();
        }
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Emit a JSON response with the given HTTP status code and payload.
     * Cleans ALL output buffers first so PHP notices can't corrupt the JSON.
     */
    private function jsonApi(int $status, array $payload): void {
        // Discard any buffered output (PHP errors, notices, stray HTML)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Validate a setting value based on its declared type.
     * Returns ['error' => bool, 'value' => mixed, 'message' => string]
     */
    private function validateValue(mixed $value, string $type): array {
        $result = ['error' => false, 'value' => $value, 'message' => ''];

        switch ($type) {
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['error' => true, 'value' => $value, 'message' => 'Invalid email address.'];
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return ['error' => true, 'value' => $value, 'message' => 'Invalid URL.'];
                }
                break;

            case 'integer':
            case 'number':
                if ($value !== '' && $value !== null && !is_numeric($value)) {
                    return ['error' => true, 'value' => $value, 'message' => 'Value must be a number.'];
                }
                $result['value'] = (int)$value;
                break;

            case 'float':
            case 'double':
                if ($value !== '' && $value !== null && !is_numeric($value)) {
                    return ['error' => true, 'value' => $value, 'message' => 'Value must be a number.'];
                }
                $result['value'] = (float)$value;
                break;

            case 'boolean':
            case 'bool':
                $result['value'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                break;

            case 'json':
                if ($value) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return ['error' => true, 'value' => $value, 'message' => 'Invalid JSON.'];
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Log settings activity to the audit log.
     */
    private function logSettingsActivity(string $action, string $description): void {
        if (class_exists('AuditLogger')) {
            AuditLogger::log($action, 'settings', null, null, $description);
        }
    }

    /**
     * Redirect with optional flash messages.
     * Overrides parent to write session flash before Router::redirect().
     */
    protected function redirect($url, $flash = []) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        foreach ((array)$flash as $k => $v) {
            $_SESSION[$k] = $v;
        }
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/nis_ams';
        header('Location: ' . $baseUrl . '/' . ltrim($url, '/'));
        exit;
    }
}
