-- Create database
CREATE DATABASE IF NOT EXISTS cnlrrs_db;
USE cnlrrs_db;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'developer', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    account_locked_until BIGINT DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    login_attempts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Login attempts log
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    successful BOOLEAN NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Indexes for faster lookups
CREATE INDEX idx_login_attempts_admin ON login_attempts(admin_id);
CREATE INDEX idx_login_attempts_time ON login_attempts(attempt_time);

-- Initial test user (password: admin123)
INSERT INTO admins (username, password_hash, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');