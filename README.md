# แพกาญ.com — Travel Booking + Voucher Platform

ระบบรวมแพพัก / รีสอร์ท / บ้านพัก / โฮมสเตย์ ในกาญจนบุรี
พร้อมระบบ **คูปองเงินสด** (Cash Voucher) เพื่อทดแทน Commission

> Pure PHP + MySQL + Tailwind CSS — Production Ready, Mobile First, Bilingual (TH/EN)

---

## 🎯 PHASE 1 + PHASE 2 + PHASE 3 (ขอบเขตของชุดโค้ดนี้)

### Phase 1
- ✅ Customer Website (Homepage, Listing, Detail, Booking, Coupon)
- ✅ Admin Dashboard (Property/Booking/Coupon/Owner/Blog/Settings)
- ✅ Coupon System (Cash Voucher 500/250)
- ✅ Authentication (Customer + Admin)
- ✅ Database Schema ครบทุกตาราง

### Phase 2 — Owner Portal ✅
- ✅ Owner Auth (login/register + redirect by role)
- ✅ Owner Dashboard (KPIs, charts, recent bookings)
- ✅ Property CRUD (รายละเอียด + กฎ + รูปภาพ + พิกัด + amenities)
- ✅ Multi-Unit CRUD (พร้อมราคาตามวัน: weekday/weekend/holiday/low/high)
- ✅ Availability Calendar (open/closed/fully_booked รายวันต่อ unit)
- ✅ Booking Management (accept/reject/complete/no-show + verify slip)
- ✅ Coupon Verification (verify code + mark used + ผูกกับ booking)
- ✅ Profile + Banking Info
- ✅ Booking Mode Control (info_only / coupon_assisted / full_booking)

### Phase 3 — Automation + AI + LINE OA ✅
- ✅ **NotificationService** (in_app + LINE + Email) + Bell dropdown ทุก layout
- ✅ **LINE OA Integration** — push API, webhook receiver, signature verify (HMAC-SHA256), LINE Login link account
- ✅ **AIService** (OpenAI-compatible: OpenAI / OpenRouter / Together / Custom)
- ✅ **AI Chatbot** "น้องแพ" (floating widget + KB-first fallback + LINE auto-reply)
- ✅ **AI Knowledge Base** (FAQ CRUD ใน admin + hit count tracking)
- ✅ **AI Tools for Owner** — generate description / rules ด้วย 1 คลิก + translate TH↔EN
- ✅ **AI Smart Search** — natural language → JSON filters → redirect ไปหน้า /properties
- ✅ **Cron Runner** + 5 automation jobs (`expire_coupons`, `mark_no_show`, `send_checkin_reminders`, `owner_weekly_report`, `cleanup_drafts`)
- ✅ **Admin Pages**: Automation Log, AI Settings, AI KB CRUD, AI Chat History, LINE Settings/Test
- ✅ Auto-notify owner เมื่อมี booking ใหม่ + admin เมื่อมีคำสั่งซื้อคูปอง

---

## 🧱 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT (Browser)                        │
│   Tailwind CSS · Alpine.js · Vanilla JS · Lucide Icons          │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTPS / SEO URL
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      WEB SERVER (Apache / Nginx)                │
│   .htaccess  →  public/index.php  (Front Controller)            │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                       APP LAYER (Pure PHP)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │   Router     │→ │  Controller  │→ │   View (PHP-tpl)    │    │
│  └──────────────┘  └──────┬───────┘  └─────────────────────┘    │
│                           ▼                                     │
│                    ┌──────────────┐                             │
│                    │    Model     │  Auth · Session · CSRF      │
│                    └──────┬───────┘  Validator · I18n · Upload  │
│                           ▼                                     │
│                    ┌──────────────┐                             │
│                    │   Database   │  PDO + Prepared Statements  │
│                    └──────┬───────┘                             │
└───────────────────────────┼─────────────────────────────────────┘
                            ▼
                    ┌──────────────┐
                    │    MySQL     │  paekan_db
                    └──────────────┘
