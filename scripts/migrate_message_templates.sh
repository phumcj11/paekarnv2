#!/bin/bash
# Owner Message Templates (Automation) — idempotent
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
