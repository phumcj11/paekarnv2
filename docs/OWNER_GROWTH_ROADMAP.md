# Owner Growth Roadmap — สถานะโค้ดจริง

> อัปเดตล่าสุด: มิถุนายน 2569  
> เป้าหมาย: เปลี่ยน Owner Portal ให้เป็น **แพ็กเกจช่วยขายครบวงจร** — เว็บหลักหา lead, Portal จัดการจอง, LINE CRM, Automation, AI

---

## สถานะแต่ละเฟส

### เฟส 1 — Owner Analytics + Lead Tracking ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| `/owner/analytics` + Chart.js 7–90 วัน | `app/Controllers/Owner/AnalyticsController.php` |
| ตาราง `analytics_page_views`, `property_lead_clicks` | `app/Services/AnalyticsPageViewService.php` |
| ติด tracking ปุ่มโทร / LINE / จอง / **แผนที่** (map) บนเว็บหลัก | `app/Models/PropertyLeadClick.php` |
| Referrer breakdown (Facebook, direct, ฯลฯ) | `app/Controllers/Owner/AnalyticsController.php` |
| AI Weekly Lead Summary (on-demand) | `AnalyticsController::aiSummary()` → `AIService` |

**Placeholders ที่รองรับ:** `{{booking_code}}`, `{{property_name}}`, `{{unit_name}}`, `{{guest_name}}`, `{{check_in_date}}`, `{{check_out_date}}`, `{{nights}}`, `{{total_price}}`, `{{property_phone}}`, `{{review_url}}`

---

### เฟส 2 — Membership Badge + Ranking ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| Badge VIP/Standard + grace period บนเว็บหลัก | `app/Views/partials/property-card.php` |
| `MembershipListingBoostService` boost ค้นหา | `app/Services/MembershipListingBoostService.php` |
| Cron เตือนต่ออายุ 30/14/7/3 วัน + LINE | `app/Services/CronService.php` |
| Grace + downgrade ใน cron | `CronService::membershipApplyGrace()` |
| **Tier gating** ฟีเจอร์ตามแพ็กเกจ | `app/Services/OwnerTier.php` |

**Tier → Feature mapping:**

| Feature | Free | Standard | VIP |
|---------|------|----------|-----|
| Automation templates | ✅ | ✅ | ✅ |
| LINE CRM | ✅ | ✅ | ✅ |
| AI draft | ✅ | ✅ | ✅ |
| LINE Broadcast | ❌ | ✅ | ✅ |
| Analytics Deep (referrer + AI summary) | ❌ | ✅ | ✅ |
| Coupon | ❌ | ✅ | ✅ |
| Guest seek leads | ❌ | ❌ | ✅ |
| Listing boost | ❌ | ❌ | ✅ |
| Available page boost | ❌ | ❌ | ✅ |

---

### เฟส 3 — หน้าแพว่างวันนี้/เสาร์นี้ ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| `/available-today`, `/available-weekend` | `app/Controllers/AvailableController.php` |
| คำนวณว่างจริงจาก bookings + availability | `app/Services/AvailablePropertiesService.php` |
| **VIP/Standard boost** ลำดับในหน้า available | `AvailablePropertiesService::findAvailableOn()` |
| Badge **"ปฏิทินอัปเดตล่าสุด"** (อัปเดตใน 7 วัน) | `app/Views/available/index.php` |

---

### เฟส 4 — LINE CRM + Segment ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| Tag + notes ต่อ contact | `app/Controllers/Owner/LineContactController.php` |
| Broadcast กรองตาม tag | `LineContactController::broadcast()` |
| หน้า `/owner/line-contacts` | `app/Views/owner/line_contacts/index.php` |
| **ประวัติจอง** บนแถว contact (booking_count, last_booking_date, status) | `LineContactController::index()` |
| **Auto-segment** — "ทักแต่ไม่จอง", "ลูกค้าเก่า 90+ วัน" | คำนวณใน PHP หลัง query |
| Segment filter strip ใน UI | `app/Views/owner/line_contacts/index.php` |

---