```

### Design Pattern

- **Front Controller** + **MVC** + **PSR-4-like Autoload**
- **Repository style Models** (extends `Core\Model` + raw PDO)
- **Service classes** สำหรับ Booking / Coupon / Payment
- **Reusable Views** ผ่าน `View::partial()` และ `layouts/`

---

## 🛡️ Security

- PDO **Prepared Statements** ทุกที่ (ไม่มี string concat SQL)
- **CSRF Token** ทุกฟอร์ม (`Csrf::field()`)
- **Password Hash** ด้วย `password_hash(PASSWORD_BCRYPT)`
- **Session Hardening** — `httponly`, `samesite=Lax`, regenerate ID หลัง login
- **Rate-limiting** สำหรับ Login (basic, in-session)
- **File Upload Validator** — MIME + Extension + Size whitelist
- **Output Escaping** ผ่าน helper `e()`

---

## 🌐 SEO & i18n

- SEO Friendly URL: `/property/{slug}-{id}`, `/coupon/{slug}`, `/blog/{slug}`
- Meta Title/Description/OG ต่อหน้า (ผ่าน `View::set('meta', ...)`)
- รองรับ **TH / EN** (default = TH) — สลับด้วย `?lang=en` หรือเก็บใน session
- Sitemap-ready (route `/sitemap.xml` เพิ่มได้ใน Phase 2)

---

## 📁 Folder Structure

```
paekan_v1/
├── app/
│   ├── Config/
│   │   ├── app.php
│   │   └── database.php
│   ├── Core/
│   │   ├── Application.php       # Bootstrap
│   │   ├── Router.php
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   ├── Database.php          # PDO singleton
│   │   ├── View.php
│   │   ├── Session.php
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Upload.php
│   │   ├── I18n.php
│   │   └── Helpers.php           # global e(), url(), asset(), __()
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── PropertyController.php
│   │   ├── CouponController.php
│   │   ├── BookingController.php
│   │   ├── BlogController.php
│   │   ├── AuthController.php
│   │   ├── AccountController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── PropertyController.php
│   │       ├── BookingController.php
│   │       ├── CouponController.php
│   │       ├── OwnerController.php
│   │       ├── BlogController.php
│   │       ├── ReviewController.php
│   │       └── SettingsController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Owner.php
│   │   ├── Property.php
│   │   ├── PropertyUnit.php
│   │   ├── PropertyImage.php
│   │   ├── Amenity.php
│   │   ├── Booking.php
│   │   ├── BookingPayment.php
│   │   ├── Coupon.php
│   │   ├── CouponOrder.php
│   │   ├── Review.php
│   │   ├── BlogPost.php
│   │   ├── Lead.php
│   │   └── Setting.php
│   ├── Services/
│   │   ├── CouponService.php
│   │   ├── BookingService.php
│   │   └── PricingService.php
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── app.php           # Customer layout
│   │   │   └── admin.php         # Admin layout
│   │   ├── partials/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   ├── nav.php
│   │   │   ├── flash.php
│   │   │   ├── property-card.php
│   │   │   └── pagination.php
│   │   ├── home/index.php
│   │   ├── properties/{index,show}.php
│   │   ├── coupons/{index,checkout,success,my}.php
│   │   ├── bookings/{create,show,my}.php
│   │   ├── account/{index,bookings,coupons,favorites,reviews,profile}.php
│   │   ├── auth/{login,register,forgot}.php
│   │   ├── blog/{index,show}.php
│   │   ├── admin/
│   │   │   ├── dashboard.php
│   │   │   ├── properties/...
│   │   │   ├── bookings/...
│   │   │   ├── coupons/...
│   │   │   ├── owners/...
│   │   │   ├── blog/...
│   │   │   ├── reviews/...
│   │   │   └── settings/...
│   │   └── errors/{404,403,500}.php
│   ├── Lang/
│   │   ├── th.php
│   │   └── en.php
│   └── Routes/
│       ├── web.php
│       └── admin.php
├── database/
│   ├── schema.sql                # CREATE TABLE ทุกตาราง
│   └── seed.sql                  # ข้อมูล mockup
├── cli/
│   └── cron.php                  # Cron CLI runner (Phase 3)
├── public/                        # DocumentRoot ใน Production
│   ├── index.php                 # Front Controller
│   ├── .htaccess
│   ├── install.php               # One-shot installer
│   ├── cron.php                  # Web-trigger cron (Phase 3)
│   ├── assets/
│   │   ├── css/app.css
│   │   ├── js/app.js
│   │   └── images/               # logo, placeholders
│   └── uploads/                  # ผู้ใช้อัปโหลด (slip, photo)
├── storage/
│   ├── logs/
│   └── cache/
├── .htaccess                     # redirect → /public  (สำหรับ XAMPP root)
├── index.php                     # bootstrap fallback ที่ root
└── README.md
```

> **XAMPP local:** วางที่ `c:\xampp\htdocs\paekan_v1\` แล้วเข้า `http://localhost/paekan_v1/`
> ระบบรองรับทั้งวิธี **subdir** และ **vhost** (DocumentRoot = `public/`) โดยอัตโนมัติ

