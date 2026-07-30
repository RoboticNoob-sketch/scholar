-- Run in phpMyAdmin after hostinger-schema.sql
-- Default login: admin / password  (change after first login)

INSERT INTO users (username, password_hash, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
