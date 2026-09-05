-- =====================================================================
--  Per-user login geofencing.
--
--  An admin can manually pin a single allowed location + radius (meters)
--  on a specific user's account (see UserController::update(), the
--  "Location Restriction" section on the Edit User page). When enabled,
--  AuthController requires a matching browser-reported GPS location
--  before completing that user's login — see /auth/geo-check and
--  /auth/geo-verify. Off by default; a Super Admin Officer account is
--  always exempt regardless of this flag (see AuthController::login()),
--  so there is always a way to reach the app and fix a bad setting.
-- =====================================================================

ALTER TABLE users
    ADD COLUMN geofence_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER lockout_until,
    ADD COLUMN geofence_lat DECIMAL(10,7) NULL AFTER geofence_enabled,
    ADD COLUMN geofence_lng DECIMAL(10,7) NULL AFTER geofence_lat,
    ADD COLUMN geofence_radius_m INT UNSIGNED NULL AFTER geofence_lng;
