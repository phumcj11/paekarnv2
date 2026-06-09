# แพกาญ — นิยาม Tier / โมเดลรายได้ (Phase 0)

เอกสารอ้างอิงภายใน · ผูกกับฟิลด์จริงในระบบ

## Tier สรุป

| แนวคิด | ฟิลด์ / พฤติกรรมหลัก |
|--------|----------------------|
| **T0 ฟรีลงชื่อ** | `properties.status = published` ได้ตามปกติ · `coupon_enabled = 0` (ค่าเริ่มต้นฝั่งเจ้าของ) · `priority` ตามค่าที่ตั้ง / ไม่มี boost สมาชิก |
| **T1 พันธมิตรคูปอง** | มีข้อตกลงแล้ว · แอดมินตั้ง `coupon_enabled = 1` และ `booking_mode` ที่เหมาะสม · แนะนำบันทึก `coupon_contract_signed_at` เมื่อมีในฐานข้อมูล |
| **T2 สมาชิก Standard** | `owners.membership_tier = standard` + สิทธิ์ยังไม่หมด · ระบบบวก `priority` ตาม `membership_boost_priority_standard` (cron `membership_sync_listing_boost` + หลังชำระแพ็ก) · เก็บ delta ใน `membership_priority_boost` |
| **T3 สมาชิก VIP** | `membership_tier = vip` + สิทธิ์ active · บวก `priority` ตาม `membership_boost_priority_vip` · ถ้า `membership_vip_auto_featured = 1` ระบบตั้ง `is_featured = 1` และทำเครื่องหมาย `membership_featured_applied = 1` เพื่อถอนคืนตอนหมดสิทธิ์ |

## รายได้หลัก (ไม่สับกับ Tier)

- **คูปองเงินสด** — placement ต่างจากแพ็กสมาชิก (แพ็ก = การเรียง/โชว์)
- **โฆษณาโซน** — ตาราง `zone_ad_campaigns` + `ZoneAdService::activeForZone()`
- **แพ็กเกจสมาชิก** — ปรับ `priority` / `is_featured` อัตโนมัติตามแถวด้านบน

## Cron ที่เกี่ยวข้อง

- `membership_downgrade` — ก่อนล้าง tier เรียกถอน boost (`membership_priority_boost` / featured จากระบบ)
- `membership_sync_listing_boost` — sync ทุก owner ที่มีสิทธิ์หรือมี residual boost

## Migration

รันไฟล์ `database/migrations/20260213_monetization_listing_boost.sql` เพื่อเพิ่มคอลัมน์และตาราง Phase 3–4

## หลังบ้านที่เกี่ยวข้อง

| เส้นทาง | หมายเหตุ |
|---------|-----------|
| `/admin/coupon-campaigns` | จัดการแถวใน `coupon_campaigns` |
| `/admin/zone-ads` | จัดการ `zone_ad_campaigns` (ชื่อโซนต้องตรง `properties.zone`) |
| `/admin/audit-logs` | ดู `audit_logs` (เช่น เปลี่ยนโหมดจอง / คูปอง / featured) |
| แก้ที่พัก (Admin) — แถบโหมดจอง | ฟิลด์ «บันทึกสัญญาร่วมคูปอง» → `coupon_contract_signed_at` |