---

## 🗄️ Database Overview

| Table | คำอธิบาย |
|---|---|
| `users` | บัญชีผู้ใช้ทุก role (customer / owner / admin) |
| `customers` / `owners` | profile แยกต่อ role |
| `properties` | ข้อมูลที่พักหลัก (ชื่อ, slug, location, นโยบาย) |
| `property_units` | "หลายแพต่อ 1 ที่พัก" + ราคา weekday/weekend/holiday |
| `property_images` / `property_360_images` | gallery + 360 |
| `amenities` / `property_amenities` / `unit_amenities` | สิ่งอำนวยความสะดวก (M:N) |
| `pricing_rules` | low/high season override |
| `availability` | calendar รายวัน per unit |
| `bookings` / `booking_payments` | การจอง + slip การโอน |
| `coupons` | คูปองที่ออกแล้ว (code, value, status, expires) |
| `coupon_orders` | คำสั่งซื้อคูปอง |
| `coupon_usages` | log การใช้คูปอง |
| `reviews` | รีวิว + คะแนน |
| `blog_posts` | บทความ SEO |
| `leads` | CRM lead tracking |
| `settings` | key-value config |
| `notifications` | (Phase 3) in-app + LINE + email queue/log |
| `cron_logs` | (Phase 3) automation execution log |
| `webhook_logs` | (Phase 3) raw payload audit (LINE, ฯลฯ) |
| `ai_knowledge_base` | (Phase 3) FAQ ของ AI Chatbot |
| `ai_chats` | (Phase 3) บทสนทนากับ AI |

ER-style relations อยู่ในหัวคอมเมนต์ของ `database/schema.sql`

---

## 💰 Coupon Business Logic

```
ลูกค้าซื้อ "คูปอง 500 บาท" ในราคา 250 บาท
  └── สร้าง coupon_orders (paid)
       └── สร้าง coupons (status=unused, code=PKAN-XXXX-XXXX, expires_at)
            └── เมื่อจอง: customer ใส่ code → BookingService หัก face_value ออกจาก total
                 └── เมื่อเจ้าของแพ verify → coupon.status=used + insert coupon_usages
```

### 3 Booking Modes ต่อที่พัก

| Mode | พฤติกรรม |
|---|---|
| `info_only` | แสดงข้อมูล + ปุ่ม "ติดต่อโดยตรง" (เก็บ lead) |
| `coupon_assisted` | จองผ่านระบบ ใช้คูปองได้ ไม่บังคับชำระเงิน online |
| `full_booking` | จองเต็มรูปแบบ + อัปโหลด slip + admin/owner verify |

---

## 🚀 Installation (XAMPP / Local)

1. โคลนหรือ unzip ไปที่ `c:\xampp\htdocs\paekan_v1\`
2. เปิด **phpMyAdmin** → สร้าง database ชื่อ `paekan_db` (utf8mb4_unicode_ci)
3. Import ไฟล์ตามลำดับ:
   - `database/schema.sql`
   - `database/seed.sql`
4. แก้ไข `app/Config/database.php` ถ้าจำเป็น (default user=`root`, pass=``)
5. เปิดเบราว์เซอร์ → `http://localhost/paekan_v1/`
6. Login ตัวอย่าง:
   - **Admin:** `admin@paekan.com` / `admin1234`
   - **Customer:** `customer@paekan.com` / `customer1234`
   - **Owner:** `owner@paekan.com` / `owner1234`

