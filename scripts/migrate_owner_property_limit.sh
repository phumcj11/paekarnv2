#!/bin/bash
# เพิ่ม max_properties column ใน owners เพื่อควบคุมโควต้าจำนวนที่พักต่อ owner — idempotent
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

add_column() {
  local table="$1" column="$2" definition="$3"
  local exists
  exists=$($MYSQL -Nse "
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = '${column}'
  ")
  if [ "$exists" = "0" ]; then
    echo "  + ${table}.${column}"
    $MYSQL -e "ALTER TABLE \`${table}\` ADD COLUMN \`${column}\` ${definition}"
  else
    echo "  = ${table}.${column} (exists)"
  fi
}

echo "=== owners: max_properties column ==="
add_column owners max_properties "INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'จำนวนที่พักสูงสุดที่ owner สร้างเองได้ (admin ปรับได้)'"

echo "=== backfill: set max_properties = 1 for all existing owners ==="
$MYSQL -e "UPDATE owners SET max_properties = 1 WHERE max_properties != 1;"
echo "  done."

echo "=== verify ==="
$MYSQL -e "SHOW COLUMNS FROM owners LIKE 'max_properties';"
echo "=== migration complete ==="
