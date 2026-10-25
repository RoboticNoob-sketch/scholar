SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_system');
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN is_system TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
    'DO 0');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
