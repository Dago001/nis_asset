<?php
/**
 * Route Definitions
 */

// Auth routes
Router::get('/login', 'AuthController@loginForm');
Router::post('/login', 'AuthController@login');
Router::get('/auth/login', 'AuthController@loginForm');
Router::post('/auth/login', 'AuthController@login');
Router::get('/auth/logout', 'AuthController@logout');
Router::get('/auth/change-password', 'AuthController@changePasswordForm');
Router::post('/auth/change-password', 'AuthController@changePassword');
Router::get('/auth/forgot-password', 'AuthController@forgotPasswordForm');
Router::post('/auth/forgot-password', 'AuthController@forgotPassword');
Router::get('/auth/reset-password', 'AuthController@resetPasswordForm');
Router::post('/auth/reset-password', 'AuthController@resetPassword');
Router::get('/auth/unauthorized', 'AuthController@unauthorized');
Router::get('/auth/refresh-captcha', 'AuthController@refreshCaptcha');
Router::get('/auth/two-factor', 'AuthController@twoFactorForm');
Router::post('/auth/two-factor', 'AuthController@twoFactorVerify');
Router::get('/auth/geo-check', 'AuthController@geoCheck');
Router::post('/auth/geo-verify', 'AuthController@geoVerify');
Router::post('/auth/disable-2fa', 'AuthController@disable2FA');
Router::post('/auth/enable-2fa', 'AuthController@enable2FA');

// Active sessions (Super Admin)
Router::get('/sessions', 'SessionsController@index');
Router::get('/sessions/terminate/{id}', 'SessionsController@terminate');

// Roles & Permissions management
Router::get('/roles', 'RoleController@index');
Router::get('/roles/edit/{id}', 'RoleController@edit');
Router::post('/roles/update/{id}', 'RoleController@update');

// Dashboard
Router::get('/', 'DashboardController@index');
Router::get('/dashboard', 'DashboardController@index');

// User management
Router::get('/users', 'UserController@index');
Router::get('/users/create', 'UserController@create');
Router::post('/users/store', 'UserController@store');
Router::get('/users/show/{id}', 'UserController@show');
Router::get('/users/edit/{id}', 'UserController@edit');  
Router::post('/users/update/{id}', 'UserController@update');
Router::get('/users/delete/{id}', 'UserController@delete');
Router::get('/users/toggle-status/{id}', 'UserController@toggleStatus');
Router::get('/users/reset-password/{id}', 'UserController@resetPassword');
Router::get('/users/reset-2fa/{id}', 'UserController@reset2fa');
Router::get('/users/profile', 'UserController@profile');
Router::post('/users/update-profile', 'UserController@updateProfile');
Router::get('/users/export', 'UserController@export');
Router::get('/users/api-get-formations-by-zone', 'UserController@apiGetFormationsByZone');
Router::get('/users/check-username', 'UserController@checkUsername');

// Land assets
Router::get('/land', 'LandController@index');
Router::get('/land/create', 'LandController@create');
Router::post('/land/store', 'LandController@store');
Router::get('/land/show/{id}', 'LandController@show');
Router::get('/land/edit/{id}', 'LandController@edit');
Router::post('/land/update/{id}', 'LandController@update');
Router::get('/land/delete/{id}', 'LandController@delete');
Router::get('/land/export', 'LandController@export');

// Building assets
Router::get('/buildings', 'BuildingController@index');
Router::get('/buildings/create', 'BuildingController@create');
Router::post('/buildings/store', 'BuildingController@store');
Router::get('/buildings/show/{id}', 'BuildingController@show');
Router::get('/buildings/edit/{id}', 'BuildingController@edit');
Router::post('/buildings/update/{id}', 'BuildingController@update');
Router::get('/buildings/delete/{id}', 'BuildingController@delete');
Router::get('/buildings/export', 'BuildingController@export');

// Rented properties
Router::get('/rented', 'RentedController@index');
Router::get('/rented/create', 'RentedController@create');
Router::post('/rented/store', 'RentedController@store');
Router::get('/rented/show/{id}', 'RentedController@show');
Router::get('/rented/edit/{id}', 'RentedController@edit');
Router::post('/rented/update/{id}', 'RentedController@update');
Router::get('/rented/delete/{id}', 'RentedController@delete');
Router::get('/rented/export', 'RentedController@export');

