#!/bin/bash
# Message Queue — delayed LINE message delivery (idempotent)
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

echo "=== message_queue ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `message_queue` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `booking_id`         INT UNSIGNED    NULL     COMMENT 'booking context (nullable for reengagement)',
  `property_id`        INT UNSIGNED    NOT NULL,
  `event_type`         VARCHAR(64)     NOT NULL,
  `message_text`       TEXT            NOT NULL,
  `guest_line_user_id` VARCHAR(128)    NOT NULL,
  `send_after`         DATETIME        NOT NULL COMMENT 'UTC time to deliver',
  `sent_at`            DATETIME        NULL,
  `status`             ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mq_pending` (`status`, `send_after`),
  INDEX `idx_mq_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  message_queue OK"

echo "=== Done ==="
