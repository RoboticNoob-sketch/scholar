-- Smoke-test seed data: 4 programs, 4 batches, 20 scholars.
-- Safe to re-run: every insert is guarded, so nothing is duplicated.
-- Import via phpMyAdmin (select the database first). Remove with seed-smoke-test-remove.sql.
--
-- Programs:  1 open batch  (vouchers generated)   2 open batch (vouchers generated)
--            3 draft batch (generate vouchers in the UI)   4 closed batch
-- Scholars:  SEED-2026-001..020, 5 per program. They have no login accounts.

SET @admin_id = (SELECT id FROM users WHERE role = 'admin' AND is_system = 0 ORDER BY id LIMIT 1);

INSERT INTO scholarship_programs (name, description, amount, total_budget, academic_year, semester, status)
SELECT * FROM (
  SELECT 'SK Educational Assistance' AS name, 'Smoke-test seed: semestral tuition assistance.' AS description, 3000.00 AS amount, 60000.00 AS total_budget, '2026-2027' AS academic_year, '1st Semester' AS semester, 'active' AS status
  UNION ALL SELECT 'SK Academic Excellence Grant', 'Smoke-test seed: grant for honor students.', 5000.00, 80000.00, '2026-2027', '1st Semester', 'active'
  UNION ALL SELECT 'SK Book & Allowance Support', 'Smoke-test seed: books and school supplies allowance.', 1500.00, 40000.00, '2026-2027', '1st Semester', 'active'
  UNION ALL SELECT 'SK Skills Training Grant', 'Smoke-test seed: technical-vocational training support.', 2000.00, 40000.00, '2026-2027', '2nd Semester', 'active'
) p
WHERE NOT EXISTS (SELECT 1 FROM scholarship_programs x WHERE x.name = p.name);

INSERT INTO distribution_batches (program_id, name, distribution_date, venue, status, created_by)
SELECT p.id, b.name, DATE_ADD(CURDATE(), INTERVAL b.day_offset DAY), b.venue, b.status, @admin_id
FROM (
  SELECT 'SK Educational Assistance' AS program, 'Educational Assistance - 1st Release' AS name, 0 AS day_offset, 'Barangay Cabay Covered Court' AS venue, 'open' AS status
  UNION ALL SELECT 'SK Academic Excellence Grant', 'Academic Excellence - 1st Release', 3, 'Barangay Cabay Hall', 'open'
  UNION ALL SELECT 'SK Book & Allowance Support', 'Book Allowance - Draft Release', 14, 'SK Office, Barangay Cabay', 'draft'
  UNION ALL SELECT 'SK Skills Training Grant', 'Skills Training - Completed Release', -30, 'Barangay Cabay Covered Court', 'closed'
) b
JOIN scholarship_programs p ON p.name = b.program
WHERE NOT EXISTS (SELECT 1 FROM distribution_batches x WHERE x.program_id = p.id AND x.name = b.name);

INSERT IGNORE INTO scholars (student_no, first_name, last_name, course, year_level, email, phone, qr_token, public_id, status)
SELECT CONCAT('SEED-2026-', LPAD(s.n, 3, '0')), s.first_name, s.last_name, s.course, s.year_level,
       CONCAT('seed.scholar', LPAD(s.n, 2, '0'), '@example.com'),
       CONCAT('0917000', LPAD(s.n, 4, '0')),
       SHA2(CONCAT('scholarly-seed-qr-', s.n), 256),
       CONCAT('SCH9000000', LPAD(s.n, 5, '0')),
       'active'
FROM (
  SELECT 1 AS n, 'Maria' AS first_name, 'Santos' AS last_name, 'BS Information Technology' AS course, '1st Year' AS year_level
  UNION ALL SELECT 2, 'Juan', 'Dela Cruz', 'BS Information Technology', '2nd Year'
  UNION ALL SELECT 3, 'Angela', 'Reyes', 'BS Education', '3rd Year'
  UNION ALL SELECT 4, 'Mark', 'Garcia', 'BS Agriculture', '1st Year'
  UNION ALL SELECT 5, 'Kristine', 'Mendoza', 'BS Business Administration', '4th Year'
  UNION ALL SELECT 6, 'Joshua', 'Bautista', 'BS Nursing', '2nd Year'
  UNION ALL SELECT 7, 'Patricia', 'Villanueva', 'BS Education', '1st Year'
  UNION ALL SELECT 8, 'Daniel', 'Aquino', 'BS Engineering', '3rd Year'
  UNION ALL SELECT 9, 'Sofia', 'Ramos', 'BS Information Technology', '4th Year'
  UNION ALL SELECT 10, 'Carlo', 'Navarro', 'BS Criminology', '2nd Year'
  UNION ALL SELECT 11, 'Bianca', 'Torres', 'BS Accountancy', '1st Year'
  UNION ALL SELECT 12, 'Miguel', 'Flores', 'BS Agriculture', '3rd Year'
  UNION ALL SELECT 13, 'Jasmine', 'Castillo', 'BS Nursing', '1st Year'
  UNION ALL SELECT 14, 'Ryan', 'Domingo', 'BS Engineering', '2nd Year'
  UNION ALL SELECT 15, 'Alyssa', 'Pascual', 'BS Education', '4th Year'
  UNION ALL SELECT 16, 'Kenneth', 'Soriano', 'BS Business Administration', '3rd Year'
  UNION ALL SELECT 17, 'Nicole', 'Valdez', 'BS Information Technology', '1st Year'
  UNION ALL SELECT 18, 'Paolo', 'Lim', 'BS Criminology', '3rd Year'
  UNION ALL SELECT 19, 'Hazel', 'Macaraeg', 'BS Accountancy', '2nd Year'
  UNION ALL SELECT 20, 'Jerome', 'Tolentino', 'BS Engineering', '4th Year'
) s;

-- 5 scholars per program: 001-005 -> program 1, 006-010 -> 2, 011-015 -> 3, 016-020 -> 4
INSERT IGNORE INTO enrollments (scholar_id, program_id, status)
SELECT sc.id, p.id, 'active'
FROM scholars sc
JOIN scholarship_programs p ON p.name = ELT(
  FLOOR((CAST(SUBSTRING(sc.student_no, 11) AS UNSIGNED) - 1) / 5) + 1,
  'SK Educational Assistance', 'SK Academic Excellence Grant', 'SK Book & Allowance Support', 'SK Skills Training Grant')
WHERE sc.student_no LIKE 'SEED-2026-%';

-- Vouchers for the two open batches only
INSERT IGNORE INTO claim_vouchers (batch_id, scholar_id, voucher_code, amount, status, expires_at)
SELECT b.id, e.scholar_id,
       CONCAT('VCH-SEED-', LPAD(CAST(SUBSTRING(sc.student_no, 11) AS UNSIGNED), 4, '0')),
       p.amount, 'pending', DATE_ADD(b.distribution_date, INTERVAL 180 DAY)
FROM distribution_batches b
JOIN scholarship_programs p ON p.id = b.program_id
JOIN enrollments e ON e.program_id = p.id AND e.status = 'active'
JOIN scholars sc ON sc.id = e.scholar_id AND sc.student_no LIKE 'SEED-2026-%'
WHERE b.status = 'open'
  AND b.name IN ('Educational Assistance - 1st Release', 'Academic Excellence - 1st Release');
