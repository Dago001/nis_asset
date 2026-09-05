-- =====================================================================
--  Fix: the armorer dashboard's "recent issues" query has always relied
--  on an index named idx_wil_date that was never actually created,
--  causing either a hard SQL error (with FORCE INDEX) or, once that
--  hint was removed, a full table-scan + filesort that can take minutes
--  on a large weapon_issue_log. Add the index the code already expects.
-- =====================================================================

ALTER TABLE `weapon_issue_log`
    ADD INDEX IF NOT EXISTS `idx_wil_date` (`issue_date`);
