-- Run once on existing databases (phpMyAdmin → SQL).
ALTER TABLE scholars
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER phone;