### เฟส 5 — Automation Template ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| UI `/owner/automation` — 6 event types + บันทึก template | `app/Controllers/Owner/AutomationController.php` |
| ตาราง `property_message_templates` | `scripts/migrate_message_templates.sh` |
| **MessageTemplateService** — render placeholders + ส่งผ่าน LINE OA | `app/Services/MessageTemplateService.php` |
| **CronService** ใช้ template จริง — checkin_reminder_1d, checkout_followup, review_request | `app/Services/CronService.php` |
| **BookingService::confirmAndNotify** ใช้ template `booking_confirmed` (fallback = Flex) | `app/Services/BookingService.php` |
| **deposit_received** ส่งเมื่อ Owner verify payment | `app/Controllers/Owner/BookingController.php` |
| **AI Campaign จากวันว่าง** | `AutomationController::aiCampaign()` |

**Event types ที่รองรับ:**
- `booking_confirmed` — หลังยืนยันการจอง
- `deposit_received` — หลัง Owner verify สลิป
- `checkin_reminder_1d` — 1 วันก่อนเช็คอิน (cron)
- `checkout_followup` — วันเช็คเอาท์ (cron)
- `review_request` — 3 วันหลังเช็คเอาท์ (cron)
- `reengagement_30d` — ใช้ใน broadcast campaign (manual)

---

### เฟส 6 — AI ช่วยการตลาด ✅ เสร็จ

| ทำแล้ว | ไฟล์หลัก |
|--------|----------|
| AI draft ใน Automation templates | `AutomationController::aiDraft()` |
| AI draft ใน LINE broadcast | `app/Controllers/LineController.php` |
| AI ใน Content Planner | `app/Controllers/Owner/ContentPlanController.php` |
| **AI สรุป lead รายสัปดาห์** (on-demand) | `AnalyticsController::aiSummary()` |
| **AI campaign จากวันว่าง** | `AutomationController::aiCampaign()` |

---

## งานนอกแผน 6 เฟสที่ทำเพิ่มแล้ว

- **Calendar bug fix** — แสดงการจองยกเลิกบนปฏิทิน (`96107c6`)
- **Owner ลบถาวร + Audit log** — `owner_booking_deleted` สำหรับ Admin track (`8545f5c`)
- ฟีเจอร์เดิมที่มีอยู่แล้ว: Content Planner, LINE Hub ต่อที่พัก, Manual booking, Guest seek leads (VIP)

---

## Cron Jobs ที่มีอยู่

| Job | หน้าที่ |
|-----|---------|
| `expire_coupons` | คูปองหมดอายุ |
| `mark_no_show` | complete/cancel booking ที่เลยกำหนด |
| `send_checkin_reminders` | เตือนก่อนเช็คอิน (template → fallback hardcode) |
| `send_checkout_followup` | ส่ง `checkout_followup` template วันเช็คเอาท์ |
| `send_review_requests` | ส่ง `review_request` template 3 วันหลังเช็คเอาท์ |
| `owner_weekly_report` | รายงานประจำสัปดาห์ (ทุกวันจันทร์) |
| `cleanup_drafts` | ลบ property draft เก่า 60+ วัน |
| `membership_apply_grace` | ตั้ง grace period เมื่อแพ็กเกจหมด |
| `membership_downgrade` | ปรับ tier เป็น none เมื่อเกิน grace |
| `membership_warn_expiring` | แจ้งเตือนก่อนหมดอายุ (ค่าเริ่มต้น 30/7/3/1 วัน — ตั้งใน Admin Settings) |
| `membership_sync_listing_boost` | sync priority/is_featured จากแพ็กสมาชิก |
| `activity_featured_expire` | หมดอายุ featured activity |

---

## สรุปสถานะ

| หัวข้อ | สถานะ |
|--------|--------|
| เฟส 1 Analytics | ✅ ครบ — views + leads (phone/line/book/map) + referrer + AI summary |
| เฟส 2 Membership | ✅ ครบ — badge/boost/grace/tier-gating |
| เฟส 3 Available pages | ✅ ครบ — tier boost + freshness badge |
| เฟส 4 LINE CRM | ✅ ครบ — tag/broadcast/history/auto-segment |
| เฟส 5 Automation | ✅ ครบ — templates เชื่อม cron + booking จริง |
| เฟส 6 AI | ✅ ครบ — draft + weekly summary + campaign |
| Bug fixes | ✅ Calendar + Owner delete |