> ต้องเปิด `mod_rewrite` ใน Apache (XAMPP เปิดเป็น default)

---

## 🌍 Deploy to Hosting / VPS

- ตั้ง **DocumentRoot = `public/`** (ป้องกันเข้าไฟล์ app/)
- คัดลอก `app/`, `database/`, `storage/` ไว้ **เหนือ** webroot ได้
- ตั้ง permission `storage/`, `public/uploads/` เป็น `775`
- เปลี่ยน `APP_ENV=production`, `APP_DEBUG=false` ใน `app/Config/app.php`
- ใช้ HTTPS + ตั้ง `session.cookie_secure = 1`

---

## 🤖 Phase 3 Setup (Automation / AI / LINE)

### 1) Cron Setup
รันทุกๆ 1 นาที (หรือ 5 นาที) เพื่อให้ automation jobs ทำงาน:

**Linux/cPanel:**
```
* * * * *   /usr/bin/php /home/USER/paekan_v1/cli/cron.php >> /tmp/paekan-cron.log 2>&1
```

**Windows Task Scheduler:** สร้าง Task รัน `C:\xampp\php\php.exe c:\xampp\htdocs\paekan_v1\cli\cron.php` ทุก 1 นาที

**Web Trigger (แชร์โฮสต์ที่ไม่มี CLI):**
```
https://yoursite.com/cron.php?key=YOUR_SECRET
```
เปลี่ยน `cron_secret` ใน `/admin/settings`

### 2) AI Setup
1. ไปที่ `/admin/ai`
2. เปิดสวิตช์ **เปิดใช้งาน AI**
3. เลือก Provider (OpenAI / OpenRouter / Together / Custom)
4. ใส่ API Key + Model (เช่น `gpt-4o-mini`)
5. ทดสอบที่ panel ด้านขวา → ส่งคำถามตัวอย่าง

### 3) LINE OA Setup
1. สร้าง LINE Official Account → Messaging API → ได้ Channel Access Token + Secret
2. (option) สร้าง LINE Login Channel → ได้ Channel ID + Secret สำหรับให้ user link account
3. ไปที่ `/admin/line` → ใส่ token + เปิดสวิตช์ + บันทึก
4. **คัดลอก Webhook URL** จากหน้า admin → ใส่ใน LINE Developers Console
5. ทดสอบส่งจาก panel ด้านขวา

### 4) Notification Channels
- **In-app** (Bell icon) — ทำงานเสมอ
- **LINE** — ส่งเมื่อ user ผูก LINE แล้ว และ `notify_line=1`
- **Email** — เปิดใน settings เพิ่มเติม (ใช้ PHP `mail()` หรือ SMTP plug-in ภายหลัง)

---

## 🎨 Brand Mood & Tone

| Token | สี | ใช้กับ |
|---|---|---|
| `primary`  (Slate Blue) | `#475C7A` / scale 50–900 | Header, CTA, Highlight |
| `secondary` (Cloud White) | `#F8FAFC` | พื้นหลัง, การ์ด |
| `accent` (Teal Green) | `#14B8A6` | Badge, Sale, Hover |
| `ink` (Text) | `#0F172A` | ตัวอักษร |

UI: Mobile First · Tailwind (CDN) + custom config inline · Lucide Icons (CDN)

---

## 🗺️ Roadmap

- **Phase 1 ✅** — Customer + Admin + Coupon (โค้ดชุดนี้)
- **Phase 2** — Owner Portal: Dashboard, Property/Unit CRUD, Calendar, Coupon Verify
- **Phase 3** — Automation: LINE OA Webhook, n8n flow, AI auto-reply, Email reminder

---

© 2026 แพกาญ.com — All Rights Reserved
