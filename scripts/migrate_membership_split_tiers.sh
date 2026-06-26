#!/bin/bash
# แยก tier สมาชิก: บริการ (service) vs ฟีเจอร์ในระบบ (feature) — idempotent
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

add_col() {
  local table="$1" col="$2" def="$3"
  if ! $MYSQL -N -e "SHOW COLUMNS FROM \`${table}\` LIKE '${col}'" | grep -q .; then
    echo "  + ${table}.${col}"
    $MYSQL -e "ALTER TABLE \`${table}\` ADD COLUMN \`${col}\` ${def}"
  else
    echo "  = ${table}.${col} (exists)"
  fi
}

echo "=== owners: service + feature tiers ==="
add_col owners service_tier "ENUM('none','standard','vip') NOT NULL DEFAULT 'none' AFTER membership_grace_until"
add_col owners feature_tier "ENUM('none','standard','vip') NOT NULL DEFAULT 'none' AFTER service_tier"
add_col owners service_expires_at "DATETIME NULL DEFAULT NULL AFTER feature_tier"
add_col owners feature_expires_at "DATETIME NULL DEFAULT NULL AFTER service_expires_at"
add_col owners service_grace_until "DATETIME NULL DEFAULT NULL AFTER feature_expires_at"
add_col owners feature_grace_until "DATETIME NULL DEFAULT NULL AFTER service_grace_until"

echo "=== backfill from legacy membership_* ==="
$MYSQL <<'SQL'
UPDATE owners SET
  service_tier = CASE WHEN membership_tier IN ('standard','vip') THEN membership_tier ELSE 'none' END,
  feature_tier = CASE WHEN membership_tier IN ('standard','vip') THEN membership_tier ELSE 'none' END,
  service_expires_at = membership_expires_at,
  feature_expires_at = membership_expires_at,
  service_grace_until = membership_grace_until,
  feature_grace_until = membership_grace_until
WHERE service_tier = 'none' AND feature_tier = 'none'
  AND membership_tier IN ('standard','vip');
SQL

echo "=== membership_plans: plan_kind ==="
add_col membership_plans plan_kind "ENUM('service','features','bundle') NOT NULL DEFAULT 'bundle' AFTER tier"

echo "=== verify ==="
$MYSQL -e "SHOW COLUMNS FROM owners LIKE '%tier%'; SHOW COLUMNS FROM membership_plans LIKE 'plan_kind';"
echo "=== migration complete ==="
