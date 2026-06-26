#!/bin/bash
# สิทธิ์บริการสมาชิก (manual perks) — idempotent
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

echo "=== owner_membership_perk_grants ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `owner_membership_perk_grants` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id`    INT UNSIGNED NOT NULL,
  `perk_key`    VARCHAR(80) NOT NULL,
  `status`      ENUM('pending','granted','waived') NOT NULL DEFAULT 'pending',
  `note`        TEXT NULL,
  `granted_at`  DATETIME NULL DEFAULT NULL,
  `granted_by`  INT UNSIGNED NULL DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_owner_perk` (`owner_id`, `perk_key`),
  KEY `idx_owner_status` (`owner_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL

echo "=== verify ==="
$MYSQL -e "SHOW TABLES LIKE 'owner_membership_perk_grants';"
echo "=== migration complete ==="
