# ABDM Bridge Gateway - PHP/CI4 Implementation

A native PHP/CodeIgniter 4 implementation of the ABDM (Ayushman Bharat Digital Mission) bridge gateway for sandboxed API integration. Lightweight, efficient, and fully integrated with existing Apache/MySQL/PHP infrastructure.

## Features

- ✅ **6 ABDM API Endpoints**: Health, ABHA Validate, Consent Request, Bundle Push, SNOMED Search, Gateway Status
- ✅ **MySQL Request Logging**: All API requests logged with response times and status codes
- ✅ **Audit Trail**: Track consent operations, patient ABHA, HI types
- ✅ **Bundle Tracking**: Monitor FHIR bundle push operations with retry logic
- ✅ **Bearer Token Validation**: Secure request authentication
- ✅ **Request ID Tracking**: Unique identifiers for all API calls
- ✅ **Native PHP/CI4**: No Docker, lightweight (~50MB vs 512MB+ Docker)
- ✅ **Production Ready**: Security headers, rate limiting, error handling

## Architecture

```
gateway-php-ci4/
├── app/
│   ├── Config/
│   │   ├── AbdmGateway.php       # ABDM configuration
│   │   ├── Routes.php             # API routing
│   │   └── ...                    # Standard CI4 configs
│   ├── Controllers/
│   │   └── AbdmGateway.php       # 6 ABDM endpoints
│   ├── Models/
│   │   ├── AbdmRequestLog.php    # Request logging model
│   │   ├── AbdmAuditTrail.php    # Consent audit trail
│   │   └── AbdmBundle.php        # Bundle tracking
│   ├── Database/
│   │   └── Migrations/           # Database schema
│   └── ...                        # Standard CI4 structure
├── composer.json                  # PHP dependencies
├── .env.example                   # Configuration template
└── public/
    └── index.php                 # Application entry point
```

## Installation

### Prerequisites

- PHP 8.3+
- MySQL 8.0+
- Apache 2.4 with mod_rewrite
- Composer

### Quick Setup (8 Steps)

```bash
# 1. Clone repository
git clone https://github.com/yourusername/abdm-bridge-gateway.git
cd abdm-bridge-gateway

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Configure .env
# Edit .env with your database credentials and ABDM tokens:
# - database.default.hostname, database, username, password
# - GATEWAY_BEARER_TOKEN (HMS authentication token)
# - ABDM_TOKEN (your ABDM sandbox token)

# 5. Generate encryption key
php spark key:generate

# 6. Create database
mysql -u root -p -e "CREATE DATABASE abdm_gateway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Run migrations
php spark migrate

# 8. Set permissions
chmod -R 755 writable/
chown -R www-data:www-data writable/
```

## Git Workflow (Local and Production)

Use a simple branch-based workflow and keep secrets out of Git.

### Repository Safety

- `.env` is ignored by `.gitignore`; do not commit runtime secrets.
- Use `.env.example` for placeholders only.
- Keep production-only values in server environment or server `.env`.

### Local Update Flow

```bash
# 1. Create feature branch
git checkout -b fix/m1-abha-validation

# 2. Install/update dependencies if required
composer install

# 3. Lint quick checks
php -l app/Controllers/Admin.php
php -l app/Config/Routes.php

# 4. Commit and push
git add .
git commit -m "Fix M1 ABHA validation token/log compatibility"
git push -u origin fix/m1-abha-validation
```

### Production Update Flow

```bash
# On server
cd /var/www/html/abdm-bridge-gateway
git fetch --all
git checkout main
git pull --ff-only origin main

# Run migrations if schema changed
php spark migrate --all

# Quick syntax checks
php -l app/Controllers/Admin.php
php -l app/Config/Routes.php
```

After deploy, validate from UI:

- `/admin/m1/abha-validate` submission works without manual bearer token.
- No generic error view crash.
- `abdm_abha_profiles` and `abdm_test_submission_logs` receive expected records.

