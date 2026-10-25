-- DESTRUCTIVE TEST RESET: backs up nothing and removes existing records.
-- Select u626072890_scholar in phpMyAdmin before running this script.
-- It preserves the schema, clears application/test records, then creates only
-- the admin and approver accounts. It does not delete uploaded files from disk.
-- Replace both bcrypt placeholders before executing this script.

USE `u626072890_scholar`;

START TRANSACTION;

DELETE FROM claims;
DELETE FROM staff_profile_verifications;
DELETE FROM claim_vouchers;
DELETE FROM enrollments;
DELETE FROM scholarship_applications;
DELETE FROM approval_requests;
DELETE FROM api_tokens;
DELETE FROM audit_logs;
DELETE FROM distribution_batches;
DELETE FROM scholars;
DELETE FROM scholarship_programs;
DELETE FROM users;

INSERT INTO users (username, email, google_sub, password_hash, role, status) VALUES
('admin', NULL, NULL, 'REPLACE_WITH_ADMIN_BCRYPT_HASH', 'admin', 'active'),
('approver', NULL, NULL, 'REPLACE_WITH_APPROVER_BCRYPT_HASH', 'approver', 'active');

COMMIT;

SELECT id, username, role, status FROM users ORDER BY username;
