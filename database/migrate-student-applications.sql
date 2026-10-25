-- Adds Google identity and student application support to an existing Scholarly database.
-- Select the target database in phpMyAdmin before running this file.

SET @migration_db = DATABASE();

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE users ADD COLUMN email VARCHAR(254) NULL UNIQUE AFTER username',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_sub') = 0,
    'ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL UNIQUE AFTER email',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

CREATE TABLE IF NOT EXISTS scholarship_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_user_id INT UNSIGNED NOT NULL,
    program_id INT UNSIGNED NULL,
    general_registration_user_id INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN program_id IS NULL THEN applicant_user_id ELSE NULL END) STORED,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    school_name VARCHAR(160) NOT NULL,
    course VARCHAR(120) NOT NULL,
    year_level VARCHAR(40) NOT NULL,
    application_note TEXT NULL,
    privacy_acknowledged_at DATETIME NULL,
    status ENUM('submitted', 'under_review', 'needs_info', 'approved', 'declined') NOT NULL DEFAULT 'submitted',
    review_note VARCHAR(1000) NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application_user_program (applicant_user_id, program_id),
    UNIQUE KEY uq_general_registration_user (general_registration_user_id),
    INDEX idx_application_status_created (status, created_at),
    CONSTRAINT fk_application_user FOREIGN KEY (applicant_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_program FOREIGN KEY (program_id) REFERENCES scholarship_programs(id) ON DELETE RESTRICT,
    CONSTRAINT fk_application_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

ALTER TABLE scholarship_applications MODIFY COLUMN program_id INT UNSIGNED NULL;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'general_registration_user_id') = 0,
    'ALTER TABLE scholarship_applications ADD COLUMN general_registration_user_id INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN program_id IS NULL THEN applicant_user_id ELSE NULL END) STORED',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_applications' AND INDEX_NAME = 'uq_general_registration_user') = 0,
    'CREATE UNIQUE INDEX uq_general_registration_user ON scholarship_applications (general_registration_user_id)',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'privacy_acknowledged_at') = 0,
    'ALTER TABLE scholarship_applications ADD COLUMN privacy_acknowledged_at DATETIME NULL AFTER application_note',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'photo_2x2_path') = 0,
    'ALTER TABLE scholarship_applications ADD COLUMN photo_2x2_path VARCHAR(255) NULL AFTER privacy_acknowledged_at',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_applications' AND COLUMN_NAME = 'enrollment_form_path') = 0,
    'ALTER TABLE scholarship_applications ADD COLUMN enrollment_form_path VARCHAR(255) NULL AFTER photo_2x2_path',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;