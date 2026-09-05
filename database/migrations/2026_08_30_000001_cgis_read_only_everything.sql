-- =====================================================================
--  Reverses part of the previous migration: CGIS should see everything
--  in the system (read-only), just not User Management or System
--  Settings. weapons.view/ammunition.view were revoked from CGIS in
--  2026_08_29_000003 when "Weapons Management" was locked down to 5
--  specific roles; re-grant them here now that CGIS is meant to see
--  that module too. Also grants returns.view, which never existed for
--  any role before this. requisition.view needs no row here — it's
--  handled by the hardcoded role list in Auth::can().
--
--  The actual read-only enforcement (no create/edit/delete/approve/
--  manage regardless of what's granted) lives in Auth::can() itself,
--  not here — this migration only needs to add the *view* grants CGIS
--  was still missing.
-- =====================================================================

INSERT INTO role_permissions (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT r.id, p.id, 1, 0, 0, 0, 0
FROM roles r
JOIN permissions p ON p.permission_key IN ('weapons.view', 'ammunition.view', 'returns.view')
WHERE r.role_name = 'CGIS'
ON DUPLICATE KEY UPDATE
    can_view = VALUES(can_view), can_create = 0, can_edit = 0, can_delete = 0, can_approve = 0;
