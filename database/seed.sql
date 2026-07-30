USE scholarly_db;

INSERT INTO users (username, password_hash, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active'),
('maria.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('juan.delacruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

INSERT INTO scholars (user_id, student_no, first_name, last_name, course, year_level, email, qr_token, public_id, status) VALUES
(3, '2022-01456', 'Maria', 'Santos', 'BS Information Technology', '3rd Year', 'm.santos@stateu.edu.ph', SHA2('profile-maria-2022-01456', 256), 'SCH000000000001', 'active'),
(4, '2022-01891', 'Juan', 'Dela Cruz', 'BS Computer Science', '2nd Year', 'j.delacruz@stateu.edu.ph', SHA2('profile-juan-2022-01891', 256), 'SCH000000000002', 'active');

INSERT INTO scholarship_programs (name, description, amount, academic_year, semester, status) VALUES
('TES Grant', 'Tertiary Education Subsidy grant assistance', 5000.00, '2025-2026', '1st Semester', 'active'),
('Merit Scholarship', 'Academic merit-based scholarship', 8000.00, '2025-2026', '1st Semester', 'active'),
('Financial Aid Program', 'Need-based financial assistance', 3500.00, '2025-2026', '1st Semester', 'active');

INSERT INTO enrollments (scholar_id, program_id, status) VALUES
(1, 1, 'active'),
(1, 3, 'active'),
(2, 2, 'active');

INSERT INTO distribution_batches (program_id, name, distribution_date, venue, status, created_by) VALUES
(1, '1st Sem AY 2025-2026 Distribution', '2026-08-14', 'Main Campus Gym', 'open', 1),
(2, 'Merit Scholarship Midyear Release', '2026-07-20', 'Admin Building Lobby', 'closed', 1);

INSERT INTO claim_vouchers (batch_id, scholar_id, voucher_code, amount, status, expires_at) VALUES
(1, 1, 'VCH-2026-001-MARIA', 5000.00, 'pending', '2026-12-31 23:59:59'),
(2, 2, 'VCH-2026-MERIT-JUAN', 8000.00, 'claimed', '2026-12-31 23:59:59');

INSERT INTO claims (voucher_id, staff_user_id, claimed_at, profile_verified, notes) VALUES
(3, 2, '2026-02-03 10:15:00', 1, 'Claimed at lobby desk');

UPDATE claim_vouchers SET status = 'claimed' WHERE id = 3;

INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES
(2, 'claim_redeemed', 'claim_voucher', 3, 'Voucher redeemed for Juan Dela Cruz'),
(1, 'batch_opened', 'distribution_batch', 1, 'Batch opened for distribution day');
