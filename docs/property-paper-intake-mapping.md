# แมปแบบฟอร์มกระดาษเจ้าของแพ → ฐานข้อมูลแพกาญ

เอกสารนี้เป็นแม่แบบการคีย์และพัฒนา UI ให้สอดคล้องกับฟอร์มที่เจ้าของแพกรอกในกระดาษ

## สรุปตารางหลัก

| หัวข้อในฟอร์ม | ที่เก็บในระบบ |
|----------------|---------------|
| ชื่อที่พัก | `properties.name`, `name_en` |
| ประเภทที่พัก | `properties.type` |
| แพริมน้ำ / แพลาก | `properties.raft_variant` (`shore` / `towed`) เมื่อ `type = raft` |
| โซน / ที่อยู่ / พิกัด | `zone`, `district`, `province`, `address`, `latitude`, `longitude` |
| จำนวนคน / หลายหลัง | `property_units` (capacity_min/max, bedrooms, bathrooms, ราคา ฯลฯ) |
| สิ่งอำนวยความสะดวก (ติ๊ก) | `amenities` + `property_amenities` / `unit_amenities` |
| เบอร์โทร / LINE / Facebook | `phone`, `line_id`, `facebook_url` |
| Email / Website | `contact_email`, `website_url` |
| Note / รายละเอียดทั่วไป | `description` |
| Check-in / Check-out | `check_in`, `check_out` |
| สัตว์เลี้ยง | `pet_policy` (+ รายละเอียดเพิ่มใน `owner_intake.pets_note` ได้) |
| ระเบียบ / คาราโอเกะ / มัดจำ | `rules`, `deposit_amount`, `deposit_note` |
| ราคาโลว์–ไฮ | `property_units.price_low`, `price_high` และฟิลด์ราคาอื่นในยูนิต |

## JSON `owner_intake` (คำถาม FAQ แบบมีโครงสร้าง)

เก็บเป็น JSON object ชื่อคีย์คงที่ดังนี้ (ค่าเป็น string ภาษาไทยจากเจ้าของแพ):

| Key | คำถามอ้างอิงจากแบบฟอร์ม |
|-----|-------------------------|
| `group_packages` | หมู่คณะ — ราคา / Package |
| `day_trip_no_overnight` | ไม่ค้างคืน / ล่องแพอย่างเดียว — มีบริการและราคา |
| `activities_pricing` | เครื่องเล่น / กิจกรรม และราคา |
| `seasonal_note` | Low / High season เพิ่มเติม (นอกเหนือราคาในยูนิต) |
| `whole_house_extra` | เหมาหลัง — ค่าใช้จ่ายเพิ่มนอกราคาแพ |
| `whole_house_food` | เหมาหลัง — สั่งอาหารได้หรือไม่ |
| `child_policy` | ราคาเด็ก |
| `pets_note` | รายละเอียดสัตว์เลี้ยงเพิ่มเติม (ถ้ามี) |

คีย์ที่ไม่มีใน JSON หรือเป็นสตริงว่าง = ไม่แสดงใน UI

## ฟอร์มเจ้าของแพ

หน้าเพิ่ม/แก้ไขที่พัก (`owner/properties/form`) มีส่วน:

- ประเภทแพ (แพริมน้ำ / แพลาก) เมื่อเลือกประเภท «แพพัก»
- E-mail / เว็บไซต์
- หัวข้อ FAQ แต่ละช่องสอดคล้างตารางคีย์ด้านบน

หลังบันทึก ระบบรวมช่องเหล่านี้เป็น `owner_intake` JSON อัตโนมัติ

## อัปเกรดฐานข้อมูลที่มีอยู่แล้ว

ติดตั้งใหม่ผ่าน `public/install.php` จะได้ `database/schema.sql` ที่มีคอลัมน์ครบแล้ว

ถ้าเป็นโปรเจกต์ที่สร้างจาก schema เก่า ให้รัน SQL ใน [database/patches/20260203_property_paper_intake.sql](database/patches/20260203_property_paper_intake.sql) ครั้งหนึ่ง (ถ้าคอลัมน์มีอยู่แล้วให้ข้ามคำสั่งที่ error)
