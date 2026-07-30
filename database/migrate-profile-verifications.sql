-- Run once on existing scholarly_db:
-- mysql -u root scholarly_db < database/migrate-profile-verifications.sql

USE scholarly_db;

CREATE TABLE IF NOT EXISTS staff_profile_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_user_id INT UNSIGNED NOT NULL,
    scholar_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    consumed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_spv_staff FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_spv_scholar FOREIGN KEY (scholar_id) REFERENCES scholars(id) ON DELETE CASCADE,
    INDEX idx_spv_staff_active (staff_user_id, expires_at, consumed_at)
);
