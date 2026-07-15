-- CRITICAL SECURITY FIXES - Run these immediately

-- 1. Add missing user columns
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT DEFAULT 0 AFTER status;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL AFTER email_verified;

-- 2. Add user agent to activity logs (if not exists)
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS user_agent VARCHAR(500) AFTER ip_address;

-- 3. Create rate_limit table
CREATE TABLE IF NOT EXISTS rate_limit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_hash VARCHAR(64) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempt_time INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action_time (ip_hash, action, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create workspace_invites table if missing
CREATE TABLE IF NOT EXISTS workspace_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Member',
    invited_by INT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_status_expires (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Add security indexes to existing tables
ALTER TABLE tasks ADD INDEX IF NOT EXISTS idx_project_status (project_id, status);
ALTER TABLE tasks ADD INDEX IF NOT EXISTS idx_assigned_to_status (assigned_to, status);
ALTER TABLE projects ADD INDEX IF NOT EXISTS idx_created_by (created_by);
ALTER TABLE activity_logs ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at);

-- 6. Ensure proper constraints
ALTER TABLE activity_logs MODIFY ip_address VARCHAR(45);

-- 7. Increase password_hash size for ARGON2ID compatibility
ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL;

COMMIT;
