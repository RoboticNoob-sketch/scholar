-- Demo mobile accounts for production (password: password)
-- Run in phpMyAdmin after hostinger-schema.sql and admin user.

INSERT INTO users (username, password_hash, role, status) VALUES
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active'),
('maria.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

INSERT INTO scholars (user_id, student_no, first_name, last_name, course, year_level, email, qr_token, public_id, status)
SELECT u.id, '2022-01456', 'Maria', 'Santos', 'BS Information Technology', '3rd Year', 'm.santos@stateu.edu.ph',
       SHA2('profile-maria-2022-01456', 256), 'SCH000000000001', 'active'
FROM users u WHERE u.username = 'maria.santos';
