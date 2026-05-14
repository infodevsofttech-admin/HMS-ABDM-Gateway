INSERT INTO abdm_hospital_users (hospital_id, username, password_hash, api_token, role, is_active) VALUES (1, 'admin', '$2y$10$gKHnUdXwNqL5nzc7v7L3wuVjwQhZ5dUg8K1k5M9pZ8dW3F7rX9Y0C', 'testapitoken123456', 'hospital_admin', 1);
SELECT id, username, role, hospital_id FROM abdm_hospital_users;