// Ongoing projects
Router::get('/projects', 'ProjectController@index');
Router::get('/projects/create', 'ProjectController@create');
Router::post('/projects/store', 'ProjectController@store');
Router::get('/projects/show/{id}', 'ProjectController@show');
Router::get('/projects/edit/{id}', 'ProjectController@edit');
Router::post('/projects/update/{id}', 'ProjectController@update');
Router::get('/projects/delete/{id}', 'ProjectController@delete');
Router::get('/projects/export', 'ProjectController@export');

// Movable assets
Router::get('/movable', 'MovableController@index');
Router::get('/movable/create', 'MovableController@create');
Router::post('/movable/store', 'MovableController@store');
Router::get('/movable/show/{id}', 'MovableController@show');
Router::get('/movable/edit/{id}', 'MovableController@edit');
Router::post('/movable/update/{id}', 'MovableController@update');
Router::get('/movable/delete/{id}', 'MovableController@delete');
Router::get('/movable/export', 'MovableController@export');

// ICT assets
Router::get('/ict', 'IctController@index');
Router::get('/ict/create', 'IctController@create');
Router::post('/ict/store', 'IctController@store');
Router::get('/ict/show/{id}', 'IctController@show');
Router::get('/ict/edit/{id}', 'IctController@edit');
Router::post('/ict/update/{id}', 'IctController@update');
Router::get('/ict/delete/{id}', 'IctController@delete');
Router::get('/ict/export', 'IctController@export');

// Fleet management
Router::get('/fleet/dashboard', 'FleetController@dashboard');
Router::get('/fleet/vehicles', 'FleetController@vehicles');
Router::get('/fleet/vehicles/create', 'FleetController@createVehicle');
Router::post('/fleet/vehicles/store', 'FleetController@storeVehicle');
Router::get('/fleet/vehicles/show/{id}', 'FleetController@showVehicle');
Router::get('/fleet/vehicles/edit/{id}', 'FleetController@editVehicle');
Router::post('/fleet/vehicles/update/{id}', 'FleetController@updateVehicle');
Router::get('/fleet/vehicles/delete/{id}', 'FleetController@deleteVehicle');
Router::get('/fleet/aircraft', 'FleetController@aircraft');
Router::get('/fleet/aircraft/create', 'FleetController@createAircraft');
Router::post('/fleet/aircraft/store', 'FleetController@storeAircraft');
Router::get('/fleet/aircraft/show/{id}', 'FleetController@showAircraft');
Router::get('/fleet/aircraft/edit/{id}', 'FleetController@editAircraft');
Router::post('/fleet/aircraft/update/{id}', 'FleetController@updateAircraft');
Router::get('/fleet/aircraft/delete/{id}', 'FleetController@deleteAircraft');
Router::get('/fleet/marine', 'FleetController@marine');
Router::get('/fleet/marine/create', 'FleetController@createMarine');
Router::post('/fleet/marine/store', 'FleetController@storeMarine');
Router::get('/fleet/marine/show/{id}', 'FleetController@showMarine');
Router::get('/fleet/marine/edit/{id}', 'FleetController@editMarine');
Router::post('/fleet/marine/update/{id}', 'FleetController@updateMarine');
Router::get('/fleet/marine/delete/{id}', 'FleetController@deleteMarine');
Router::get('/fleet/motorcycles', 'FleetController@motorcycles');
Router::get('/fleet/motorcycles/create', 'FleetController@createMotorcycle');
Router::post('/fleet/motorcycles/store', 'FleetController@storeMotorcycle');
Router::get('/fleet/motorcycles/show/{id}', 'FleetController@showMotorcycle');
Router::get('/fleet/motorcycles/edit/{id}', 'FleetController@editMotorcycle');
Router::post('/fleet/motorcycles/update/{id}', 'FleetController@updateMotorcycle');
Router::get('/fleet/motorcycles/delete/{id}', 'FleetController@deleteMotorcycle');
Router::get('/fleet/export', 'FleetController@export');