## Database Schema

### abdm_request_logs
Logs all API requests for monitoring and debugging.

```sql
CREATE TABLE abdm_request_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_id VARCHAR(100) UNIQUE NOT NULL,
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    status_code INT NOT NULL,
    response_time_ms INT DEFAULT 0,
    ip_address VARCHAR(50),
    authorization_status VARCHAR(20) NOT NULL,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (endpoint),
    KEY (status_code),
    KEY (created_at)
);
```

### abdm_audit_trail
Tracks consent operations and patient data access.

```sql
CREATE TABLE abdm_audit_trail (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_id VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    patient_abha VARCHAR(50),
    consent_id VARCHAR(100),
    hi_types JSON,
    action_status VARCHAR(20) NOT NULL,
    details JSON,
    performed_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (patient_abha),
    KEY (consent_id),
    KEY (created_at)
);
```

### abdm_bundles
Tracks FHIR bundle push operations with retry counters.

```sql
CREATE TABLE abdm_bundles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    bundle_id VARCHAR(100) UNIQUE NOT NULL,
    consent_id VARCHAR(100),
    hi_type VARCHAR(100) NOT NULL,
    bundle_hash VARCHAR(255),
    push_status VARCHAR(20) DEFAULT 'pending',
    push_timestamp DATETIME,
    response_status INT,
    response_body JSON,
    retry_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (consent_id),
    KEY (push_status),
    KEY (created_at)
);
```

## API Endpoints

### 1. Health Check
```
GET /api/v3/health
Response: { "status": "ok", "timestamp": "2024-05-12T10:30:00Z" }
```

### 2. ABHA Validation
```
POST /api/v3/abha/validate
Content-Type: application/json

{
    "abha": "00-0000-0000-0000",
    "name": "Patient Name"
}
```

### 3. Consent Request
```
POST /api/v3/consent/request
Authorization: Bearer <GATEWAY_TOKEN>
Content-Type: application/json

{
    "patient_abha": "00-0000-0000-0000",
    "hi_types": ["OPConsultation", "LabResult"],
    "purpose": "TREATMENT"
}
```

### 4. Bundle Push
```
POST /api/v3/bundle/push
Authorization: Bearer <GATEWAY_TOKEN>
Content-Type: application/json

{
    "consent_id": "consent-id-xxx",
    "bundle": { /* FHIR Bundle */ }
}
```

### 5. SNOMED Search
```
GET /api/v3/snomed/search?term=diabetes
Authorization: Bearer <GATEWAY_TOKEN>
```

### 6. Gateway Status
```
GET /api/v3/gateway/status
Authorization: Bearer <GATEWAY_TOKEN>

Response:
{
    "gateway": "operational",
    "database": "connected",
    "abdm_m3": "reachable",
    "snomed_service": "reachable",
    "timestamp": "2024-05-12T10:30:00Z"
}
```

## Configuration

### .env Settings

```env
# Database
database.default.hostname = localhost
database.default.database = abdm_gateway_db
database.default.username = root
database.default.password = password

# Gateway
GATEWAY_BEARER_TOKEN = your-hms-bearer-token
GATEWAY_SOURCE_CODE = SBXID_033661

# ABDM M3 API
ABDM_M3_URL = https://dev.abdm.gov.in/api/v3
ABDM_TOKEN = your-abdm-token
ABDM_M3_TIMEOUT = 30

# SNOMED Service
SNOMED_SERVICE_URL = https://csnotk.e-atria.in/csnoserv
SNOMED_SERVICE_TIMEOUT = 10

# Logging
LOG_DATABASE = true
LOG_REQUEST_BODY = true
LOG_RESPONSE_BODY = false
```

## Apache VirtualHost Configuration

