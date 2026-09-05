-- =====================================================================
--  Restrict weapons/ammunition access to exactly the 5 roles that should
--  have it: Super Admin Officer (bypasses the permission system
--  entirely), admin, HQ Armorer, Armorer, Command Armorer. Every other
--  role that had weapons.view/ammunition.view (CGIS, Command Approval
--  Officer, Command Data Entry Officer, HQ Sectional Supervisor, HQ
--  Vetting Officer) loses it — this also drives the sidebar's "Weapons
--  Management" section, which is gated on these same permissions.
--
--  Also fixes a real bug: ReturnsController checks Auth::can('returns.view'
--  / 'returns.create' / 'returns.process'), but no returns.* permission
--  ever existed in the `permissions` table — meaning nobody but Super
--  Admin could ever open /returns, despite the sidebar linking to it
--  for everyone. Adds the missing permission keys and grants them to
--  the same weapons-focused roles ("Armorer... manages returns").
-- =====================================================================

DELETE rp FROM role_permissions rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name IN ('CGIS', 'Command Approval Officer', 'Command Data Entry Officer', 'HQ Sectional Supervisor', 'HQ Vetting Officer')
  AND (p.permission_key LIKE 'weapons.%' OR p.permission_key LIKE 'ammunition.%');

INSERT INTO permissions (permission_key, module, description)
SELECT * FROM (
    SELECT 'returns.view' AS permission_key, 'Returns' AS module, 'View Weapon/Ammunition Returns' AS description
    UNION ALL SELECT 'returns.create', 'Returns', 'Create a Return'
    UNION ALL SELECT 'returns.process', 'Returns', 'Process/Verify a Return'
) v
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = v.permission_key);

INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, g.can_view, g.can_create, g.can_edit, g.can_delete, g.can_approve
FROM (
    SELECT 'admin' role_name, 'returns.view' permission_key, 1 can_view, 0 can_create, 0 can_edit, 0 can_delete, 0 can_approve
    UNION ALL SELECT 'admin', 'returns.create',  0,1,0,0,0
    UNION ALL SELECT 'admin', 'returns.process', 0,0,1,0,0
    UNION ALL SELECT 'Armorer', 'returns.view',    1,0,0,0,0
    UNION ALL SELECT 'Armorer', 'returns.create',  0,1,0,0,0
    UNION ALL SELECT 'Armorer', 'returns.process', 0,0,1,0,0
    UNION ALL SELECT 'Command Armorer', 'returns.view',    1,0,0,0,0
    UNION ALL SELECT 'Command Armorer', 'returns.create',  0,1,0,0,0
    UNION ALL SELECT 'Command Armorer', 'returns.process', 0,0,1,0,0
    UNION ALL SELECT 'HQ Armorer', 'returns.view',    1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'returns.create',  0,1,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'returns.process', 0,0,1,0,0
) g
JOIN roles r ON r.role_name = g.role_name
JOIN permissions p ON p.permission_key = g.permission_key
ON DUPLICATE KEY UPDATE
    can_view = VALUES(can_view), can_create = VALUES(can_create),
    can_edit = VALUES(can_edit), can_delete = VALUES(can_delete),
    can_approve = VALUES(can_approve);
