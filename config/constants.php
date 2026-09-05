<?php
/**
 * System Constants
 */

// Asset Categories
define('ASSET_CATEGORIES', [
    'land' => 'Land Assets',
    'building' => 'Building Assets',
    'rented' => 'Rented Properties',
    'project' => 'Ongoing Projects',
    'movable' => 'Movable Assets',
    'ict' => 'ICT Assets',
    'vehicle' => 'Vehicles',
    'aircraft' => 'Aircraft',
    'marine' => 'Marine',
    'motorcycle' => 'Motorcycles',
    'weapon' => 'Weapons',
    'ammunition' => 'Ammunition'
]);

// User Roles
define('USER_ROLES', [
    'Super Admin Officer' => 'Overall System Administrator',
    'HQ Sectional Supervisor' => 'Sectional Heads at Headquarters',
    'HQ Vetting Officer' => 'Data Vetting Officers at HQ',
    'Command Approval Officer' => 'Heads of various commands',
    'Command Data Entry Officer' => 'Officers at command/unit level'
]);

// Requisition Status
define('REQUISITION_STATUS', [
    'Draft' => 'Draft',
    'Pending' => 'Pending Approval',
    'Approved' => 'Approved',
    'Rejected' => 'Rejected',
    'Issued' => 'Issued',
    'Partially Issued' => 'Partially Issued',
    'Completed' => 'Completed'
]);

// Requisition Priority
define('REQUISITION_PRIORITY', [
    'Low' => 'Low',
    'Medium' => 'Medium',
    'High' => 'High',
    'Urgent' => 'Urgent'
]);

// Audit Status
define('AUDIT_STATUS', [
    'Draft' => 'Draft',
    'Submitted' => 'Submitted',
    'Reviewed' => 'Reviewed',
    'Approved' => 'Approved'
]);

// Weapon Condition
define('WEAPON_CONDITION', [
    'Serviceable' => 'Serviceable',
    'Unserviceable' => 'Unserviceable',
    'Under Repair' => 'Under Repair'
]);

// Ammunition Condition
define('AMMUNITION_CONDITION', [
    'Serviceable' => 'Serviceable',
    'Unserviceable' => 'Unserviceable',
    'Condemned' => 'Condemned'
]);

// Vehicle Status
define('VEHICLE_STATUS', [
    'Active' => 'Active',
    'In Repair' => 'In Repair',
    'Grounded' => 'Grounded',
    'Awaiting Disposal' => 'Awaiting Disposal'
]);

// Project Status
define('PROJECT_STATUS', [
    'Planning' => 'Planning',
    'In Progress' => 'In Progress',
    'On Hold' => 'On Hold',
    'Completed' => 'Completed',
    'Cancelled' => 'Cancelled'
]);

// ICT Asset Categories
define('ICT_CATEGORIES', [
    'Hardware' => 'Hardware',
    'Software' => 'Software',
    'Network' => 'Network',
    'Server' => 'Server',
    'Peripheral' => 'Peripheral'
]);

// Ownership Types
define('OWNERSHIP_TYPES', [
    'FGN' => 'FGN',
    'Donor' => 'Donor',
    'Leased' => 'Leased',
    'Private' => 'Private'
]);

// Funding Sources
define('FUNDING_SOURCES', [
    'Capital Appropriation' => 'Capital Appropriation',
    'Special Intervention' => 'Special Intervention',
    'Donor' => 'Donor',
    'IGR' => 'IGR'
]);