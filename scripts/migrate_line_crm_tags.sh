#!/bin/bash
# LINE CRM Tags — เพิ่ม tags column ใน property_line_contacts (idempotent)
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

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

echo "=== property_line_contacts CRM columns ==="
add_column property_line_contacts tags "TEXT NULL DEFAULT NULL COMMENT 'JSON array of tag strings'"
add_column property_line_contacts notes "TEXT NULL DEFAULT NULL COMMENT 'private notes by owner'"

echo "=== Done ==="
