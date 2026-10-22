-- Run once against an existing scholarly_db:
-- mysql -u root scholarly_db < database/migrate-approval-workflow.sql

ALTER TABLE users
    MODIFY role ENUM('admin', 'approver', 'staff', 'student') NOT NULL;

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