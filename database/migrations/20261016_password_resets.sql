-- Migration: 20261016_password_resets.sql
-- Description: Create st_password_resets table for secure tokenized password reset lifecycle

CREATE TABLE IF NOT EXISTS `st_password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  KEY `idx_pwd_reset_user` (`user_id`),
  KEY `idx_pwd_reset_token` (`token_hash`),
  KEY `idx_pwd_reset_expires` (`expires_at`),
  CONSTRAINT `fk_pwd_reset_user` FOREIGN KEY (`user_id`) REFERENCES `st_user_master` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
