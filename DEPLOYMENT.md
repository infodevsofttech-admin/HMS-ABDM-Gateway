# ABDM Gateway - Deployment Guide

Complete step-by-step guide for deploying PHP/CI4 ABDM gateway to production Ubuntu server.

## Prerequisites

- Ubuntu 20.04 LTS or newer
- Apache 2.4+
- PHP 8.3+ with extensions: curl, json, mbstring, mysql, openssl
- MySQL 8.0+
- Composer
- Git
- Domain: `abdm-bridge.e-atria.in` with A record pointing to server

## Installation Steps

### 1. Install System Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP, MySQL
sudo apt install -y apache2 php8.3 php8.3-cli php8.3-curl php8.3-json \
    php8.3-mbstring php8.3-mysql php8.3-openssl php8.3-xml php8.3-zip \
    mysql-server mysql-client git curl

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Verify installation
php --version
composer --version
mysql --version
```

### 2. Create Application Directory

```bash
# Create project directory
sudo mkdir -p /var/www/abdm-bridge-gateway
cd /var/www/abdm-bridge-gateway

# Set proper permissions
sudo chown -R $USER:$USER /var/www/abdm-bridge-gateway
chmod -R 755 /var/www/abdm-bridge-gateway
```

### 3. Clone Repository

```bash
# Clone from GitHub (adjust URL for your repo)
git clone https://github.com/yourusername/abdm-bridge-gateway.git .

# Or copy files via SFTP/SCP if git not used
# scp -r gateway-php-ci4/* user@server:/var/www/abdm-bridge-gateway/
```

### 4. Install Dependencies

```bash
cd /var/www/abdm-bridge-gateway

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Verify installation
ls -la vendor/
```

### 5. Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit .env with production values
nano .env
```

**Key Settings for .env:**

```env
CI_ENVIRONMENT = production

# Database
database.default.hostname = localhost
database.default.database = abdm_gateway_db
database.default.username = abdm_gateway_user
database.default.password = YOUR_SECURE_PASSWORD

# Gateway
GATEWAY_BEARER_TOKEN = YOUR_HMS_BEARER_TOKEN
GATEWAY_SOURCE_CODE = SBXID_033661

# ABDM M3
ABDM_TOKEN = YOUR_ABDM_M3_TOKEN

# Logging
LOG_LEVEL = info
LOG_DATABASE = true
```

### 6. Create Database & User

```bash
# Login to MySQL
sudo mysql

# Create database
CREATE DATABASE abdm_gateway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'abdm_gateway_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';

# Grant privileges
GRANT ALL PRIVILEGES ON abdm_gateway_db.* TO 'abdm_gateway_user'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;
```

### 7. Generate Encryption Key

```bash
cd /var/www/abdm-bridge-gateway
php spark key:generate
```

### 8. Run Database Migrations

```bash
php spark migrate
```

### 9. Set Permissions

```bash
cd /var/www/abdm-bridge-gateway

# Set proper ownership
sudo chown -R www-data:www-data /var/www/abdm-bridge-gateway/writable
sudo chown -R www-data:www-data /var/www/abdm-bridge-gateway/public

# Set permissions
sudo chmod -R 755 /var/www/abdm-bridge-gateway/writable
sudo chmod -R 755 /var/www/abdm-bridge-gateway/public
```

### 10. Enable Apache Modules

```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Verify
sudo apache2ctl -M | grep rewrite
```

### 11. Configure Apache VirtualHost

Create `/etc/apache2/sites-available/abdm-bridge.conf`:

```bash
sudo nano /etc/apache2/sites-available/abdm-bridge.conf
```

Add configuration:

```apache
# HTTP VirtualHost - Redirect to HTTPS
<VirtualHost *:80>
    ServerName abdm-bridge.e-atria.in
    ServerAlias www.abdm-bridge.e-atria.in
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

# HTTPS VirtualHost
<VirtualHost *:443>
    ServerName abdm-bridge.e-atria.in
    ServerAlias www.abdm-bridge.e-atria.in
    
    DocumentRoot /var/www/abdm-bridge-gateway/public
    
    # SSL Configuration (will be added by certbot)
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/chain.pem
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    
    # CORS Headers
    Header always set Access-Control-Allow-Origin "https://e-atria.in"
    Header always set Access-Control-Allow-Methods "GET,POST,OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type,Authorization"
    Header always set Access-Control-Max-Age "3600"
    
    # CodeIgniter 4 Configuration
    <Directory /var/www/abdm-bridge-gateway/public>
        Options +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
        
        # Enable URL rewriting
        RewriteEngine On
        RewriteBase /
        
        # Redirect to index.php
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
    </Directory>
    
    # Prevent access to sensitive files
    <FilesMatch "\.env|\.git|composer\.lock">
        Require all denied
    </FilesMatch>
    
    # Allow public directory
    <Directory /var/www/abdm-bridge-gateway/public>
        Require all granted
    </Directory>
    
    # Deny access to writable directory
    <Directory /var/www/abdm-bridge-gateway/writable>
        Require all denied
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/abdm-bridge-error.log
    CustomLog ${APACHE_LOG_DIR}/abdm-bridge-access.log combined
    LogLevel warn
</VirtualHost>
```

### 12. Enable VirtualHost

```bash
# Enable the site
sudo a2ensite abdm-bridge.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test Apache configuration
sudo apache2ctl configtest
# Should show: Syntax OK

# Restart Apache
sudo systemctl restart apache2
```

### 13. Install SSL Certificate

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain certificate
sudo certbot certonly --apache \
    -d abdm-bridge.e-atria.in \
    -d www.abdm-bridge.e-atria.in \
    -m your-email@e-atria.in \
    --agree-tos --non-interactive

