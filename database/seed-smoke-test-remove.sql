-- Removes everything created by seed-smoke-test.sql (vouchers, claims, enrollments cascade).
DELETE FROM scholars WHERE student_no LIKE 'SEED-2026-%';
DELETE FROM scholarship_programs
WHERE name IN ('SK Educational Assistance', 'SK Academic Excellence Grant', 'SK Book & Allowance Support', 'SK Skills Training Grant')
  AND description LIKE 'Smoke-test seed:%';
