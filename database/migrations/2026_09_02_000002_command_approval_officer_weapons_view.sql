-- =====================================================================
--  Grants "Command Approval Officer" read-only visibility into Weapons
--  and Ammunition inventory. Auth::isCommandRestricted() already scopes
--  weapons_inventory/ammunition_inventory to the viewer's own command for
--  this role (it's not in the HQ-exempt role list), so granting the view
--  permission is the only thing missing — the role previously had no way
--  to see weapons/ammunition at all, on top of needing to approve
--  requisitions for exactly that stock.
-- =====================================================================

INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 1, 0, 0, 0, 0
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Command Approval Officer'
  AND p.permission_key IN ('weapons.view', 'ammunition.view')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