# Verify certificate
sudo certbot certificates

# Setup auto-renewal
sudo certbot renew --dry-run
```

### 14. Configure Firewall

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Verify
sudo ufw status
```

## Testing

### 1. Test API Endpoints

```bash
# Health check (no auth required)
curl https://abdm-bridge.e-atria.in/api/v3/health

# With authentication (requires GATEWAY_BEARER_TOKEN)
curl -H "Authorization: Bearer YOUR_TOKEN" \
    https://abdm-bridge.e-atria.in/api/v3/gateway/status
```

### 2. Check Request Logs

```bash
# View recent requests
mysql -u abdm_gateway_user -p -e \
    "SELECT * FROM abdm_gateway_db.abdm_request_logs ORDER BY created_at DESC LIMIT 10;"

# View error requests
mysql -u abdm_gateway_user -p -e \
    "SELECT * FROM abdm_gateway_db.abdm_request_logs WHERE status_code >= 400;"
```

### 3. Verify SSL

```bash
# Test SSL certificate
sudo certbot renew --dry-run

# Check certificate expiry
echo | openssl s_client -servername abdm-bridge.e-atria.in \
    -connect abdm-bridge.e-atria.in:443 2>/dev/null | openssl x509 -noout -dates
```

## Monitoring & Maintenance

### Log Rotation

Create `/etc/logrotate.d/abdm-bridge`:

```
/var/www/abdm-bridge-gateway/writable/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}

/var/log/apache2/abdm-bridge*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
```

### Database Backup Script

Create `/opt/backup_gateway_db.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/abdm-gateway"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_USER="abdm_gateway_user"
DB_NAME="abdm_gateway_db"
DB_PASSWORD="YOUR_PASSWORD"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > \
    $BACKUP_DIR/abdm_gateway_db_$TIMESTAMP.sql

# Compress backup
gzip $BACKUP_DIR/abdm_gateway_db_$TIMESTAMP.sql

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/abdm_gateway_db_$TIMESTAMP.sql.gz"
```

Make executable and schedule in crontab:

```bash
sudo chmod +x /opt/backup_gateway_db.sh
sudo crontab -e

# Add: 0 2 * * * /opt/backup_gateway_db.sh
```

### Monitoring Script

Create monitoring dashboard with health checks:

```bash
#!/bin/bash

echo "=== ABDM Gateway Health Check ==="
echo "Time: $(date)"
echo ""

# Check Apache
echo "Apache Status:"
sudo systemctl is-active apache2 && echo "✓ Running" || echo "✗ Stopped"

# Check MySQL
echo ""
echo "MySQL Status:"
sudo systemctl is-active mysql && echo "✓ Running" || echo "✗ Stopped"

# Check API
echo ""
echo "API Health:"
curl -s https://abdm-bridge.e-atria.in/api/v3/health | jq . || echo "✗ Unreachable"

# Check SSL
echo ""
echo "SSL Certificate:"
echo | openssl s_client -servername abdm-bridge.e-atria.in \
    -connect abdm-bridge.e-atria.in:443 2>/dev/null | openssl x509 -noout -dates

# Check Disk Space
echo ""
echo "Disk Space:"
df -h /var/www/abdm-bridge-gateway

# Check Recent Errors
echo ""
echo "Recent Apache Errors:"
tail -5 /var/log/apache2/abdm-bridge-error.log
```

## Troubleshooting

### 404 Not Found

```bash
# Check Apache rewrite module
sudo apache2ctl -M | grep rewrite

# Check .htaccess
ls -la /var/www/abdm-bridge-gateway/public/.htaccess

# Check Apache error log
tail -20 /var/log/apache2/abdm-bridge-error.log
```

### Database Connection Error

```bash
# Test MySQL connection
mysql -u abdm_gateway_user -p -h localhost -e "SELECT 1;"

# Check PHP MySQL extension
php -m | grep mysql

# Check .env database settings
cat /var/www/abdm-bridge-gateway/.env | grep database
```

### Permission Denied

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/abdm-bridge-gateway
sudo chmod -R 755 /var/www/abdm-bridge-gateway/writable

# Check file ownership
ls -la /var/www/abdm-bridge-gateway/writable/
```

### HTTPS Not Working

```bash
# Check SSL certificate
sudo certbot certificates

# Renew certificate
sudo certbot renew --force-renewal

# Check Apache SSL config
sudo apache2ctl configtest
```

## Post-Deployment Checklist

- [ ] DNS A record pointing to server IP
- [ ] SSL certificate installed and auto-renewal configured
- [ ] Apache VirtualHost configured and enabled
- [ ] Database created and migrations run
- [ ] .env file configured with production values
- [ ] File permissions set correctly (755 for writable/)
- [ ] API endpoints tested and responding
- [ ] Request logs visible in database
- [ ] Firewall rules configured (80, 443, SSH)
- [ ] Log rotation configured
- [ ] Database backup script scheduled
- [ ] Monitoring dashboard setup
- [ ] Team notified of deployment

## Performance Optimization

### Enable Caching Headers

Add to Apache VirtualHost:

```apache
<Files ~ "\.(jpg|jpeg|png|gif|js|css|ico)$">
    Header set Cache-Control "max-age=31536000, public"
</Files>
```

### Enable GZIP Compression

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### Optimize PHP

Edit `/etc/php/8.3/apache2/php.ini`:

```ini
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 100M
opcache.enable = 1
opcache.memory_consumption = 128
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

## Support & Issues

- For deployment issues: email dev@e-atria.in
- Check logs: `/var/log/apache2/abdm-bridge-*.log`
- Database issues: Check writable/logs/database.log
- View request logs: MySQL `abdm_request_logs` table
