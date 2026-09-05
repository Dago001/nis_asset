-- =====================================================================
--  Four roles existed in the `roles` table with zero rows in
--  role_permissions, making them non-functional: a user assigned only
--  one of these roles could log in but would fail Auth::can() on
--  essentially everything.
--
--    - admin: now a full user-management role (users.manage granted
--      explicitly in code — see Auth::can()) plus broad operational
--      access, short of Role/Settings management and Super Admin
--      governance, which stay Super-Admin-only.
--    - CGIS: "Analytical Dashboard Access Only" — view-only across every
--      module, no create/edit/delete/approve anywhere.
--    - Command Armorer / HQ Armorer: their requisition.* access already
--      worked via a hardcoded role-name check in Auth::can(), but
--      weapons.*/ammunition.* access (what an armorer actually needs
--      day to day) goes through the normal role_permissions table and
--      had nothing granted at all.
--
--  Each row's flags are spelled out explicitly (view/create/edit/delete/
--  approve) rather than derived from the permission_key text — a couple
--  of keys (reports.export, users.manage) don't end in a word that maps
--  cleanly onto one of the five flags, and getUserPermissions() only
--  picks up a row when at least one flag is 1.
--
--  Idempotent: role_permissions has a unique (role_id, permission_id)
--  key, so this is safe to re-run.
-- =====================================================================

INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, g.can_view, g.can_create, g.can_edit, g.can_delete, g.can_approve
FROM (
    -- admin
    SELECT 'admin' role_name, 'dashboard.view' permission_key, 1 can_view, 0 can_create, 0 can_edit, 0 can_delete, 0 can_approve
    UNION ALL SELECT 'admin', 'land.view',        1,0,0,0,0
    UNION ALL SELECT 'admin', 'land.create',       0,1,0,0,0
    UNION ALL SELECT 'admin', 'land.edit',         0,0,1,0,0
    UNION ALL SELECT 'admin', 'land.delete',       0,0,0,1,0
    UNION ALL SELECT 'admin', 'building.view',     1,0,0,0,0
    UNION ALL SELECT 'admin', 'building.create',   0,1,0,0,0
    UNION ALL SELECT 'admin', 'building.edit',     0,0,1,0,0
    UNION ALL SELECT 'admin', 'building.delete',   0,0,0,1,0
    UNION ALL SELECT 'admin', 'rented.view',       1,0,0,0,0
    UNION ALL SELECT 'admin', 'rented.create',     0,1,0,0,0
    UNION ALL SELECT 'admin', 'rented.edit',       0,0,1,0,0
    UNION ALL SELECT 'admin', 'rented.delete',     0,0,0,1,0
    UNION ALL SELECT 'admin', 'movable.view',      1,0,0,0,0
    UNION ALL SELECT 'admin', 'movable.create',    0,1,0,0,0
    UNION ALL SELECT 'admin', 'movable.edit',      0,0,1,0,0
    UNION ALL SELECT 'admin', 'movable.delete',    0,0,0,1,0
    UNION ALL SELECT 'admin', 'ict.view',          1,0,0,0,0
    UNION ALL SELECT 'admin', 'ict.create',        0,1,0,0,0
    UNION ALL SELECT 'admin', 'ict.edit',          0,0,1,0,0
    UNION ALL SELECT 'admin', 'ict.delete',        0,0,0,1,0
    UNION ALL SELECT 'admin', 'fleet.view',        1,0,0,0,0
    UNION ALL SELECT 'admin', 'fleet.create',      0,1,0,0,0
    UNION ALL SELECT 'admin', 'fleet.edit',        0,0,1,0,0
    UNION ALL SELECT 'admin', 'fleet.delete',      0,0,0,1,0
    UNION ALL SELECT 'admin', 'weapons.view',      1,0,0,0,0
    UNION ALL SELECT 'admin', 'weapons.create',    0,1,0,0,0
    UNION ALL SELECT 'admin', 'weapons.edit',      0,0,1,0,0
    UNION ALL SELECT 'admin', 'weapons.delete',    0,0,0,1,0
    UNION ALL SELECT 'admin', 'ammunition.view',   1,0,0,0,0
    UNION ALL SELECT 'admin', 'ammunition.create', 0,1,0,0,0
    UNION ALL SELECT 'admin', 'ammunition.edit',   0,0,1,0,0
    UNION ALL SELECT 'admin', 'ammunition.delete', 0,0,0,1,0
    UNION ALL SELECT 'admin', 'audit.view',        1,0,0,0,0
    UNION ALL SELECT 'admin', 'audit.create',      0,1,0,0,0
    UNION ALL SELECT 'admin', 'audit.approve',     0,0,0,0,1
    UNION ALL SELECT 'admin', 'reports.view',      1,0,0,0,0
    UNION ALL SELECT 'admin', 'reports.export',    1,0,0,0,0
    UNION ALL SELECT 'admin', 'users.manage',      0,0,1,0,0

    -- CGIS: view-only, everywhere
    UNION ALL SELECT 'CGIS', 'dashboard.view',   1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'land.view',        1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'building.view',    1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'rented.view',      1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'movable.view',     1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'ict.view',         1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'fleet.view',       1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'weapons.view',     1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'ammunition.view',  1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'audit.view',       1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'reports.view',     1,0,0,0,0
    UNION ALL SELECT 'CGIS', 'reports.export',   1,0,0,0,0

    -- Command Armorer: full weapons/ammunition CRUD, command-scoped by
    -- Auth::isCommandRestricted(). requisition.* already works via the
    -- hardcoded role check in Auth::can().
    UNION ALL SELECT 'Command Armorer', 'dashboard.view',    1,0,0,0,0
    UNION ALL SELECT 'Command Armorer', 'weapons.view',      1,0,0,0,0
    UNION ALL SELECT 'Command Armorer', 'weapons.create',    0,1,0,0,0
    UNION ALL SELECT 'Command Armorer', 'weapons.edit',      0,0,1,0,0
    UNION ALL SELECT 'Command Armorer', 'weapons.delete',    0,0,0,1,0
    UNION ALL SELECT 'Command Armorer', 'ammunition.view',   1,0,0,0,0
    UNION ALL SELECT 'Command Armorer', 'ammunition.create', 0,1,0,0,0
    UNION ALL SELECT 'Command Armorer', 'ammunition.edit',   0,0,1,0,0
    UNION ALL SELECT 'Command Armorer', 'ammunition.delete', 0,0,0,1,0

    -- HQ Armorer: same, service-wide (HQ-exempt from command scoping),
    -- plus reporting/audit visibility.
    UNION ALL SELECT 'HQ Armorer', 'dashboard.view',    1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'weapons.view',      1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'weapons.create',    0,1,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'weapons.edit',      0,0,1,0,0
    UNION ALL SELECT 'HQ Armorer', 'weapons.delete',    0,0,0,1,0
    UNION ALL SELECT 'HQ Armorer', 'ammunition.view',   1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'ammunition.create', 0,1,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'ammunition.edit',   0,0,1,0,0
    UNION ALL SELECT 'HQ Armorer', 'ammunition.delete', 0,0,0,1,0
    UNION ALL SELECT 'HQ Armorer', 'audit.view',        1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'reports.view',      1,0,0,0,0
    UNION ALL SELECT 'HQ Armorer', 'reports.export',    1,0,0,0,0
) g
JOIN roles r ON r.role_name = g.role_name
JOIN permissions p ON p.permission_key = g.permission_key
ON DUPLICATE KEY UPDATE
    can_view = VALUES(can_view), can_create = VALUES(can_create),
    can_edit = VALUES(can_edit), can_delete = VALUES(can_delete),
    can_approve = VALUES(can_approve);

-- Bring the 'admin' role's stored description in line with its new,
-- broader reality (it used to say "no User Management").
UPDATE roles
SET description = 'System Administrator — full operational access and user management, excluding Role/Settings management and Super Admin governance'
WHERE role_name = 'admin';
