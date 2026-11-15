-- Adds submissions.contact_phone when it is missing.
-- Safe to run multiple times on the same database.

SET @db_name = DATABASE();
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'submissions'
      AND COLUMN_NAME = 'contact_phone'
);

SET @migration_sql = IF(
    @column_exists = 0,
    'ALTER TABLE submissions ADD COLUMN contact_phone VARCHAR(40) NULL AFTER contact_email',
    "SELECT 'contact_phone already exists' AS migration_status"
);

PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
