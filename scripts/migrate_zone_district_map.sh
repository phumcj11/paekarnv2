#!/bin/bash
# zone_district_map — ผูกอำเภอกับโzo แนะนำ (idempotent)
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

echo "=== zone_district_map table ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `zone_district_map` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `zone_name`  VARCHAR(80) NOT NULL,
  `district`   VARCHAR(80) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_zone_district` (`zone_name`, `district`),
  KEY `idx_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL

echo "=== seed default district → zone mappings (INSERT IGNORE) ==="
$MYSQL <<'SQL'
INSERT IGNORE INTO zone_district_map (zone_name, district, sort_order) VALUES
  ('ริมแม่น้ำแคว', 'เมืองกาญจนบุรี', 1),
  ('แม่น้ำแคว', 'เมืองกาญจนบุรี', 2),
  ('แควใหญ่', 'เมืองกาญจนบุรี', 3),
  ('ริมแม่น้ำแควน้อย', 'ท่ามะกา', 1),
  ('แควน้อย', 'ท่ามะกา', 2),
  ('ริมแม่น้ำแคว', 'ท่าม่วง', 1),
  ('แม่น้ำแคว', 'ท่าม่วง', 2),
  ('แควน้อย', 'ไทรโยค', 1),
  ('ริมแม่น้ำแควน้อย', 'ไทรโยค', 2),
  ('อุทยานแห่งชาติเอราวัณ', 'ไทรโยค', 3),
  ('ไทรโยค', 'ไทรโยค', 4),
  ('เขื่อนศรีนครินทร์', 'ศรีสวัสดิ์', 1),
  ('อุทยานแห่งชาติเอราวัณ', 'ศรีสวัสดิ์', 2),
  ('ศรีสวัสดิ์', 'ศรีสวัสดิ์', 3),
  ('สังขละบุรี', 'สังขละบุรี', 1),
  ('เขื่อนวชิราลงกรณ์', 'สังขละบุรี', 2),
  ('ทองผาภูมิ', 'ทองผาภูมิ', 1);
SQL

echo "=== verify ==="
$MYSQL -e "SELECT COUNT(*) AS map_rows FROM zone_district_map;"
echo "=== migration complete ==="
