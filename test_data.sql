INSERT INTO abdm_hospitals (hospital_name, hfr_id, gateway_mode, contact_name, contact_email, contact_phone, is_active) 
VALUES ('Test Hospital', 'TH-2026-001', 'test', 'Admin User', 'admin@test-hospital.com', '+91-9999999999', 1);

INSERT INTO abdm_hospital_users (hospital_id, username, password_hash, api_token, role, is_active) 
VALUES (1, 'admin', '$2y$10$gKHnUdXwNqL5nzc7v7L3wuVjwQhZ5dUg8K1k5M9pZ8dW3F7rX9Y0C', 'testapitoken123456', 'hospital_admin', 1);

SELECT COUNT(*) AS hospital_count FROM abdm_hospitals;
SELECT COUNT(*) AS user_count FROM abdm_hospital_users;
