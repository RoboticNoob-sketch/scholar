-- Run AFTER hostinger-schema.sql in phpMyAdmin.
-- Replace the password hash before importing (see deploy/hostinger/DEPLOY.md).

INSERT INTO users (username, password_hash, role, status) VALUES
('admin', 'REPLACE_WITH_BCRYPT_HASH', 'admin', 'active');
