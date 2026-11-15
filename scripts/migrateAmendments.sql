-- Safe to run on an existing database; also included in schema.sql.
CREATE TABLE IF NOT EXISTS submission_amendments (
 submission_id BIGINT UNSIGNED PRIMARY KEY,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 message TEXT NOT NULL,
 FOREIGN KEY (submission_id) REFERENCES submissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS submission_revisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 submission_id BIGINT UNSIGNED NOT NULL,
 actor VARCHAR(32) NOT NULL,
 payload JSON NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (submission_id) REFERENCES submissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
