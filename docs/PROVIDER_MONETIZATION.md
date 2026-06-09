# Provider Monetization — แพกาญ.com

เอกสารนี้อธิบายโมเดลรายได้จาก **ผู้ให้บริการกิจกรรม** (Activity Provider) ที่ portal `/provider` และตลาด `/activities`

> **แยกจาก Owner** — เจ้าของแพใช้คูปอง/สมาชิก/โฆษณาโซน ดู [`TIER_MODEL.md`](TIER_MODEL.md)

---

## รายได้หลัก (มีอยู่แล้ว)

### ค่าคอมมิชชันจาก Voucher

เมื่อสินค้าตั้ง `booking_mode = voucher`:

| รายการ | สูตร |
|--------|------|
| รายได้แพกาญ | `commission_amount` |
| โอนให้ provider | `provider_payout` = subtotal − commission |

- ค่าเริ่มต้นสมัครเอง: **10%** (`AuthController::providerRegister`)
- Admin ปรับ % ต่อ provider ที่ `/admin/activity-providers`
- ลูกค้าโอนเข้าบัญชีแพกาญ → admin mark `paid` → แพกาญถือเงินก่อน settlement

### Lead mode

`booking_mode = lead` = ลูกค้าติดต่อ LINE/โทร — **ไม่คิดคอมอัตโนมัติ** แต่มี **lead tracking** นับคลิกแล้ว

---

## Phase 1 — Settlement + รายงาน

### Database

รัน patch (MySQL 5.7):

```
database/patches/20260522_provider_monetization_mysql57.sql
```

เพิ่มบน `activity_orders`:

- `provider_paid_at` — วันที่โอนให้ provider
- `provider_payout_ref` — เลขอ้างอิงการโอน

### Admin

- **`/admin/activity-orders`** — KPI รายได้แพกาญ / รอโอน / GMV, สรุปตาม provider, กรองเดือน
- หน้ารายละเอียดออเดอร์ — ปุ่ม «บันทึกโอนให้ provider แล้ว»
- Lead report แสดงในหน้า activity-orders (เมื่อมีข้อมูล)

### Provider

- **`/provider`** — การ์ด «รอรับจากแพกาญ» vs «โอนแล้ว»

### Workflow แนะนำ

1. Approve provider → สร้าง product → ตั้ง `booking_mode=voucher`
2. ลูกค้าซื้อ voucher + แนบสลิป
3. Admin mark `paid`
4. Provider confirm + redeem
5. Admin โอนเงิน → บันทึก settlement + ref

---

## Phase 2 — Featured กิจกรรม

### ตาราง `activity_featured_campaigns`

Admin จัดการที่ **`/admin/activity-featured`**:

- เลือกสินค้า, ราคา, ช่วงวันที่, priority boost
- เปิดใช้ → ตั้ง `is_featured=1` + `priority` บนสินค้า
- Cron **`activity_featured_expire`** ปิดแคมเปญหมดอายุ + sync สินค้า

รันจาก **`/admin/automation`** หรือ `php cli/cron.php`

---

## Phase 3 — Lead + Subscription + Gateway

### Lead tracking

- ปุ่ม LINE/โทรบนหน้า `/activities/{slug}` ผ่าน `/activities/lead/{id}?type=line|phone`
- บันทึกลง `activity_lead_clicks`
- Admin ดูสรุปใน activity-orders · Provider ดูยอดบน dashboard

### Provider subscription (manual)

- ตาราง `activity_provider_subscriptions`
- Admin กำหนดที่แก้ไข provider → แพ็ก / คอม override / featured slots
- Provider เห็นแพ็กปัจจุบันบน dashboard

### Payment gateway (slot)

- Settings → Commerce → «ใช้ Gateway กับ checkout กิจกรรม»
- Setting: `activity_checkout_gateway_enabled`
- ยังใช้ flow สลิป manual เป็นหลัก — Gateway รอเชื่อม Omise/2C2P ตาม `payment_gateway_*`

---

## KPI

| ตัวชี้วัด | ที่ดู |
|-----------|-------|
| Provider active + voucher published | `/admin/activity-providers`, `/admin/activity-products` |
| GMV / เดือน | `SUM(total_price)` สถานะ paid+ |
| รายได้แพกาญ | `SUM(commission_amount)` |
| Settlement ค้าง | `SUM(provider_payout)` WHERE `provider_paid_at IS NULL` |
| Lead volume | activity-orders (lead section) |
| Featured revenue | `/admin/activity-featured` (price_paid) |

---

## ไฟล์หลัก

| ไฟล์ | บทบาท |
|------|--------|
| `database/patches/20260522_provider_monetization_mysql57.sql` | Schema |
| `app/Models/ActivityOrder.php` | Settlement + revenue queries |
| `app/Controllers/Admin/ActivityOrderController.php` | Report + mark payout |
| `app/Controllers/Provider/DashboardController.php` | ยอดรอรับ/โอนแล้ว |
| `app/Models/ActivityFeaturedCampaign.php` | Featured campaigns |
| `app/Services/ActivityFeaturedService.php` | Sync + expire |
| `app/Services/CronService.php` | `activity_featured_expire` |
| `app/Models/ActivityLeadClick.php` | Lead tracking |
| `app/Controllers/ActivitiesController.php` | `leadClick()` redirect |
