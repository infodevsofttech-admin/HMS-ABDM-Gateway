CREATE TABLE IF NOT EXISTS abdm_abha_profiles (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    hospital_id BIGINT(20) UNSIGNED NULL,
    user_id BIGINT(20) UNSIGNED NULL,
    abha_number VARCHAR(50) NOT NULL,
    abha_address VARCHAR(255) NULL,
    full_name VARCHAR(255) NULL,
    gender VARCHAR(20) NULL,
    mobile VARCHAR(20) NULL,
    date_of_birth VARCHAR(30) NULL,
    year_of_birth VARCHAR(10) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'verified',
    last_request_id VARCHAR(100) NULL,
    last_verified_at DATETIME NULL,
    profile_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_abdm_abha_profiles_abha_number (abha_number),
    KEY idx_abdm_abha_profiles_hospital_id (hospital_id),
    KEY idx_abdm_abha_profiles_user_id (user_id),
    KEY idx_abdm_abha_profiles_last_verified_at (last_verified_at),
    CONSTRAINT fk_abdm_abha_profiles_hospital_id
        FOREIGN KEY (hospital_id)
        REFERENCES abdm_hospitals (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_abdm_abha_profiles_user_id
        FOREIGN KEY (user_id)
        REFERENCES abdm_hospital_users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
