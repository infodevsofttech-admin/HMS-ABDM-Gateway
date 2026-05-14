# HMS Access Management Interface - Implementation Guide

## Overview
This HMS (Hospital Management System) Access Management interface allows you to:
- Add and manage Hospital records with HFR IDs
- Configure HMS API credentials per hospital
- Support multiple authentication types (API Key, Basic Auth)
- Test HMS connectivity before deployment
- Track HMS connection status and verification

## Features

### 1. Hospital Management
- **Add Hospital**: Create new hospital records with HFR ID and contact info
- **Gateway Mode**: Switch between TEST and LIVE modes per hospital
- **Status Control**: Activate/Inactive hospitals
- **Contact Info**: Store contact name, email, and phone

### 2. HMS Credential Management
- **Multiple Auth Types**:
  - API Key Authentication (Bearer token)
  - Basic Authentication (Username/Password)
- **Per-Hospital Credentials**: Each hospital can have one or more HMS integrations
- **Credential Encryption**: Sensitive data is encrypted before storage
- **Status Tracking**: Active/Inactive status and verification flags

### 3. Connection Testing
- **Test Button**: Verify HMS connectivity from the gateway
- **Status Tracking**: Records last verification time and status
- **Health Check Endpoint**: Tests `/health` endpoint on HMS system

## Database Schema

### hms_credentials Table
```sql
CREATE TABLE hms_credentials (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    hospital_id BIGINT UNSIGNED UNIQUE NOT NULL,
    hms_name VARCHAR(150) NOT NULL,
    hms_api_endpoint VARCHAR(500) NOT NULL,
    hms_api_key TEXT,
    hms_auth_type VARCHAR(30) DEFAULT 'api_key',
    hms_username VARCHAR(100),
    hms_password TEXT,
    is_verified TINYINT DEFAULT 0,
    last_verified_at DATETIME,
    is_active TINYINT DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (hospital_id) REFERENCES abdm_hospitals(id) ON DELETE CASCADE
);
```

## How to Use

### Step 1: Run Migrations
```bash
cd gateway-php-ci4
php spark migrate
```

### Step 2: Access Admin Interface
1. Navigate to `/admin/hospitals` to view/add hospitals
2. Click "HMS Access Management" link or navigate to `/admin/hms-access`

### Step 3: Add Hospital
1. Go to `/admin/hospitals`
2. Fill in Hospital name and HFR ID
3. Select Gateway Mode (TEST/LIVE)
4. Add contact information
5. Click "Create Hospital"

### Step 4: Configure HMS Credential
1. Go to `/admin/hms-access`
2. Select the Hospital from dropdown
3. Enter HMS System Name (e.g., "Meddata HMS", "Athenahealth")
4. Enter HMS API Endpoint (e.g., https://hms.example.com/api)
5. Choose Authentication Type:
   - **API Key**: Provide Bearer token
   - **Basic Auth**: Provide Username and Password
6. Click "Add HMS Credential"

### Step 5: Test Connection
1. Go to `/admin/hms-access`
2. Find your HMS credential in the list
3. Click "Test Connection" button
4. Status will update to show verification result

### Step 6: View/Edit Credential
1. Click "View" on any credential
2. Update API endpoint or credentials as needed
3. Click "Update Credential"
4. Optional: Click "Delete Credential" to remove

## API Integration

### Using Hospital API Token
When a hospital user logs in via `/api/v3/hospital/login`, they receive an API token.

Use this token to authenticate requests:
```bash
curl -H "Authorization: Bearer {api_token}" \
     -H "Content-Type: application/json" \
     https://gateway.example.com/api/v3/endpoint
```

### Using Hospital ID
Gateway can fetch HMS credentials based on hospital_id:
```php
$hmsModel = new HmsCredential();
$credential = $hmsModel->getActiveByHospital($hospitalId);

// Use credential for HMS requests
$endpoint = $credential->hms_api_endpoint . '/patient/search';
$headers = [
    'Authorization' => 'Bearer ' . $credential->hms_api_key,
    'Content-Type' => 'application/json'
];
```

## Security Considerations

### 1. Credential Encryption
- API keys and passwords are encrypted before storage
- Use a strong ENCRYPTION_KEY in .env
- Example: `ENCRYPTION_KEY=your-256-bit-hex-key`

### 2. Access Control
- Only admin users should have access to `/admin/*` routes
- Add authentication middleware if needed
- Protect sensitive endpoints

### 3. HTTPS Required
- Always use HTTPS for HMS API endpoints
- Verify SSL certificates in production
- Never transmit credentials in plain text

### 4. API Rate Limiting
- Implement rate limiting on test endpoints
- Monitor connection test frequency
- Alert on repeated failures

## Troubleshooting

### Connection Test Fails
1. Check HMS API endpoint URL
2. Verify authentication credentials
3. Ensure gateway has outbound HTTPS access
4. Check HMS firewall rules
5. Verify HMS health endpoint responds to GET requests

### Credentials Not Saving
1. Check database permissions
2. Verify ENCRYPTION_KEY is set in .env
3. Check MySQL error logs
4. Ensure hospital_id is valid

### API Authentication Issues
1. Verify API token is not expired
2. Check token format in Authorization header
3. Ensure Bearer prefix is included
4. Verify hospital is active

## Admin Navigation

| Page | URL | Purpose |
|------|-----|---------|
| Dashboard | `/admin/dashboard` | Overview of all systems |
| Hospitals | `/admin/hospitals` | Create/manage hospitals |
| Hospital Users | `/admin/users` | Create user credentials |
| HMS Access | `/admin/hms-access` | Manage HMS integrations |
| M1 Module | `/admin/m1-module` | M1 ABHA configuration |

## Next Steps

1. **Run migrations** to create hms_credentials table
2. **Add first hospital** and test the interface
3. **Configure HMS endpoint** with your HMS provider
4. **Test connection** to verify integration
5. **Deploy to production** when ready

## Support

For issues or questions:
1. Check error messages on the interface
2. Review CI4 logs in `writable/logs/`
3. Verify database with MySQL queries
4. Test HMS endpoint with curl command

---

**Created**: May 14, 2026  
**Version**: 1.0  
**Status**: Ready for Testing
