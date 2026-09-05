<?php
/**
 * Notification Controller
 */
class NotificationController extends Controller {
    
    /**
     * Get unread notifications API (JSON)
     */
    public function apiGetUnread() {
        if (!Auth::check()) {
            $this->jsonResponse(false, 'Unauthorized');
            return;
        }
        
        $userId = Auth::id();
        $userRoles = $_SESSION['roles'] ?? [];
        $userCommandId = Auth::commandId();
        
        // 1. Fetch user-specific notifications from DB
        $dbNotifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 20",
            [$userId]
        ) ?: [];
        
        // 2. Proactive pending approval alerts (ensures approvers never miss pending requests)
        $approvalAlerts = [];
        
        // A) Command Approval Officer: Check pending requisitions in their command
        if (in_array('Command Approval Officer', $userRoles, true) && !empty($userCommandId)) {
            try {
                $pendingCmd = Database::fetchAll(
                    "SELECT id, requisition_number, created_at FROM requisitions 
                     WHERE status = 'Pending' AND approval_stage = 'Command_Approval' AND requesting_command_id = ?
                     ORDER BY created_at DESC LIMIT 5",
                    [$userCommandId]
                ) ?: [];
                
                foreach ($pendingCmd as $p) {
                    $approvalAlerts[] = [
                        'id' => 'req_cmd_' . $p['id'],
                        'user_id' => $userId,
                        'message' => "Requisition {$p['requisition_number']} is awaiting your Command Approval.",
                        'link' => "/requisition/show/{$p['id']}",
                        'is_read' => 0,
                        'created_at' => $p['created_at']
                    ];
                }
            } catch (Exception $e) {}
        }
        
        // B) HQ Armorer: Check pending requisitions at HQ Vetting stage
        if (in_array('HQ Armorer', $userRoles, true)) {
            try {
                $pendingHq = Database::fetchAll(
                    "SELECT id, requisition_number, created_at FROM requisitions 
                     WHERE status = 'Pending' AND approval_stage = 'HQ_Vetting'
                     ORDER BY created_at DESC LIMIT 5"
                ) ?: [];
                
                foreach ($pendingHq as $p) {
                    $approvalAlerts[] = [
                        'id' => 'req_hq_' . $p['id'],
                        'user_id' => $userId,
                        'message' => "Requisition {$p['requisition_number']} is awaiting HQ Vetting & Approval.",
                        'link' => "/requisition/show/{$p['id']}",
                        'is_read' => 0,
                        'created_at' => $p['created_at']
                    ];
                }
            } catch (Exception $e) {}
        }
        
        // 3. Query expiring soon warnings (within 30 days)
        $expiryNotifications = [];
        $today = date('Y-m-d');
        $expiryLimit = date('Y-m-d', strtotime('+30 days'));
        
        // Vehicle Insurance Expiry
        try {
            $vehicles = Database::fetchAll(
                "SELECT id, asset_code, registration_number, insurance_expiry FROM vehicle_assets 
                 WHERE insurance_expiry >= ? AND insurance_expiry <= ? ORDER BY insurance_expiry ASC LIMIT 5",
                [$today, $expiryLimit]
            ) ?: [];
            foreach ($vehicles as $v) {
                $expiryNotifications[] = [
                    'id' => 'v_ins_' . $v['id'],
                    'user_id' => $userId,
                    'message' => "Vehicle insurance expiring: {$v['asset_code']} ({$v['registration_number']}) on {$v['insurance_expiry']}",
                    'link' => "/fleet/vehicles/edit/{$v['id']}",
                    'is_read' => 0,
                    'created_at' => $v['insurance_expiry'] . ' 00:00:00'
                ];
            }
        } catch (Exception $e) {}
        
        // Aircraft Insurance Expiry
        try {
            $aircrafts = Database::fetchAll(
                "SELECT id, asset_code, tail_number, insurance_expiry FROM aircraft_assets 
                 WHERE insurance_expiry >= ? AND insurance_expiry <= ? ORDER BY insurance_expiry ASC LIMIT 5",
                [$today, $expiryLimit]
            ) ?: [];
            foreach ($aircrafts as $a) {
                $expiryNotifications[] = [
                    'id' => 'a_ins_' . $a['id'],
                    'user_id' => $userId,
                    'message' => "Aircraft insurance expiring: {$a['asset_code']} ({$a['tail_number']}) on {$a['insurance_expiry']}",
                    'link' => "/fleet/aircraft/edit/{$a['id']}",
                    'is_read' => 0,
                    'created_at' => $a['insurance_expiry'] . ' 00:00:00'
                ];
            }
        } catch (Exception $e) {}

