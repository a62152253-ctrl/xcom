-- schema_v2.sql — Rozszerzenie bazy danych TaskManager

-- 13. Personal Notes table
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT 'Bez tytułu',
  `content` TEXT DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '#3b82f6',
  `tags` VARCHAR(255) DEFAULT NULL,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Workspace Invites table (team sharing)
CREATE TABLE IF NOT EXISTS `workspace_invites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invited_by` INT NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `role` ENUM('Administrator', 'Member') DEFAULT 'Member',
  `status` ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. User Tokens (Remember Me)
CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `selector` VARCHAR(255) NOT NULL UNIQUE,
  `validator_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
