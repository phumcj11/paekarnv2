#!/bin/bash
# ตั้ง owner เป็น «บริการอย่างเดียว» — ล้าง feature_tier (ไม่ได้ฟีเจอร์ระบบ)
#
# โหมด 1 — ระบุ ID เอง:
#   SERVICE_ONLY_OWNER_IDS="12,34" bash scripts/fix_service_only_owners.sh
#
# โหมด 2 — auto จากออเดอร์ที่จ่ายแล้ว (มีแต่แพ็ก plan_kind=service):
#   AUTO_FROM_ORDERS=1 bash scripts/fix_service_only_owners.sh
#
# โหมด 3 — ทุก owner ที่ service ใช้งานอยู่แต่ feature = tier เดิมจาก backfill (ระวัง — ใช้ DRY_RUN ก่อน):
#   CLEAR_FEATURE_FOR_ACTIVE_SERVICE=1 bash scripts/fix_service_only_owners.sh
#
# DRY_RUN=1 — แสดงรายการ ไม่ update
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DRY_RUN="${DRY_RUN:-0}"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "ERROR: Set DB_NAME, DB_USER, and DB_PASS before running this script."
  exit 1
fi

export MYSQL_PWD="$DB_PASS"
MYSQL="mysql -N -u${DB_USER} ${DB_NAME}"

has_split=$($MYSQL -e "SHOW COLUMNS FROM owners LIKE 'feature_tier'" | wc -l | tr -d ' ')
if [ "$has_split" = "0" ]; then
  echo "ERROR: Run migrate_membership_split_tiers.sh first."
  exit 1
fi

TMP_IDS=$(mktemp)
trap 'rm -f "$TMP_IDS"' EXIT

if [ -n "$SERVICE_ONLY_OWNER_IDS" ]; then
  echo "=== Mode: explicit owner IDs ==="
  echo "$SERVICE_ONLY_OWNER_IDS" | tr ',' '\n' | sed 's/[^0-9]//g' | grep -v '^$' >> "$TMP_IDS"
elif [ "$AUTO_FROM_ORDERS" = "1" ]; then
  echo "=== Mode: paid orders — service plans only ==="
  has_kind=$($MYSQL -e "SHOW COLUMNS FROM membership_plans LIKE 'plan_kind'" | wc -l | tr -d ' ')
  if [ "$has_kind" = "0" ]; then
    echo "ERROR: plan_kind column missing — run migrate_membership_split_tiers.sh"
    exit 1
  fi
  $MYSQL <<'SQL' >> "$TMP_IDS"
SELECT DISTINCT o.id
FROM owners o
WHERE o.service_tier IN ('standard','vip')
  AND EXISTS (
    SELECT 1 FROM membership_orders mo
    JOIN membership_plans mp ON mp.id = mo.plan_id
    WHERE mo.owner_id = o.id AND mo.status = 'paid' AND mp.plan_kind = 'service'
  )
  AND NOT EXISTS (
    SELECT 1 FROM membership_orders mo
    JOIN membership_plans mp ON mp.id = mo.plan_id
    WHERE mo.owner_id = o.id AND mo.status = 'paid'
      AND mp.plan_kind IN ('features','bundle')
  );
SQL
elif [ "$CLEAR_FEATURE_FOR_ACTIVE_SERVICE" = "1" ]; then
  echo "=== Mode: all owners with active service tier (clears feature) ==="
  $MYSQL <<'SQL' >> "$TMP_IDS"
SELECT o.id FROM owners o
WHERE o.service_tier IN ('standard','vip')
  AND o.feature_tier IN ('standard','vip')
  AND (
    o.service_expires_at IS NULL OR o.service_expires_at > NOW()
    OR (o.service_grace_until IS NOT NULL AND o.service_grace_until > NOW())
  );
SQL
else
  echo "Usage: set SERVICE_ONLY_OWNER_IDS, AUTO_FROM_ORDERS=1, or CLEAR_FEATURE_FOR_ACTIVE_SERVICE=1"
  exit 1
fi

sort -u "$TMP_IDS" -o "$TMP_IDS"
COUNT=$(wc -l < "$TMP_IDS" | tr -d ' ')

if [ "$COUNT" = "0" ]; then
  echo "No matching owners."
  exit 0
fi

echo "Owners to fix ($COUNT):"
$MYSQL -e "
SELECT o.id, u.name, u.email, o.service_tier, o.feature_tier
FROM owners o
JOIN users u ON u.id = o.user_id
WHERE o.id IN ($(paste -sd, "$TMP_IDS"))
ORDER BY o.id;
"

if [ "$DRY_RUN" = "1" ]; then
  echo "DRY_RUN=1 — no changes applied."
  echo "Then run: php cli/cron.php membership_sync_listing_boost"
  exit 0
fi

IDS=$(paste -sd, "$TMP_IDS")
$MYSQL <<SQL
UPDATE owners SET
  feature_tier = 'none',
  feature_expires_at = NULL,
  feature_grace_until = NULL,
  membership_tier = 'none',
  membership_expires_at = NULL,
  membership_grace_until = NULL
WHERE id IN ($IDS);
SQL

echo "=== Updated $COUNT owner(s) — feature tier cleared ==="
echo "Run on server: php cli/cron.php membership_sync_listing_boost"
echo "=== done ==="
