START TRANSACTION;

INSERT INTO abdm_hospitals (
    hospital_name,
    hfr_id,
    gateway_mode,
    contact_name,
    contact_email,
    contact_phone,
    is_active,
    created_at,
    updated_at
)
VALUES (
    'Demo Hospital',
    'HFR-DEMO-0001',
    'test',
    'Gateway Admin',
    'admin@demo-hospital.local',
    '9999999999',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    hospital_name = VALUES(hospital_name),
    gateway_mode = VALUES(gateway_mode),
    contact_name = VALUES(contact_name),
    contact_email = VALUES(contact_email),
    contact_phone = VALUES(contact_phone),
    is_active = VALUES(is_active),
    updated_at = NOW();

SET @hospital_id = (
    SELECT id
    FROM abdm_hospitals
    WHERE hfr_id = 'HFR-DEMO-0001'
    LIMIT 1
);

INSERT INTO abdm_hospital_users (
    hospital_id,
    username,
    password_hash,
    api_token,
    role,
    is_active,
    created_at,
    updated_at
)
VALUES (
    @hospital_id,
    'hospitaladmin',
    '$2y$10$jRppxDn6DRmb5g.JWBvwGOoH5J4mUbfD.OKyRlTWwz..nDQAJBh0S',
    'b3f2d26d5731f28b248a803f7336b947474431968846747183e843e9903bda9a',
    'hospital_admin',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    hospital_id = VALUES(hospital_id),
    password_hash = VALUES(password_hash),
    api_token = VALUES(api_token),
    role = VALUES(role),
    is_active = VALUES(is_active),
    updated_at = NOW();

COMMIT;
