#!/bin/bash
mysql -u pcj_paekarn -pPaekarn@2026! pcj_paekarn <<'SQL'
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token_hash` (`token_hash`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "Migration result: $?"
mysql -u pcj_paekarn -pPaekarn@2026! pcj_paekarn -e "SHOW TABLES LIKE 'password_reset_tokens';"
