-- TaskManager Pro - Schema Fixes & Index Optimization
-- Run this to add missing tables and optimize database

-- Activity Logs Table with User Agent
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Rate Limiting Table
CREATE TABLE IF NOT EXISTS rate_limit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_hash VARCHAR(64) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempt_time INT NOT NULL,
    INDEX idx_ip_hash (ip_hash),
    INDEX idx_action (action),
    INDEX idx_attempt_time (attempt_time)
);

-- Add Missing Columns to Users Table (skipped if already present)
-- ALTER TABLE users ADD last_login TIMESTAMP NULL DEFAULT NULL;
-- ALTER TABLE users ADD email_verified TINYINT DEFAULT 0;
-- ALTER TABLE users ADD email_verified_at TIMESTAMP NULL DEFAULT NULL;
-- ALTER TABLE users ADD password_reset_token VARCHAR(255) NULL DEFAULT NULL;
-- ALTER TABLE users ADD password_reset_expires TIMESTAMP NULL DEFAULT NULL;

-- Task Files Table (secure file uploads)
CREATE TABLE IF NOT EXISTS task_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    UNIQUE INDEX idx_file_hash (file_hash),
    INDEX idx_task_id (task_id)
);

-- Task Comments Table
CREATE TABLE IF NOT EXISTS task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Subtasks Table
CREATE TABLE IF NOT EXISTS subtasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_completed TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
);

-- Optimize Existing Tables with Indexes
CREATE INDEX idx_tasks_project_id ON tasks(project_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_created_at ON tasks(created_at);

CREATE INDEX idx_projects_created_by ON projects(created_by);
CREATE INDEX idx_projects_is_archived ON projects(is_archived);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);

-- Backup table for audit (optional)
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50),
    entity_id INT,
    action VARCHAR(50),
    old_values JSON,
    new_values JSON,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user_id (user_id)
);
