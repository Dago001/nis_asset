-- Insert default zones
INSERT INTO `zones` (`zone_code`, `zone_name`) VALUES
('HQ', 'Headquarters'),
('A', 'Zone A - Lagos'),
('B', 'Zone B - Kaduna'),
('C', 'Zone C - Bauchi'),
('D', 'Zone D - Minna'),
('E', 'Zone E - Owerri'),
('F', 'Zone F - Ibadan'),
('G', 'Zone G - Benin City'),
('H', 'Zone H - Makurdi');

-- Insert states
INSERT INTO `states` (`state_name`, `zone_id`) VALUES
('Abia', (SELECT id FROM zones WHERE zone_code = 'E')),
('Adamawa', (SELECT id FROM zones WHERE zone_code = 'C')),
('Akwa Ibom', (SELECT id FROM zones WHERE zone_code = 'E')),
('Anambra', (SELECT id FROM zones WHERE zone_code = 'G')),
('Bauchi', (SELECT id FROM zones WHERE zone_code = 'C')),
('Bayelsa', (SELECT id FROM zones WHERE zone_code = 'G')),
('Benue', (SELECT id FROM zones WHERE zone_code = 'H')),
('Borno', (SELECT id FROM zones WHERE zone_code = 'C')),
('Cross River', (SELECT id FROM zones WHERE zone_code = 'E')),
('Delta', (SELECT id FROM zones WHERE zone_code = 'G')),
('Ebonyi', (SELECT id FROM zones WHERE zone_code = 'E')),
('Edo', (SELECT id FROM zones WHERE zone_code = 'G')),
('Ekiti', (SELECT id FROM zones WHERE zone_code = 'F')),
('Enugu', (SELECT id FROM zones WHERE zone_code = 'G')),
('Federal Capital Territory', (SELECT id FROM zones WHERE zone_code = 'D')),
('Gombe', (SELECT id FROM zones WHERE zone_code = 'C')),
('Imo', (SELECT id FROM zones WHERE zone_code = 'E')),
('Jigawa', (SELECT id FROM zones WHERE zone_code = 'B')),
('Kaduna', (SELECT id FROM zones WHERE zone_code = 'B')),
('Kano', (SELECT id FROM zones WHERE zone_code = 'B')),
('Katsina', (SELECT id FROM zones WHERE zone_code = 'B')),
('Kebbi', (SELECT id FROM zones WHERE zone_code = 'D')),
('Kogi', (SELECT id FROM zones WHERE zone_code = 'D')),
('Kwara', (SELECT id FROM zones WHERE zone_code = 'D')),
('Lagos', (SELECT id FROM zones WHERE zone_code = 'A')),
('Nasarawa', (SELECT id FROM zones WHERE zone_code = 'D')),
('Niger', (SELECT id FROM zones WHERE zone_code = 'D')),
('Ogun', (SELECT id FROM zones WHERE zone_code = 'A')),
('Ondo', (SELECT id FROM zones WHERE zone_code = 'F')),
('Osun', (SELECT id FROM zones WHERE zone_code = 'F')),
('Oyo', (SELECT id FROM zones WHERE zone_code = 'F')),
('Plateau', (SELECT id FROM zones WHERE zone_code = 'C')),
('Rivers', (SELECT id FROM zones WHERE zone_code = 'E')),
('Sokoto', (SELECT id FROM zones WHERE zone_code = 'B')),
('Taraba', (SELECT id FROM zones WHERE zone_code = 'C')),
('Yobe', (SELECT id FROM zones WHERE zone_code = 'C')),
('Zamfara', (SELECT id FROM zones WHERE zone_code = 'B'));

-- Insert commands
INSERT INTO `commands` (`command_name`, `command_type`, `zone_id`) VALUES
('CGIS Office', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('Visa and Residency Directorate', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('Passport and Other Travel Document Directorate', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('Finance and Account Directorate', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('Works And Logistics Directorate', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('ICT And Cyber Security Directorate', 'Directorate', (SELECT id FROM zones WHERE zone_code = 'HQ')),
('Lagos State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'A')),
('Ogun State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'A')),
('Kaduna State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'B')),
('Kano State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'B')),
('Bauchi State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'C')),
('Borno State Command', 'State Command', (SELECT id FROM zones WHERE zone_code = 'C'));

-- Insert weapon types
INSERT INTO `weapon_types` (`type_name`) VALUES
('G3 Rifle'), ('AR70 Rifle'), ('AK-47 Rifle'), ('Galil Rifle'), ('LAR Rifle'),
('SMG'), ('FN Rifle'), ('AK 81-1 Rifle'), ('GF98-9 Rifle'), ('AK 103 Rifle'),
('X95 TAVOR'), ('Pistol Baretta'), ('Dicon Pistol'), ('Stone Pistol'), ('HP Pistol'),
('CZ Pistol'), ('CF Pistol'), ('Shotgun'), ('Machine Gun'), ('Grenade Launcher'),
('Sniper Rifle');

-- Insert weapon calibres
INSERT INTO `weapon_calibres` (`calibre_name`) VALUES
('7.62x51mm'), ('7.62x39mm'), ('5.56x45mm'), ('9x19mm'), ('12 Gauge'),
('.45 ACP'), ('5.7x28mm'), ('.22 LR'), ('7.62x54mmR'), ('.50 BMG');

-- Insert ammunition types
INSERT INTO `ammunition_types` (`ammo_type`) VALUES
('Live'), ('Blank'), ('Tracer'), ('Armor Piercing'), ('Training'),
('Rubber Bullet'), ('Bean Bag'), ('Pepper Ball'), ('Paint Marker');

-- Insert ammunition calibres
INSERT INTO `ammunition_calibres` (`calibre`, `rounds_per_unit`) VALUES
('7.62x51mm', 30), ('7.62x39mm', 30), ('5.56x45mm', 30), ('9x19mm', 30),
('12 Gauge', 25), ('.45 ACP', 30), ('5.7x28mm', 30), ('37mm', 5), ('40mm', 5);

-- Insert default admin user (password: Admin@123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `nis_number`, `rank`, `is_active`) VALUES
('admin', 'admin@immigration.gov.ng', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'NIS0001', 'ACI', 1);

-- Assign super admin role
INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_by`) VALUES (1, 1, 1);

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('org_name', 'Nigeria Immigration Service', 'organization'),
('org_location', 'Abuja, Nigeria', 'organization'),
('armory_name', 'Main Armory', 'organization'),
('session_timeout', '30', 'security'),
('password_min_length', '8', 'security'),
('max_login_attempts', '5', 'security'),
('lockout_time', '15', 'security'),
('require_approval', '1', 'transactions'),
('auto_calculate_balance', '1', 'transactions'),
('audit_cycle', 'quarterly', 'audit'),
('variance_tolerance', '5', 'audit'),
('weapon_min_stock_alert', '10', 'weapons'),
('ammo_min_stock_alert', '100', 'ammunition'),
('rounds_per_unit', '30', 'ammunition');