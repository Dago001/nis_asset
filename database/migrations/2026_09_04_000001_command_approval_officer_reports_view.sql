-- =====================================================================
--  Grants "Command Approval Officer" read and export permissions for
--  Reports (Asset Reports and Weapons Report). Command-level scoping
--  ensures the officer only accesses and prints their assigned command's data.
-- =====================================================================

INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 1, 0, 0, 0, 0
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Command Approval Officer'
  AND p.permission_key IN ('reports.view', 'reports.export')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );