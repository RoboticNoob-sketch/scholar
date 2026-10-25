-- Indexes so audit log filtering by date, action and user stays fast as the table grows.

SET @migration_db = DATABASE();

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_created') = 0,
    'ALTER TABLE audit_logs ADD INDEX idx_audit_created (created_at)',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_action_created') = 0,
    'ALTER TABLE audit_logs ADD INDEX idx_audit_action_created (action, created_at)',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @migration_db AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_user_created') = 0,
    'ALTER TABLE audit_logs ADD INDEX idx_audit_user_created (user_id, created_at)',
    'DO 0'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
