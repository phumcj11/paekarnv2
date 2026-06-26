#!/bin/bash
# =============================================================================
# อัปเดต settings ใน DB จาก paekarn.com → paekan.com
#
# วิธีใช้บน VPS:
#   export DB_NAME='paekarnv2_db'
#   export DB_USER='paekarnv2_user'
#   export DB_PASS='...'
#   bash scripts/migrate_domain_settings.sh
# =============================================================================
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-localhost}"
NEW_URL="https://paekan.com"
NEW_EMAIL_FROM="no-reply@paekan.com"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "ERROR: Set DB_NAME, DB_USER, DB_PASS ก่อนรัน"
  exit 1
fi

MYSQL_CMD="mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME"

echo "=== อัปเดต settings.app_url ==="
$MYSQL_CMD -e "
  INSERT INTO settings (\`key\`, \`value\`)
  VALUES ('app_url', '$NEW_URL')
  ON DUPLICATE KEY UPDATE \`value\` = '$NEW_URL';
"
echo "  ✓ app_url → $NEW_URL"

echo "=== อัปเดต settings.email_from ==="
$MYSQL_CMD -e "
  INSERT INTO settings (\`key\`, \`value\`)
  VALUES ('email_from', '$NEW_EMAIL_FROM')
  ON DUPLICATE KEY UPDATE \`value\` = '$NEW_EMAIL_FROM';
"
echo "  ✓ email_from → $NEW_EMAIL_FROM"

echo ""
echo "=== ตรวจสอบค่าที่บันทึก ==="
$MYSQL_CMD -e "SELECT \`key\`, \`value\` FROM settings WHERE \`key\` IN ('app_url', 'email_from');"

echo ""
echo "✓ migrate_domain_settings เสร็จสิ้น"
