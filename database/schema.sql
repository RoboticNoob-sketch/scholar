CREATE DATABASE IF NOT EXISTS scholarly_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scholarly_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'student') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE scholars (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    student_no VARCHAR(32) NOT NULL UNIQUE,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    course VARCHAR(120) NULL,
    year_level VARCHAR(40) NULL,
    email VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    qr_token CHAR(64) NOT NULL UNIQUE,
    public_id CHAR(16) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_scholars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE scholarship_programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scholar_id INT UNSIGNED NOT NULL,
    program_id INT UNSIGNED NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'removed') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_enrollment (scholar_id, program_id),
    CONSTRAINT fk_enrollment_scholar FOREIGN KEY (scholar_id) REFERENCES scholars(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollment_program FOREIGN KEY (program_id) REFERENCES scholarship_programs(id) ON DELETE CASCADE
);

CREATE TABLE distribution_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    distribution_date DATE NOT NULL,
    venue VARCHAR(160) NOT NULL,
    status ENUM('draft', 'open', 'closed') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_batch_program FOREIGN KEY (program_id) REFERENCES scholarship_programs(id) ON DELETE CASCADE,
    CONSTRAINT fk_batch_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE claim_vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    scholar_id INT UNSIGNED NOT NULL,
    voucher_code VARCHAR(64) NOT NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'claimed', 'expired', 'void') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_batch_scholar (batch_id, scholar_id),
    CONSTRAINT fk_voucher_batch FOREIGN KEY (batch_id) REFERENCES distribution_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_voucher_scholar FOREIGN KEY (scholar_id) REFERENCES scholars(id) ON DELETE CASCADE
);

CREATE TABLE claims (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT UNSIGNED NOT NULL UNIQUE,
    staff_user_id INT UNSIGNED NOT NULL,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_verified TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    CONSTRAINT fk_claim_voucher FOREIGN KEY (voucher_id) REFERENCES claim_vouchers(id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_staff FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(40) NULL,
    entity_id INT UNSIGNED NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_vouchers_status ON claim_vouchers(status);
CREATE INDEX idx_batches_status ON distribution_batches(status);
CREATE INDEX idx_claims_claimed_at ON claims(claimed_at);

CREATE TABLE staff_profile_verifications (
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
