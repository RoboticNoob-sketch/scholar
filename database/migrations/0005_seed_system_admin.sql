-- Needs 'system_admin_password_hash' in web/config/config.local.php (output of password_hash()).
-- Until it is set, this migration is skipped and retried on later requests.
INSERT INTO users (username, password_hash, role, status, is_system)
SELECT 'systemadmin', '{{SYSTEM_ADMIN_PASSWORD_HASH}}', 'admin', 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'systemadmin');

UPDATE users SET is_system = 1 WHERE username = 'systemadmin';
