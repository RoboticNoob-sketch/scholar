-- Run once against an existing scholarly_db:
-- mysql -u root scholarly_db < database/migrate-program-budget.sql

ALTER TABLE scholarship_programs
    ADD COLUMN total_budget DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER amount;