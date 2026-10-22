-- One-go, repeatable upgrade for an existing Scholarly database.
-- In phpMyAdmin, select the target database first, then run this file in the SQL tab.
-- Back up the database before applying production schema changes.

SET @migration_db = DATABASE();

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'scholarship_programs' AND COLUMN_NAME = 'total_budget') = 0,
    'ALTER TABLE scholarship_programs ADD COLUMN total_budget DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER amount',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'approver', 'staff', 'student') NOT NULL;

CREATE TABLE IF NOT EXISTS approval_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_type VARCHAR(40) NOT NULL,
    entity_id INT UNSIGNED NULL,
    payload LONGTEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    submitted_by INT UNSIGNED NOT NULL,
    reviewed_by INT UNSIGNED NULL,
    review_note VARCHAR(1000) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_approval_submitter FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_approval_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_approval_status_created (status, created_at),
    INDEX idx_approval_entity (request_type, entity_id, status)
);

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'id') = 0,
    'ALTER TABLE approval_requests ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'request_type') = 0,
    'ALTER TABLE approval_requests ADD COLUMN request_type VARCHAR(40) NOT NULL DEFAULT ''program_create''', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'entity_id') = 0,
    'ALTER TABLE approval_requests ADD COLUMN entity_id INT UNSIGNED NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'payload') = 0,
    'ALTER TABLE approval_requests ADD COLUMN payload LONGTEXT NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;
UPDATE approval_requests SET payload = '{}' WHERE payload IS NULL;
ALTER TABLE approval_requests MODIFY COLUMN payload LONGTEXT NOT NULL;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE approval_requests ADD COLUMN status ENUM(''pending'',''approved'',''rejected'') NOT NULL DEFAULT ''pending''', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;
ALTER TABLE approval_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending';

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'submitted_by') = 0,
    'ALTER TABLE approval_requests ADD COLUMN submitted_by INT UNSIGNED NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'reviewed_by') = 0,
    'ALTER TABLE approval_requests ADD COLUMN reviewed_by INT UNSIGNED NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'review_note') = 0,
    'ALTER TABLE approval_requests ADD COLUMN review_note VARCHAR(1000) NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE approval_requests ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'reviewed_at') = 0,
    'ALTER TABLE approval_requests ADD COLUMN reviewed_at DATETIME NULL', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND INDEX_NAME = 'idx_approval_status_created') = 0,
    'ALTER TABLE approval_requests ADD INDEX idx_approval_status_created (status, created_at)', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND INDEX_NAME = 'idx_approval_entity') = 0,
    'ALTER TABLE approval_requests ADD INDEX idx_approval_entity (request_type, entity_id, status)', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND CONSTRAINT_NAME = 'fk_approval_submitter') = 0,
    'ALTER TABLE approval_requests ADD CONSTRAINT fk_approval_submitter FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE RESTRICT', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @migration_db AND TABLE_NAME = 'approval_requests' AND CONSTRAINT_NAME = 'fk_approval_reviewer') = 0,
    'ALTER TABLE approval_requests ADD CONSTRAINT fk_approval_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE RESTRICT', 'DO 0'
);
PREPARE migration_stmt FROM @migration_sql; EXECUTE migration_stmt; DEALLOCATE PREPARE migration_stmt;

-- Existing program budgets are intentionally left unchanged; set confirmed amounts in Programs > Edit.

SELECT DATABASE() AS migrated_database;
SELECT role, COUNT(*) AS user_count FROM users GROUP BY role;
SELECT name, total_budget FROM scholarship_programs WHERE name = 'Financial Aid Program';
SHOW COLUMNS FROM approval_requests;