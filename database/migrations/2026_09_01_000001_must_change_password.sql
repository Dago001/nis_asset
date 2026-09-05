-- =====================================================================
--  Adds a "must change password on next login" flag.
--
--  Set to 1 whenever an admin creates a new account or resets someone's
--  password on their behalf, so the person is forced through a password
--  change before they can use anything else — they never end up using
--  a password only the admin who set it knows.
-- =====================================================================

ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