// Weapons management
Router::get('/weapons', 'WeaponsController@index');
Router::get('/weapons/dashboard', 'WeaponsController@dashboard');
Router::get('/weapons/create', 'WeaponsController@create');
Router::post('/weapons/store', 'WeaponsController@store');
Router::get('/weapons/show/{id}', 'WeaponsController@show');
Router::get('/weapons/edit/{id}', 'WeaponsController@edit');
Router::post('/weapons/update/{id}', 'WeaponsController@update');
Router::get('/weapons/delete/{id}', 'WeaponsController@delete');
Router::get('/weapons/types', 'WeaponsController@types');
Router::post('/weapons/types/store', 'WeaponsController@storeType');
Router::get('/weapons/types/delete/{id}', 'WeaponsController@deleteType');
Router::get('/weapons/calibres', 'WeaponsController@calibres');
Router::post('/weapons/calibres/store', 'WeaponsController@storeCalibre');
Router::get('/weapons/calibres/delete/{id}', 'WeaponsController@deleteCalibre');
Router::get('/weapons/export', 'WeaponsController@export');

// Ammunition management
Router::get('/ammunition', 'AmmunitionController@index');
Router::get('/ammunition/dashboard', 'AmmunitionController@dashboard');
Router::get('/ammunition/create', 'AmmunitionController@create');
Router::post('/ammunition/store', 'AmmunitionController@store');
Router::get('/ammunition/show/{id}', 'AmmunitionController@show');
Router::get('/ammunition/edit/{id}', 'AmmunitionController@edit');
Router::post('/ammunition/update/{id}', 'AmmunitionController@update');
Router::get('/ammunition/delete/{id}', 'AmmunitionController@delete');
Router::get('/ammunition/types', 'AmmunitionController@types');
Router::post('/ammunition/types/store', 'AmmunitionController@storeType');
Router::get('/ammunition/types/delete/{id}', 'AmmunitionController@deleteType');
Router::get('/ammunition/calibres', 'AmmunitionController@calibres');
Router::post('/ammunition/calibres/store', 'AmmunitionController@storeCalibre');
Router::get('/ammunition/calibres/delete/{id}', 'AmmunitionController@deleteCalibre');
Router::get('/ammunition/export', 'AmmunitionController@export');

// Requisition routes (singular)
Router::get('/requisition', 'RequisitionController@index');
Router::get('/requisition/create', 'RequisitionController@create');
Router::post('/requisition/store', 'RequisitionController@store');
Router::get('/requisition/show/{id}', 'RequisitionController@show');
Router::get('/requisition/edit/{id}', 'RequisitionController@edit');
Router::post('/requisition/update/{id}', 'RequisitionController@update');
Router::get('/requisition/delete/{id}', 'RequisitionController@delete');
Router::post('/requisition/approve/{id}', 'RequisitionController@approve');
Router::post('/requisition/reject/{id}', 'RequisitionController@reject');
Router::get('/requisition/my', 'RequisitionController@my');
Router::get('/requisition/pending', 'RequisitionController@pending');
Router::get('/requisition/export', 'RequisitionController@export');

// Notifications
Router::get('/api/notifications/unread', 'NotificationController@apiGetUnread');
Router::get('/notifications/mark-read/{id}', 'NotificationController@markAsRead');
Router::post('/notifications/mark-all-read', 'NotificationController@markAllAsRead');


// Returns
Router::get('/returns', 'ReturnsController@index');
Router::get('/returns/create', 'ReturnsController@create');
Router::post('/returns/store', 'ReturnsController@store');
Router::get('/returns/show/{id}', 'ReturnsController@show');
Router::get('/returns/edit/{id}', 'ReturnsController@edit');
Router::post('/returns/update/{id}', 'ReturnsController@update');
Router::get('/returns/delete/{id}', 'ReturnsController@delete');
Router::get('/returns/process/{id}', 'ReturnsController@process');
Router::get('/returns/export', 'ReturnsController@export');

