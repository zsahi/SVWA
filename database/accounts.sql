-- Separate from the deliberately injectable legacy lab database.
CREATE DATABASE IF NOT EXISTS svwa_accounts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'svwa_accounts'@'%' IDENTIFIED BY 'svwa_accounts_local';
GRANT SELECT, INSERT, UPDATE, DELETE ON svwa_accounts.* TO 'svwa_accounts'@'%';
USE svwa_accounts;
CREATE TABLE IF NOT EXISTS accounts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(60) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(254) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('user','admin') NOT NULL DEFAULT 'user',
 session_version INT NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS password_resets (
 token_hash CHAR(64) PRIMARY KEY,
 account_id INT UNSIGNED NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS student_results (
 roll_no CHAR(4) PRIMARY KEY,
 owner_id INT UNSIGNED NOT NULL UNIQUE,
 student_name VARCHAR(100) NOT NULL,
 subject VARCHAR(100) NOT NULL,
 marks INT NOT NULL,
 FOREIGN KEY (owner_id) REFERENCES accounts(id) ON DELETE CASCADE
);
-- Hashes generated with PHP password_hash(). Reapplying preserves existing profiles/passwords.
INSERT IGNORE INTO accounts (username,name,email,password_hash,role) VALUES
('admin','SVWA Administrator','admin@example.test','$2y$10$iyrVQbkggKOrWC8fi7I0Z.Gekm61axMDCHDyo6EfuN6JbbbbRG/Oa','admin'),
('user1','Ayesha Khan','user1@example.test','$2y$10$WET9uOtCJJQEt5IC8A9FCOpde3SoVHf7YOYVQ4yL1d0Ttw/qKbC3S','user'),
('user2','Bilal Ahmed','user2@example.test','$2y$10$AWOOZtGH3FJJPSGE/jBY3eQz.me6Qim5v1o5yeprHqKzw6Bxolmge','user');
INSERT IGNORE INTO student_results SELECT '1001',id,'Ayesha Khan','Computer Science',88 FROM accounts WHERE username='user1';
INSERT IGNORE INTO student_results SELECT '1002',id,'Bilal Ahmed','Applied Mathematics',67 FROM accounts WHERE username='user2';