```apache
<VirtualHost *:80>
    ServerName abdm-bridge.e-atria.in
    ServerAlias www.abdm-bridge.e-atria.in
    
    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName abdm-bridge.e-atria.in
    ServerAlias www.abdm-bridge.e-atria.in
    
    DocumentRoot /var/www/abdm-bridge-gateway/public
    
    # Enable HTTPS
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/abdm-bridge.e-atria.in/privkey.pem
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    # CI4 URL Rewriting
    <Directory /var/www/abdm-bridge-gateway/public>
        Options +FollowSymLinks
        RewriteEngine On
        
        # Redirect to public folder
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
        
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/abdm-bridge-error.log
    CustomLog ${APACHE_LOG_DIR}/abdm-bridge-access.log combined
</VirtualHost>
```

## SSL Setup with Let's Encrypt

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtain certificate
sudo certbot certonly --apache -d abdm-bridge.e-atria.in -d www.abdm-bridge.e-atria.in

# Auto-renewal (check with)
sudo certbot renew --dry-run
```

## Monitoring

### View Request Logs
```bash
# Recent requests
SELECT * FROM abdm_request_logs ORDER BY created_at DESC LIMIT 100;

# Failed requests
SELECT * FROM abdm_request_logs WHERE status_code >= 400;

# Slow requests (>1s)
SELECT * FROM abdm_request_logs WHERE response_time_ms > 1000;
```

### View Audit Trail
```bash
# Consent operations
SELECT * FROM abdm_audit_trail WHERE action = 'consent_request' ORDER BY created_at DESC;

# Failed actions
SELECT * FROM abdm_audit_trail WHERE action_status = 'failed';
```

### View Bundle Status
```bash
# Pending bundles
SELECT * FROM abdm_bundles WHERE push_status = 'pending';

# Failed bundles
SELECT * FROM abdm_bundles WHERE push_status = 'failed' ORDER BY retry_count DESC;
```

## Performance Metrics

- **Request Processing**: 50-200ms (typical CURL roundtrip)
- **Database Logging**: <5ms per request
- **Memory Usage**: ~50MB (vs 512MB+ Docker)
- **Disk Usage**: ~20MB (vs 500MB+ Docker)
- **Concurrent Requests**: 100+ (Apache workers)
- **Request Rate**: 1000+ req/min on moderate hardware

## Troubleshooting

### Database Connection Error
```
Error: Unknown database 'abdm_gateway_db'
Solution: Check .env database settings and run: php spark migrate
```

### CORS Errors
```
XMLHttpRequest blocked by CORS policy
Solution: Check CORS_ALLOW_ORIGIN in .env and ensure HMS has proper headers
```

### ABDM M3 Timeout
```
Error: cURL operation timed out
Solution: Check ABDM_M3_TIMEOUT in .env (increase to 60 for slow networks)
```

### File Upload Permission
```
Error: Could not move uploaded file
Solution: chmod -R 755 writable/ && chown -R www-data:www-data writable/
```

## Development

### Run Local Server
```bash
php spark serve
# Visit http://localhost:8080
```

### Run Tests
```bash
vendor/bin/phpunit
```

### Database Migrations
```bash
# Create migration
php spark make:migration CreateAbdmTables

# Run migrations
php spark migrate

# Rollback
php spark migrate:rollback
```

## Deployment Checklist

- [ ] Copy .env.example to .env
- [ ] Update database credentials
- [ ] Update ABDM tokens (M3_TOKEN, BEARER_TOKEN)
- [ ] Run composer install
- [ ] Run php spark key:generate
- [ ] Create database and run migrations
- [ ] Set permissions on writable/ directory
- [ ] Configure Apache VirtualHost
- [ ] Setup SSL with Let's Encrypt
- [ ] Test all 6 API endpoints
- [ ] Monitor request logs for errors
- [ ] Setup log rotation (writable/logs/)
- [ ] Configure monitoring/alerting

## License

Apache License 2.0 - See LICENSE file

## Support

For issues, feature requests, or questions:
- GitHub Issues: https://github.com/yourusername/abdm-bridge-gateway/issues
- Email: dev@e-atria.in
