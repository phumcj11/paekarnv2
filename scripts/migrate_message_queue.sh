#!/bin/bash
# Message Queue — delayed LINE message delivery (idempotent)
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "ERROR: Set DB_NAME, DB_USER, and DB_PASS before running this script."
  exit 1
fi

export MYSQL_PWD="$DB_PASS"
MYSQL="mysql -u${DB_USER} ${DB_NAME}"

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