        // Combine and de-duplicate by message/link
        $allRaw = array_merge($approvalAlerts, $dbNotifications, $expiryNotifications);
        $seen = [];
        $formatted = [];
        
        foreach ($allRaw as $n) {
            $key = ($n['link'] ?? '') . '|' . ($n['message'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $formatted[] = $this->formatNotification($n);
        }
        
        $totalCount = count($formatted);
        
        $this->jsonResponse(true, 'Unread notifications fetched', [
            'notifications' => $formatted,
            'count' => $totalCount
        ]);
    }
    
    /**
     * Format notification for front-end rendering
     */
    protected function formatNotification($n) {
        $msg = $n['message'] ?? '';
        $type = 'info';
        $badge = 'Notice';
        $icon = 'fas fa-bell';
        $iconColor = '#1F6F8B';
        $iconBg = 'rgba(31, 111, 139, 0.12)';
        $badgeBg = '#e8f4fd';
        $badgeColor = '#1F6F8B';

        $msgLower = strtolower($msg);

        if (strpos($msgLower, 'submitted for approval') !== false || 
            strpos($msgLower, 'awaiting') !== false || 
            strpos($msgLower, 'pending') !== false || 
            strpos($msgLower, 'for approval') !== false) {
            $type = 'approval_request';
            $badge = 'Approval Request';
            $icon = 'fas fa-file-signature';
            $iconColor = '#d97706';
            $iconBg = 'rgba(217, 119, 6, 0.14)';
            $badgeBg = '#fef3c7';
            $badgeColor = '#92400e';
        } elseif (strpos($msgLower, 'rejected') !== false || strpos($msgLower, 'rejection') !== false) {
            $type = 'rejected';
            $badge = 'Rejected';
            $icon = 'fas fa-times-circle';
            $iconColor = '#dc2626';
            $iconBg = 'rgba(220, 38, 38, 0.14)';
            $badgeBg = '#fee2e2';
            $badgeColor = '#b91c1c';
        } elseif (strpos($msgLower, 'approved') !== false) {
            $type = 'approved';
            $badge = 'Approved';
            $icon = 'fas fa-check-circle';
            $iconColor = '#16a34a';
            $iconBg = 'rgba(22, 163, 74, 0.14)';
            $badgeBg = '#dcfce7';
            $badgeColor = '#15803d';
        } elseif (strpos($msgLower, 'issued') !== false || strpos($msgLower, 'fulfilled') !== false || strpos($msgLower, 'completed') !== false) {
            $type = 'fulfillment';
            $badge = 'Fulfilled';
            $icon = 'fas fa-box-check';
            $iconColor = '#2563eb';
            $iconBg = 'rgba(37, 99, 235, 0.14)';
            $badgeBg = '#dbeafe';
            $badgeColor = '#1d4ed8';
        } elseif (strpos($msgLower, 'expir') !== false || strpos($msgLower, 'warning') !== false) {
            $type = 'warning';
            $badge = 'Expiry Warning';
            $icon = 'fas fa-exclamation-triangle';
            $iconColor = '#ca8a04';
            $iconBg = 'rgba(202, 138, 4, 0.14)';
            $badgeBg = '#fef9c3';
            $badgeColor = '#854d0e';
        }

        // Relative time formatting
        $timeAgo = 'Just now';
        if (!empty($n['created_at'])) {
            $timestamp = strtotime($n['created_at']);
            if ($timestamp) {
                $diff = time() - $timestamp;
                if ($diff < 60) {
                    $timeAgo = 'Just now';
                } elseif ($diff < 3600) {
                    $mins = max(1, floor($diff / 60));
                    $timeAgo = $mins . 'm ago';
                } elseif ($diff < 86400) {
                    $hours = max(1, floor($diff / 3600));
                    $timeAgo = $hours . 'h ago';
                } elseif ($diff < 604800) {
                    $days = max(1, floor($diff / 86400));
                    $timeAgo = $days . 'd ago';
                } else {
                    $timeAgo = date('M j, Y', $timestamp);
                }
            }
        }

        // Normalize URL link
        $baseUrl = $this->getBaseUrl();
        $link = $n['link'] ?? '';
        $targetUrl = '';
        if (!empty($link)) {
            if (preg_match('/^https?:\/\//i', $link)) {
                $targetUrl = $link;
            } else {
                $cleanLink = ltrim($link, '/');
                if (strpos($cleanLink, 'nis_ams/') === 0) {
                    $cleanLink = substr($cleanLink, 8);
                }
                if ($cleanLink === 'weapons/issue' || strpos($cleanLink, 'weapons/issue') === 0) {
                    $cleanLink = str_replace('weapons/issue', 'weapon_issue', $cleanLink);
                }
                $targetUrl = $baseUrl . '/' . ltrim($cleanLink, '/');
            }
        } else {
            $targetUrl = $baseUrl . '/dashboard';
        }

        return [
            'id' => $n['id'],
            'message' => $msg,
            'link' => $targetUrl,
            'raw_link' => $link,
            'is_read' => (int)($n['is_read'] ?? 0),
            'created_at' => $n['created_at'] ?? date('Y-m-d H:i:s'),
            'type' => $type,
            'badge' => $badge,
            'badge_bg' => $badgeBg,
            'badge_color' => $badgeColor,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'icon_bg' => $iconBg,
            'time_ago' => $timeAgo
        ];
    }
    
    /**
     * Mark a notification as read (JSON/Redirect)
     */
    public function markAsRead($id) {
        if (!Auth::check()) {
            $this->jsonResponse(false, 'Unauthorized');
            return;
        }
        
        $userId = Auth::id();
        $redirectLink = '';
        
        if (is_numeric($id)) {
            $notif = Database::fetchOne("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$id, $userId]);
            if ($notif) {
                Database::update('notifications', [
                    'is_read' => 1
                ], 'id = ? AND user_id = ?', [$id, $userId]);
                $redirectLink = $notif['link'];
            }
        } else {
            // Dynamic ID format parsing
            $parts = explode('_', (string)$id);
            if (count($parts) >= 3) {
                $type = $parts[0];
                $subType = $parts[1];
                $realId = $parts[2];
                if ($type === 'req') {
                    $redirectLink = "/requisition/show/{$realId}";
                } elseif ($type === 'v') {
                    $redirectLink = "/fleet/vehicles/edit/{$realId}";
                } elseif ($type === 'a') {
                    $redirectLink = "/fleet/aircraft/edit/{$realId}";
                } elseif ($type === 'r') {
                    $redirectLink = "/rented/edit/{$realId}";
                } elseif ($type === 'ict') {
                    $redirectLink = "/ict/edit/{$realId}";
                }
            }
        }
        
        // Normalize redirect URL
        $baseUrl = $this->getBaseUrl();
        if (!empty($redirectLink)) {
            if (preg_match('/^https?:\/\//i', $redirectLink)) {
                $targetUrl = $redirectLink;
            } else {
                $cleanLink = ltrim($redirectLink, '/');
                if (strpos($cleanLink, 'nis_ams/') === 0) {
                    $cleanLink = substr($cleanLink, 8);
                }
                if ($cleanLink === 'weapons/issue' || strpos($cleanLink, 'weapons/issue') === 0) {
                    $cleanLink = str_replace('weapons/issue', 'weapon_issue', $cleanLink);
                }
                $targetUrl = $baseUrl . '/' . ltrim($cleanLink, '/');
            }
        } else {
            $targetUrl = $baseUrl . '/dashboard';
        }
        
        if ($this->isAjax()) {
            $this->jsonResponse(true, 'Notification marked as read', ['redirect_url' => $targetUrl]);
        } else {
            header('Location: ' . $targetUrl);
            exit;
        }
    }
    
    /**
     * Mark all notifications as read (JSON/Redirect)
     */
    public function markAllAsRead() {
        if (!Auth::check()) {
            $this->jsonResponse(false, 'Unauthorized');
            return;
        }
        
        $userId = Auth::id();
        Database::update('notifications', [
            'is_read' => 1
        ], 'user_id = ? AND is_read = 0', [$userId]);
        
        if ($this->isAjax()) {
            $this->jsonResponse(true, 'All notifications marked as read');
        } else {
            $this->redirect('dashboard', ['success' => 'All notifications marked as read']);
        }
    }
    
    /**
     * Helper: Resolve Base URL safely
     */
    protected function getBaseUrl() {
        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/');
        }
        if (class_exists('Config')) {
            return rtrim(Config::get('base_url', 'http://localhost/nis_ams'), '/');
        }
        return 'http://localhost/nis_ams';
    }

    /**
     * Helper: Check if request is AJAX
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($success, $message, $data = []) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}
