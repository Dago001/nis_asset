-- =====================================================================
--  Tracks live sessions (one row per currently-active PHP session for a
--  logged-in user) so an admin can see who's online right now — with IP
--  and device — and forcibly end a specific session (stolen device,
--  compromised account) without having to reset that person's password.
--
--  Distinct from the pre-existing `user_sessions` table, which only ever
--  stored "remember me" tokens, not live session state.
-- =====================================================================

CREATE TABLE IF NOT EXISTS active_sessions (
    session_id     VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id        INT(11) NOT NULL,
    ip_address     VARCHAR(45)  DEFAULT NULL,
    user_agent     VARCHAR(255) DEFAULT NULL,
    last_activity  DATETIME NOT NULL,
    created_at     DATETIME NOT NULL,
    revoked        TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_active_sessions_user (user_id),
    KEY idx_active_sessions_activity (last_activity),
    CONSTRAINT fk_active_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (permission_key, module, description)
SELECT 'sessions.manage', 'Admin', 'View active sessions and force-terminate one'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'sessions.manage');

-- Same governance-role scope as roles.manage/settings.manage: Super Admin only.
INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 1, 0, 0, 1, 0
FROM roles r
JOIN permissions p ON p.permission_key = 'sessions.manage'
WHERE r.role_name = 'Super Admin Officer'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
