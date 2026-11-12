-- NIWFF staging database. Import into a dedicated, empty MySQL/MariaDB database.
CREATE TABLE IF NOT EXISTS admins (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(100) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('club','event') NOT NULL,
 status ENUM('pending','approved','rejected','changes_requested','withdrawn') NOT NULL DEFAULT 'pending',
 payload JSON NOT NULL,
 contact_name VARCHAR(160) NOT NULL,
 contact_email VARCHAR(254) NOT NULL,
 reviewer_note TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 published_at DATETIME NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 INDEX publication (kind,status,published_at),
 FOREIGN KEY (reviewed_by) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_log (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 submission_id BIGINT UNSIGNED NOT NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 old_status VARCHAR(32) NOT NULL,
 new_status VARCHAR(32) NOT NULL,
 note TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (submission_id) REFERENCES submissions(id),
 FOREIGN KEY (admin_id) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
