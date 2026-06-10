#!/bin/bash
# Owner Message Templates (Automation) — idempotent
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

echo "=== property_message_templates ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `property_message_templates` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `property_id`      INT UNSIGNED NOT NULL,
  `event_type`       VARCHAR(64)  NOT NULL,
  `is_enabled`       TINYINT(1)   NOT NULL DEFAULT 0,
  `message_text`     TEXT         NOT NULL,
  `send_delay_hours` SMALLINT     NOT NULL DEFAULT 0
                     COMMENT 'delay after event trigger (0 = immediate)',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_prop_event` (`property_id`, `event_type`),
  INDEX `idx_pmt_property` (`property_id`),
  INDEX `idx_pmt_event`    (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  property_message_templates OK"

echo "=== Done ==="
