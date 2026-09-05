#!/bin/bash

# NIS Asset Management System Deployment Script
# Run with: bash deploy.sh

echo "========================================="
echo "NIS Asset Management System Deployment"
echo "========================================="

# Configuration
APP_NAME="nis-ams"
APP_DIR="/var/www/$APP_NAME"
DB_NAME="nis_ams"
DB_USER="nis_user"
DB_PASS=$(openssl rand -base64 32 | tr -d /=+ | cut -c -24)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting deployment...${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt-get update && apt-get upgrade -y

# Install required packages
echo -e "${YELLOW}Installing required packages...${NC}"
apt-get install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-curl \
    php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-intl \
    php8.1-bcmath php8.1-soap php8.1-ldap unzip git curl

# Enable Apache modules
echo -e "${YELLOW}Enabling Apache modules...${NC}"
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Create application directory
echo -e "${YELLOW}Creating application directory...${NC}"
mkdir -p $APP_DIR
cd $APP_DIR

# Clone repository (replace with your repo)
echo -e "${YELLOW}Cloning repository...${NC}"
git clone https://github.com/your-org/nis-ams.git .

# Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
chown -R www-data:www-data $APP_DIR
# Uploads/logs need to be writable by the web user but not world-readable
chmod -R 750 $APP_DIR/assets/uploads/
chmod -R 750 $APP_DIR/assets/backups/ 2>/dev/null || true
chmod -R 750 $APP_DIR/logs/
# .env holds credentials: owner read/write only
chmod 600 $APP_DIR/.env
chown www-data:www-data $APP_DIR/.env

# Install Composer
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev

# Create database
echo -e "${YELLOW}Creating database...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import schema
echo -e "${YELLOW}Importing database schema...${NC}"
SCHEMA_FILE="database/schema.sql"
[ -f "$SCHEMA_FILE" ] || SCHEMA_FILE="db/nis_ams.sql"
mysql $DB_NAME < "$SCHEMA_FILE"
[ -f database/seeders/initial_data.sql ] && mysql $DB_NAME < database/seeders/initial_data.sql
for m in database/migrations/*.sql; do [ -f "$m" ] && mysql $DB_NAME < "$m"; done

# Create .env file (chmod 600 immediately — it holds live credentials)
echo -e "${YELLOW}Creating environment file...${NC}"
ENCRYPTION_KEY=$(php -r "echo bin2hex(random_bytes(32));")
umask 077
cat > .env << EOF
# Application
APP_NAME="NIS Asset Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://CHANGE-ME
APP_VERSION=2.0.0

# Database
DB_HOST=localhost
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS
DB_CHARSET=utf8mb4

# Session
SESSION_NAME=NIS_AMS_SESSION
SESSION_LIFETIME=1800
SESSION_SECURE=true

# Security
CSRF_TOKEN_NAME=csrf_token
PASSWORD_MIN_LENGTH=8
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_TIME=900
RATE_LIMIT=60
TRUST_PROXY=false

# 32-byte hex key for encrypting sensitive settings at rest (e.g. smtp_password)
ENCRYPTION_KEY=$ENCRYPTION_KEY

# Upload
UPLOAD_MAX_SIZE=10485760
ALLOWED_FILE_TYPES=pdf,jpg,jpeg,png,doc,docx
EOF
chmod 600 .env

# Create Apache virtual host
echo -e "${YELLOW}Configuring Apache...${NC}"
cat > /etc/apache2/sites-available/$APP_NAME.conf << EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $APP_DIR
    
    <Directory $APP_DIR>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/$APP_NAME-error.log
    CustomLog \${APACHE_LOG_DIR}/$APP_NAME-access.log combined
</VirtualHost>
EOF

# Enable site
a2ensite $APP_NAME
systemctl restart apache2

# Set up cron jobs
echo -e "${YELLOW}Setting up cron jobs...${NC}"
echo "0 2 * * * php $APP_DIR/scripts/cleanup.php >> $APP_DIR/logs/cron.log 2>&1" | crontab -

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Application URL: http://localhost"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASS"
echo ""
echo "Admin bootstrap:"
echo "Run 'php scripts/create_admin.php' on the server to create the first"
echo "Super Admin account with a password you choose (it is never printed or"
echo "stored in this script)."
echo ""
echo "Next steps:"
echo "1. Update .env with your domain and database credentials (chmod 600)"
echo "2. Configure a valid SSL certificate and force HTTPS"
echo "3. Run 'php scripts/migrate.php' to apply schema updates"
echo "4. Run 'php scripts/create_admin.php' to create the first admin"
echo "5. Test all modules"
echo ""
echo -e "${GREEN}Done!${NC}"