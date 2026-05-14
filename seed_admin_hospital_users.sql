INSERT INTO abdm_hospitals (
    hospital_name,
    hfr_id,
    gateway_mode,
    contact_name,
    contact_email,
    contact_phone,
    is_active
) VALUES (
    'Test Hospital',
    'TH-2026-001',
    'test',
    'HMS Dev Team',
    'dev@test-hospital.com',
    '+91-9999999999',
    1
)
ON DUPLICATE KEY UPDATE
    hospital_name = VALUES(hospital_name),
    gateway_mode = VALUES(gateway_mode),
    contact_name = VALUES(contact_name),
    contact_email = VALUES(contact_email),
    contact_phone = VALUES(contact_phone),
    is_active = VALUES(is_active);

SET @hospital_id = (SELECT id FROM abdm_hospitals WHERE hfr_id = 'TH-2026-001' LIMIT 1);

INSERT INTO abdm_hospital_users (
    hospital_id,
    username,
    password_hash,
    api_token,
    role,
    is_active
) VALUES (
    @hospital_id,
    'admin_portal',
    '$2y$10$036H78E.6rK6JG9v1y5es.84C4diGP2oMr4FUf0AdxKzqnDHkK9NW',
    'svc-admin-portal-token-2026-001',
    'service_provider',
    1
)
ON DUPLICATE KEY UPDATE
    hospital_id = VALUES(hospital_id),
    password_hash = VALUES(password_hash),
    api_token = VALUES(api_token),
    role = VALUES(role),
    is_active = VALUES(is_active);

INSERT INTO abdm_hospital_users (
    hospital_id,
    username,
    password_hash,
    api_token,
    role,
    is_active
) VALUES (
    @hospital_id,
    'hms_dev_user',
    '$2y$10$1IOxExOgMWajm6FdsnItBOgosu.V724bm.HBw6xeStyWreCKiQWJK',
    'hms-dev-user-token-2026-001',
    'hospital_user',
    1
)
ON DUPLICATE KEY UPDATE
    hospital_id = VALUES(hospital_id),
    password_hash = VALUES(password_hash),
    api_token = VALUES(api_token),
    role = VALUES(role),
    is_active = VALUES(is_active);

SELECT id, username, role, hospital_id, is_active
FROM abdm_hospital_users
WHERE username IN ('admin_portal', 'hms_dev_user');
