CREATE TABLE IF NOT EXISTS abdm_hospitals (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    hospital_name VARCHAR(255) NOT NULL,
    hfr_id VARCHAR(100) NOT NULL,
    gateway_mode VARCHAR(10) NOT NULL DEFAULT 'test',
    contact_name VARCHAR(150) NULL,
    contact_email VARCHAR(150) NULL,
    contact_phone VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_abdm_hospitals_hfr_id (hfr_id),
    KEY idx_abdm_hospitals_gateway_mode (gateway_mode),
    KEY idx_abdm_hospitals_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS abdm_hospital_users (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    hospital_id BIGINT(20) UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    api_token VARCHAR(128) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'hospital_admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_abdm_hospital_users_username (username),
    UNIQUE KEY uq_abdm_hospital_users_api_token (api_token),
    KEY idx_abdm_hospital_users_hospital_id (hospital_id),
    KEY idx_abdm_hospital_users_is_active (is_active),
    CONSTRAINT fk_abdm_hospital_users_hospital_id
        FOREIGN KEY (hospital_id)
        REFERENCES abdm_hospitals (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS abdm_test_submission_logs (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id VARCHAR(100) NULL,
    hospital_id BIGINT(20) UNSIGNED NULL,
    user_id BIGINT(20) UNSIGNED NULL,
    event_type VARCHAR(100) NULL,
    endpoint VARCHAR(255) NOT NULL,
    http_status INT NOT NULL DEFAULT 200,
    request_payload JSON NULL,
    response_payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_abdm_test_submission_logs_request_id (request_id),
    KEY idx_abdm_test_submission_logs_hospital_id (hospital_id),
    KEY idx_abdm_test_submission_logs_user_id (user_id),
    KEY idx_abdm_test_submission_logs_event_type (event_type),
    KEY idx_abdm_test_submission_logs_created_at (created_at),
    CONSTRAINT fk_abdm_test_submission_logs_hospital_id
        FOREIGN KEY (hospital_id)
        REFERENCES abdm_hospitals (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_abdm_test_submission_logs_user_id
        FOREIGN KEY (user_id)
        REFERENCES abdm_hospital_users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
