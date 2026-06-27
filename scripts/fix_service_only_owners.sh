#!/bin/bash
# ตั้ง owner เป็น «บริการอย่างเดียว» — ล้าง feature_tier (ไม่ได้ฟีเจอร์ระบบ)
#
# โหมด 1 — ระบุ ID เอง:
#   SERVICE_ONLY_OWNER_IDS="12,34" bash scripts/fix_service_only_owners.sh
#
# โหมด 2 — auto จากออเดอร์ที่จ่ายแล้ว (มีแต่แพ็ก plan_kind=service):
#   AUTO_FROM_ORDERS=1 bash scripts/fix_service_only_owners.sh
#
# โหมด 3 — ไม่มีออเดอร์ paid แต่มี service+feature tier เท่ากัน (มักจาก backfill manual VIP บริการ):
#   NO_ORDERS_SAME_TIER=1 bash scripts/fix_service_only_owners.sh
#
# โหมด 4 — รวม 2+3 สำหรับ backfill ครั้งแรกหลัง deploy:
#   BACKFILL_V1=1 bash scripts/fix_service_only_owners.sh
#
# DRY_RUN=1 — แสดงรายการ ไม่ update
# FORCE=1 — ข้าม marker (ใช้เมื่อ deploy รันซ้ำโดยตั้งใจ)
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DRY_RUN="${DRY_RUN:-0}"
APP_DIR="${APP_DIR:-}"
MARKER="${SERVICE_ONLY_MARKER:-}"

if [ -z "$MARKER" ] && [ -n "$APP_DIR" ]; then
  MARKER="$APP_DIR/storage/backups/.service_only_backfill_v1"
fi

if [ -n "$MARKER" ] && [ -f "$MARKER" ] && [ "${FORCE:-0}" != "1" ] && [ -z "$SERVICE_ONLY_OWNER_IDS" ]; then
  echo "Skip: service-only backfill already applied ($MARKER). Set FORCE=1 to re-run."
  exit 0
fi

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

if [ "$BACKFILL_V1" = "1" ]; then
  AUTO_FROM_ORDERS=1
  NO_ORDERS_SAME_TIER=1
fi

TMP_IDS=$(mktemp)
trap 'rm -f "$TMP_IDS"' EXIT

append_auto_from_orders() {
  has_kind=$($MYSQL -e "SHOW COLUMNS FROM membership_plans LIKE 'plan_kind'" | wc -l | tr -d ' ')
  if [ "$has_kind" = "0" ]; then
    echo "WARN: plan_kind missing — skip AUTO_FROM_ORDERS"
    return 0
  fi
  $MYSQL <<'SQL' >> "$TMP_IDS"
SELECT DISTINCT o.id
FROM owners o
WHERE o.service_tier IN ('standard','vip')
  AND o.feature_tier IN ('standard','vip')
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
}

append_no_orders_same_tier() {
  $MYSQL <<'SQL' >> "$TMP_IDS"
SELECT o.id FROM owners o
WHERE o.service_tier IN ('standard','vip')
  AND o.feature_tier = o.service_tier
  AND (
    o.service_expires_at IS NULL OR o.service_expires_at > NOW()
    OR (o.service_grace_until IS NOT NULL AND o.service_grace_until > NOW())
  )
  AND NOT EXISTS (
    SELECT 1 FROM membership_orders mo
    WHERE mo.owner_id = o.id AND mo.status = 'paid'
  );
SQL
}

if [ -n "$SERVICE_ONLY_OWNER_IDS" ]; then
  echo "=== Mode: explicit owner IDs ==="
  echo "$SERVICE_ONLY_OWNER_IDS" | tr ',' '\n' | sed 's/[^0-9]//g' | grep -v '^$' >> "$TMP_IDS"
else
  if [ "$AUTO_FROM_ORDERS" = "1" ]; then
    echo "=== Mode: paid orders — service plans only ==="
    append_auto_from_orders
  fi
  if [ "$NO_ORDERS_SAME_TIER" = "1" ]; then
    echo "=== Mode: no paid orders, active service tier (backfill manual) ==="
    append_no_orders_same_tier
  fi
  if [ "$AUTO_FROM_ORDERS" != "1" ] && [ "$NO_ORDERS_SAME_TIER" != "1" ]; then
    echo "Usage: SERVICE_ONLY_OWNER_IDS, AUTO_FROM_ORDERS=1, NO_ORDERS_SAME_TIER=1, or BACKFILL_V1=1"
    exit 1
  fi
fi

sort -u "$TMP_IDS" -o "$TMP_IDS"
COUNT=$(wc -l < "$TMP_IDS" | tr -d ' ')

if [ "$COUNT" = "0" ]; then
  echo "No matching owners."
  if [ -n "$MARKER" ] && [ "$DRY_RUN" != "1" ] && [ "$BACKFILL_V1" = "1" ]; then
    mkdir -p "$(dirname "$MARKER")"
    touch "$MARKER"
    echo "Marker created (nothing to fix): $MARKER"
  fi
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

if [ -n "$MARKER" ]; then
  mkdir -p "$(dirname "$MARKER")"
  touch "$MARKER"
  echo "Marker: $MARKER"
fi

echo "=== Updated $COUNT owner(s) — feature tier cleared ==="
echo "Run: php cli/cron.php membership_sync_listing_boost"
echo "=== done ==="
