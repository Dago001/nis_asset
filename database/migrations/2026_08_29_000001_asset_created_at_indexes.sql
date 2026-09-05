-- =====================================================================
--  reports/summary (and the other asset reports) sort/filter every one
--  of these tables by created_at, and the new monthly-growth-trend
--  chart on reports/summary groups by created_at over a rolling date
--  window. None of these tables had an index on created_at, so every
--  such query fell back to a full table scan + filesort — cheap today
--  at ~100k rows per table, but the first thing to become "reports take
--  forever" again as the tables grow. Add the index now while it's free.
-- =====================================================================

ALTER TABLE `land_assets`        ADD INDEX IF NOT EXISTS `idx_la_created_at` (`created_at`);
ALTER TABLE `building_assets`    ADD INDEX IF NOT EXISTS `idx_ba_created_at` (`created_at`);
ALTER TABLE `rented_properties`  ADD INDEX IF NOT EXISTS `idx_rp_created_at` (`created_at`);
ALTER TABLE `movable_assets`     ADD INDEX IF NOT EXISTS `idx_ma_created_at` (`created_at`);
ALTER TABLE `ict_assets`         ADD INDEX IF NOT EXISTS `idx_ia_created_at` (`created_at`);
ALTER TABLE `vehicle_assets`     ADD INDEX IF NOT EXISTS `idx_va_created_at` (`created_at`);
ALTER TABLE `aircraft_assets`    ADD INDEX IF NOT EXISTS `idx_aa_created_at` (`created_at`);
ALTER TABLE `motorcycle_assets`  ADD INDEX IF NOT EXISTS `idx_moa_created_at` (`created_at`);
ALTER TABLE `weapons_inventory`     ADD INDEX IF NOT EXISTS `idx_wi_created_at` (`created_at`);
ALTER TABLE `ammunition_inventory`  ADD INDEX IF NOT EXISTS `idx_ai_created_at` (`created_at`);
