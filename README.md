# NIS Asset Management System

## Overview
The Nigeria Immigration Service Asset Management System (NIS-AMS) is a comprehensive web-based application for managing all assets of the Nigeria Immigration Service, including land, buildings, vehicles, weapons, ammunition, and more.

## Features
- **Complete Asset Management**: Land, Buildings, Rented Properties, Projects, Movable Assets, ICT Assets, Fleet (Vehicles, Aircraft, Marine, Motorcycles), Weapons, Ammunition
- **User Management**: 5-role hierarchy with granular permissions
- **Requisition Workflow**: Submit, approve, and track requisitions
- **Returns Management**: Process returns and track missing items
- **Quarterly Audits**: Conduct audits with variance calculation
- **Real-time Dashboard**: Live statistics and alerts
- **Reports**: Comprehensive reporting with export options
- **Document Management**: Upload and manage documents for all assets

## Technology Stack
- **Backend**: PHP 7.4/8.0 with MVC architecture
- **Database**: MySQL 5.7/8.0 with PDO
- **Frontend**: HTML5, CSS3, JavaScript, Font Awesome
- **Security**: CSRF protection, XSS prevention, SQL injection prevention
- **Additional**: Composer, PHPMailer, Monolog

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite
- Composer

### Quick Install
```bash
# Clone repository
git clone https://github.com/nis/asset-management.git /var/www/nis-ams

# Run deployment script
cd /var/www/nis-ams
chmod +x deploy.sh
sudo ./deploy.sh
```

### Manual install / upgrade
```bash
cp .env.example .env          # then edit; chmod 600 .env
composer install --no-dev     # installs PHPMailer, TCPDF, PhpSpreadsheet, Monolog
php scripts/migrate.php        # apply database/migrations/*.sql
php scripts/create_admin.php   # create the first Super Admin (prompts for password)
```

### Security notes (read before deploying)
- **`.env` is git-ignored** and must never be committed. It is the only place
  credentials belong. `chmod 600 .env` and own it by the web user.
- **`composer install` is required.** `vendor/` is git-ignored; without it, PDF
  export falls back to HTML and SMTP email is disabled. Commit `composer.lock`
  so deployments are reproducible and auditable.
- **HTTPS only in production.** Set `APP_ENV=production` and `SESSION_SECURE=true`;
  the app redirects HTTP→HTTPS and sets HSTS. Uncomment the redirect block in
  `.htaccess` if your host does not already force TLS.
- **Uploaded files** are served only through `serve_document.php`, which enforces
  authentication and per-record authorisation. Keep `assets/uploads/.htaccess`
  and `assets/backups/.htaccess` in place. Backups are never web-accessible.
- **Maintenance scripts** (`scripts/*.php`) run from cron / CLI only and refuse
  to execute over HTTP.
- Two-factor authentication is mandatory. On first sign-in each user enrols an
  authenticator app; codes are single-use (replay-protected).
- If this repo's history ever contained a real `.env`, log file or DB dump,
  rotate every affected credential and scrub history with `git filter-repo`.