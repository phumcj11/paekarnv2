#!/bin/bash
# เพิ่ม phone column ใน property_line_contacts สำหรับ auto-match จองกับ LINE — idempotent
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

add_index() {
  local table="$1" index_name="$2" columns="$3"
  local exists
  exists=$($MYSQL -Nse "
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND INDEX_NAME = '${index_name}'
  ")
  if [ "$exists" = "0" ]; then
    echo "  + index ${table}.${index_name}"
    $MYSQL -e "ALTER TABLE \`${table}\` ADD INDEX \`${index_name}\` (${columns})"
  else
    echo "  = index ${table}.${index_name} (exists)"
  fi
}

echo "=== property_line_contacts: phone column ==="
add_column property_line_contacts phone "VARCHAR(30) NULL DEFAULT NULL COMMENT 'เบอร์โทรที่ลูกค้าส่งมาทางแชท'"
add_index  property_line_contacts idx_plc_phone "(phone)"

echo "=== verify ==="
$MYSQL -e "SHOW COLUMNS FROM property_line_contacts LIKE 'phone';"
echo "=== migration complete ==="
