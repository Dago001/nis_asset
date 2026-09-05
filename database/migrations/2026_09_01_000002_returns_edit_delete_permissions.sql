-- =====================================================================
--  Adds returns.edit / returns.delete permissions.
--
--  ReturnsController is getting edit()/update()/delete() methods (the
--  routes and UI links to them already existed — clicking them threw a
--  Fatal Error since the methods didn't exist). This migration grants
--  the new permissions to the same roles that already hold the sibling
--  returns.create/returns.process (for edit) and weapons.delete (for
--  delete, the closest existing precedent for a destructive armory
--  action) permissions, so nothing needs a role-assignment change to
--  pick this up.
-- =====================================================================

INSERT INTO permissions (permission_key, module, description)
SELECT 'returns.edit', 'returns', 'Edit an existing weapon/ammunition return'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'returns.edit');

INSERT INTO permissions (permission_key, module, description)
SELECT 'returns.delete', 'returns', 'Delete a weapon/ammunition return'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'returns.delete');

-- returns.edit -> same roles as returns.create / returns.process
INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 0, 0, 1, 0, 0
FROM roles r
JOIN permissions p ON p.permission_key = 'returns.edit'
WHERE r.role_name IN ('admin', 'Armorer', 'Command Armorer', 'HQ Armorer')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- returns.delete -> same roles as weapons.delete (excludes plain Armorer)
INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 0, 0, 0, 1, 0
FROM roles r
JOIN permissions p ON p.permission_key = 'returns.delete'
WHERE r.role_name IN ('admin', 'Command Armorer', 'HQ Armorer')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
