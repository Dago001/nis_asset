<?php
/**
 * Active Sessions (Super Admin only)
 *
 * Shows every currently logged-in session — who, from what IP/device, and
 * how recently active — with the ability to force-terminate one. Distinct
 * from core/Session.php (the request-lifecycle session handler this reads
 * its data from) and the pre-existing `user_sessions` table (which only
 * ever tracked "remember me" tokens, not live session state).
 */
class SessionsController extends Controller {

    public function index() {
        if (!Auth::can('sessions.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view active sessions']);
            return;
        }

        // A session with no activity in a while is really just waiting on
        // PHP's garbage collector — don't show it as "online".
        $staleCutoff = date('Y-m-d H:i:s', time() - 1800); // 30 minutes

        $sessions = Database::fetchAll(
            "SELECT s.*, u.username, u.full_name,
                    (SELECT GROUP_CONCAT(r.role_name SEPARATOR ', ')
                       FROM user_roles ur JOIN roles r ON ur.role_id = r.id
                      WHERE ur.user_id = u.id) as role_names
             FROM active_sessions s
             JOIN users u ON s.user_id = u.id
             WHERE s.revoked = 0 AND s.last_activity >= ?
             ORDER BY s.last_activity DESC",
            [$staleCutoff]
        ) ?: [];

        $this->view('sessions/index', [
            'title' => 'Active Sessions',
            'active' => 'sessions',
            'sessions' => $sessions,
            'currentSessionId' => session_id(),
        ]);
    }

    // Named $id, not $sessionId — Router::dispatch() passes route params as
    // PHP 8 *named* arguments keyed by the route's own {id} placeholder, so
    // the parameter name here has to match that literally or the call blows
    // up with "Unknown named parameter" before this method body ever runs.
    public function terminate($id) {
        if (!Auth::can('sessions.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage sessions']);
            return;
        }

        if ($id === session_id()) {
            $this->redirect('sessions', ['error' => "That's your own current session — log out normally instead of terminating it here."]);
            return;
        }

        $row = Database::fetchOne("SELECT user_id FROM active_sessions WHERE session_id = ?", [$id]);
        if (!$row) {
            $this->redirect('sessions', ['error' => 'Session not found — it may have already ended.']);
            return;
        }

        Database::update('active_sessions', ['revoked' => 1], 'session_id = ?', [$id]);

        if (class_exists('AuditLogger')) {
            AuditLogger::log('SESSION_TERMINATED', 'active_sessions', $row['user_id'], null,
                "Force-terminated an active session for user #{$row['user_id']}");
        }

        $this->redirect('sessions', ['success' => 'Session terminated. That user will be signed out on their next request.']);
    }
}