// Audit
Router::get('/audit/quarterly', 'AuditController@quarterly');
Router::get('/audit/quarterly/create', 'AuditController@createQuarterly');
Router::post('/audit/quarterly/store', 'AuditController@storeQuarterly');
Router::get('/audit/quarterly/show/{id}', 'AuditController@showQuarterly');
Router::get('/audit/quarterly/edit/{id}', 'AuditController@editQuarterly');
Router::post('/audit/quarterly/update/{id}', 'AuditController@updateQuarterly');
Router::get('/audit/quarterly/delete/{id}', 'AuditController@deleteQuarterly');
Router::get('/audit/quarterly/review/{id}', 'AuditController@review');
Router::get('/audit/quarterly/approve/{id}', 'AuditController@approve');
Router::get('/audit/history', 'AuditController@history');
Router::get('/audit/export', 'AuditController@exportHistory');

// Reports
Router::get('/reports', 'ReportController@index');
Router::get('/reports/summary', 'ReportController@summary');
Router::get('/reports/assets', 'ReportController@assets');
Router::get('/reports/weapons', 'ReportController@weapons');
Router::get('/reports/ammunition', 'ReportController@ammunition');
Router::get('/reports/fleet', 'ReportController@fleet');
Router::get('/reports/audit', 'ReportController@audit');
Router::post('/reports/save', 'ReportController@save');
Router::get('/reports/saved', 'ReportController@saved');
Router::get('/reports/load/{id}', 'ReportController@load');
Router::get('/reports/delete/{id}', 'ReportController@delete');


// Weapon Issue Routes
Router::get('/weapon_issue', 'WeaponIssueController@index');
Router::get('/weapon_issue/create', 'WeaponIssueController@create');
Router::post('/weapon_issue/store', 'WeaponIssueController@store');
Router::get('/weapon_issue/show/{id}', 'WeaponIssueController@show');
Router::get('/weapon_issue/return/{id}', 'WeaponIssueController@return');
Router::post('/weapon_issue/processReturn/{id}', 'WeaponIssueController@processReturn');
Router::get('/weapon_issue/history', 'WeaponIssueController@history');

// API Routes
Router::get('/api/get_issued_weapons', 'WeaponIssueController@apiGetIssuedWeapons');
Router::get('/api/get_issued_ammunition', 'WeaponIssueController@apiGetIssuedAmmunition');

// API routes
Router::get('/api/get_lgas', 'ApiController@getLgas');
Router::get('/api/get_commands', 'ApiController@getCommands');
Router::get('/api/get_land_assets', 'ApiController@getLandAssets');
Router::get('/api/get_land_map_locations', 'ApiController@getLandMapLocations');
Router::get('/api/get_weapons', 'ApiController@getWeapons');
Router::get('/api/get_ammunition', 'ApiController@getAmmunition');
Router::get('/api/get_requisitions', 'ApiController@getRequisitions');
Router::get('/api/get_returns_log', 'ApiController@getReturnsLog');
Router::get('/api/dashboard_stats', 'ApiController@dashboardStats');
Router::post('/api/update_inventory', 'ApiController@updateInventory');
Router::get('/api/validate_serial', 'ApiController@validateSerial');
Router::get('/api/get_audit_details', 'ApiController@getAuditDetails');

// Settings page (HTML)
Router::get('/settings', 'SettingsController@index');
Router::get('/settings/export', 'SettingsController@export');
Router::post('/settings/import', 'SettingsController@import');

    // Settings group save (non‑API) – used by the UI when saving a tab
    Router::post('/settings/save', 'SettingsController@saveGroup');

// Settings RESTful API — all under /api/v1/settings
Router::get('/api/v1/settings',            'SettingsController@apiIndex');   // GET  all settings
Router::get('/api/v1/settings/{key}',      'SettingsController@apiGet');     // GET  one setting
Router::post('/api/v1/settings',           'SettingsController@apiCreate');  // POST create setting
Router::put('/api/v1/settings/{key}',      'SettingsController@apiUpdate');  // PUT  update one
Router::post('/api/v1/settings/batch',     'SettingsController@apiBatch');   // POST bulk update
Router::delete('/api/v1/settings/{key}',   'SettingsController@apiDelete');  // DELETE one
Router::post('/api/v1/settings/test-smtp', 'SettingsController@apiTestSmtp');// POST test email

// 404 fallback
Router::set404(function() {
    header('HTTP/1.1 404 Not Found');
    require_once BASE_PATH . '/views/errors/404.php';
});