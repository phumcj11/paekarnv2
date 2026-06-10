<?php
namespace App\Services;

use App\Core\Database;

/**
 * LINE Chatbot ต่อที่พัก — Phase 1 (FAQ) + Phase 2 (เช็กวันว่าง)
 *
 * เรียกจาก LineController::propertyWebhook() เมื่อได้ event type=message
 * ตอบกลับผ่าน PropertyLineService::reply() ด้วย per-property token
 */
class PropertyLineBotService
{
    /** Intent constants */
    private const INTENT_GREETING    = 'greeting';
    private const INTENT_PRICE       = 'price';
    private const INTENT_CHECKIN     = 'checkin_time';
    private const INTENT_LOCATION    = 'location';
    private const INTENT_AMENITIES   = 'amenities';
    private const INTENT_PETS        = 'pets';
    private const INTENT_CONTACT     = 'contact';
    private const INTENT_BOOKING_HOW = 'booking_how';
    private const INTENT_AVAIL       = 'availability';
    private const INTENT_FALLBACK    = 'fallback';

    /** Thai month abbreviation → month number */
    private const THAI_MONTHS = [
        'ม\.ค\.'    => 1,  'มกราคม'      => 1,
        'ก\.พ\.'    => 2,  'กุมภาพันธ์'   => 2,
        'มี\.ค\.'   => 3,  'มีนาคม'       => 3,
        'เม\.ย\.'   => 4,  'เมษายน'       => 4,
        'พ\.ค\.'    => 5,  'พฤษภาคม'      => 5,
        'มิ\.ย\.'   => 6,  'มิถุนายน'     => 6,
        'ก\.ค\.'    => 7,  'กรกฎาคม'      => 7,
        'ส\.ค\.'    => 8,  'สิงหาคม'      => 8,
        'ก\.ย\.'    => 9,  'กันยายน'      => 9,
        'ต\.ค\.'    => 10, 'ตุลาคม'       => 10,
        'พ\.ย\.'    => 11, 'พฤศจิกายน'    => 11,
        'ธ\.ค\.'    => 12, 'ธันวาคม'      => 12,
    ];

    private const TYPE_LABELS = [
        'raft'      => 'แพพัก',
        'resort'    => 'รีสอร์ท',
        'homestay'  => 'โฮมสเตย์',
        'house'     => 'บ้านพัก',
        'pool_villa'=> 'พูลวิลล่า',
        'hotel'     => 'โรงแรม',
        'camping'   => 'แคมป์ปิ้ง',
    ];

    /**
     * Phase 3C — รับ postback (datetimepicker จาก Quick Reply / Rich Menu)
     * $data  = postback data string เช่น "avail_date"
     * $params = postback.params เช่น ["date" => "2026-06-15"]
     */
    public static function handlePostback(int $propertyId, string $replyToken, string $data, array $params): bool
    {
        $property = Database::fetch(
            "SELECT id, name, type, zone, district, province, address, phone, line_id,
                    check_in, check_out, pet_policy, description, latitude, longitude,
                    facebook_url, website_url
             FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        if (!$property) return false;

        $units = Database::fetchAll(
            "SELECT id, name, price, price_weekend, capacity_min, capacity_max,
                    bedrooms, bathrooms, total_units, extra_person_fee
             FROM property_units WHERE property_id = :p AND is_active = 1 ORDER BY price ASC",
            ['p' => $propertyId]
        );

        // date picker — avail_date
        if ($data === 'avail_date' && !empty($params['date'])) {
            $dateStr  = $params['date'];          // "2026-06-15"
            $checkIn  = new \DateTime($dateStr);
            $checkOut = (clone $checkIn)->modify('+1 day');

            $available = self::queryAvailableUnits($propertyId, $checkIn, $checkOut, 1);
            $label     = self::thaiDateShort($checkIn) . ' – ' . self::thaiDateShort($checkOut) . ' (1 คืน)';

            if (empty($available)) {
                $msgs = [self::txtMsg("📅 {$label}\nเสียใจด้วย ไม่มียูนิตว่างในวันที่เลือก\nลองเลือกวันอื่นได้เลยครับ")];
            } else {
                $lines = ["📅 {$label}\nยูนิตที่ว่าง:"];
                foreach ($available as $u) {
                    $price = self::calcPrice($u, $checkIn);
                    $lines[] = "• {$u['name']} — ฿" . number_format($price) . '/คืน'
                        . ' (รับ ' . $u['capacity_min'] . '–' . $u['capacity_max'] . ' คน)';
                }
                $lines[] = "\nสอบถามเพิ่มเติมหรือต้องการจอง ทักเลยครับ";
                $msgs = [self::txtMsg(implode("\n", $lines))];
            }

            // Quick Reply หลังผลลัพธ์
            $qr = ['items' => [
                self::qrDatepicker('📅 เลือกวันอื่น', 'avail_date'),
                self::qrText('💰 ราคา', 'ราคาเท่าไหร่'),
                self::qrText('📞 ติดต่อ', 'เบอร์โทร'),
            ]];
            $last = array_pop($msgs);
            $last['quickReply'] = $qr;
            $msgs[] = $last;

            return PropertyLineService::reply($propertyId, $replyToken, $msgs);
        }

        // fallback postback
        return PropertyLineService::reply($propertyId, $replyToken, [
            self::txtMsg("ได้รับข้อมูลแล้วครับ ✅"),
        ]);
    }

    /**
     * Entry point: อ่านข้อความ, ตัดสิน intent, ส่ง reply กลับลูกค้า
     * @return bool true ถ้า reply สำเร็จ
     */
    public static function handle(int $propertyId, string $replyToken, string $text): bool
    {
        $property = Database::fetch(
            "SELECT id, name, type, zone, district, province, address, phone, line_id,
                    check_in, check_out, pet_policy, description, latitude, longitude,
                    facebook_url, website_url
             FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        if (!$property) return false;

        $units = Database::fetchAll(
            "SELECT id, name, price, price_weekend, capacity_min, capacity_max,
                    bedrooms, bathrooms, total_units, extra_person_fee
             FROM property_units
             WHERE property_id = :p AND is_active = 1
             ORDER BY price ASC",
            ['p' => $propertyId]
        );

        $intent = self::detectIntent($text);

        $messages = match ($intent) {
            self::INTENT_GREETING    => [self::txtMsg(self::buildGreeting($property))],
            self::INTENT_PRICE       => [self::txtMsg(self::buildPrice($property, $units))],
            self::INTENT_CHECKIN     => [self::txtMsg(self::buildCheckinTime($property))],
            self::INTENT_LOCATION    => [self::txtMsg(self::buildLocation($property))],
            self::INTENT_AMENITIES   => self::buildAmenitiesMsgs($propertyId, $property, $units),
            self::INTENT_PETS        => [self::txtMsg(self::buildPets($property))],
            self::INTENT_CONTACT     => [self::txtMsg(self::buildContact($property))],
            self::INTENT_BOOKING_HOW => [self::txtMsg(self::buildBookingHow($property))],
            self::INTENT_AVAIL       => self::buildAvailabilityMsgs($propertyId, $property, $units, $text),
            default                  => [self::txtMsg(self::buildFallback($property))],
        };

        // ติด Quick Reply ใน intent ที่เหมาะสม
        $qr = self::quickReplyFor($intent);
        if ($qr && !empty($messages)) {
            $last = array_pop($messages);
            $last['quickReply'] = $qr;
            $messages[] = $last;
        }

        $ok = PropertyLineService::reply($propertyId, $replyToken, $messages);
        if (!$ok) {
            error_log("[Paekarn] PropertyLineBot reply FAIL property={$propertyId} intent={$intent}");
        }
        return $ok;
    }

    /**
     * Quick Reply buttons ตาม intent
     * @return array|null LINE quickReply object
     */
    private static function quickReplyFor(string $intent): ?array
    {
        $all = [
            self::qrText('💰 ราคา', 'ราคาเท่าไหร่'),
            self::qrText('📅 ว่างไหม', 'เสาร์นี้ ว่างไหม'),
            self::qrText('📅 เสาร์ทั้งเดือน', 'เดือนนี้ เสาร์ไหน ว่างบ้าง'),
            self::qrText('📍 ที่อยู่', 'ที่อยู่'),
            self::qrText('🕐 เช็คอิน', 'เช็คอินกี่โมง'),
            self::qrText('📞 ติดต่อ', 'เบอร์โทร'),
        ];

        $avail = [
            self::qrDatepicker('📅 เลือกวันเช็คอิน', 'avail_date'),
            self::qrText('เสาร์นี้', 'เสาร์นี้ ว่างไหม'),
            self::qrText('พรุ่งนี้', 'พรุ่งนี้ ว่างไหม'),
            self::qrText('เสาร์ทั้งเดือน', 'เดือนนี้ เสาร์ไหน ว่างบ้าง'),
            self::qrText('💰 ราคา', 'ราคาเท่าไหร่'),
        ];

        return match ($intent) {
            self::INTENT_GREETING    => ['items' => $all],
            self::INTENT_FALLBACK    => ['items' => $all],
            self::INTENT_PRICE       => ['items' => [
                self::qrDatepicker('📅 เลือกวันเช็คอิน', 'avail_date'),
                self::qrText('เสาร์นี้ ว่างไหม', 'เสาร์นี้ ว่างไหม'),
                self::qrText('📍 ที่อยู่', 'ที่อยู่'),
                self::qrText('📞 ติดต่อ', 'เบอร์โทร'),
            ]],
            self::INTENT_AVAIL       => ['items' => $avail],
            self::INTENT_LOCATION    => ['items' => [
                self::qrText('💰 ราคา', 'ราคาเท่าไหร่'),
                self::qrDatepicker('📅 เลือกวันเช็คอิน', 'avail_date'),
                self::qrText('📞 ติดต่อ', 'เบอร์โทร'),
            ]],
            self::INTENT_CONTACT     => ['items' => [
                self::qrDatepicker('📅 เลือกวันเช็คอิน', 'avail_date'),
                self::qrText('💰 ราคา', 'ราคาเท่าไหร่'),
                self::qrText('📍 ที่อยู่', 'ที่อยู่'),
            ]],
            default => null,
        };
    }

    private static function qrText(string $label, string $text): array
    {
        return [
            'type'   => 'action',
            'action' => ['type' => 'message', 'label' => mb_substr($label, 0, 20), 'text' => $text],
        ];
    }

    private static function qrDatepicker(string $label, string $dataKey): array
    {
        return [
            'type'   => 'action',
            'action' => [
                'type'   => 'datetimepicker',
                'label'  => mb_substr($label, 0, 20),
                'data'   => $dataKey,
                'mode'   => 'date',
                'initial'=> date('Y-m-d', strtotime('+1 day')),
                'min'    => date('Y-m-d'),
                'max'    => date('Y-m-d', strtotime('+6 month')),
            ],
        ];
    }

    // =========================================================
    // INTENT DETECTION
    // =========================================================

    private static function detectIntent(string $text): string
    {
        $t = mb_strtolower($text, 'UTF-8');

        // Availability — highest priority (มีวันที่หรือคำถามเรื่องห้องว่าง)
        if (
            self::hasDatePattern($t) ||
            preg_match('/ว่าง|มีห้อง|มีแพ|จะจอง|อยากจอง|ต้องการจอง|check.*avail|avail/u', $t)
        ) {
            return self::INTENT_AVAIL;
        }

        // Greeting
        if (preg_match('/สวัสดี|หวัดดี|ดีครับ|ดีค่ะ|hello|hi\b|หวัดดีครับ|ดีจ้า/u', $t)) {
            return self::INTENT_GREETING;
        }

        // Price
        if (preg_match('/ราคา|เท่าไหร่|ค่าห้อง|คืนละ|ค่าเช่า|ค่าที่พัก|ค่าแพ|งบ|แพงไหม|ถูกไหม/u', $t)) {
            return self::INTENT_PRICE;
        }

        // Check-in / Check-out time
        if (preg_match('/เช็คอิน|เช็คเอาท์|check.?in|check.?out|กี่โมง|เวลา.*เข้า|เวลา.*ออก|เช็คอิน|เช็คเอ้าท์/u', $t)) {
            return self::INTENT_CHECKIN;
        }

        // Location
        if (preg_match('/ที่อยู่|อยู่ที่ไหน|อยู่แถวไหน|แผนที่|เส้นทาง|gps|google.*map|map|ทางไป|ไปยัง|เดินทาง|จะไป/u', $t)) {
            return self::INTENT_LOCATION;
        }

        // Amenities / facilities
        if (preg_match('/สิ่งอำนวย|มีอะไรบ้าง|สระ|แอร์|wifi|wi-fi|อินเทอร์เน็ต|จอดรถ|อาหาร|ครัว|เครื่องปรับ|ฟรี.*บริการ|บริการ.*ฟรี/u', $t)) {
            return self::INTENT_AMENITIES;
        }

        // Pets
        if (preg_match('/สัตว์เลี้ยง|หมา|แมว|สุนัข|น้องหมา|น้องแมว|เอาหมา|เอาแมว/u', $t)) {
            return self::INTENT_PETS;
        }

        // Contact
        if (preg_match('/โทร|เบอร์|ติดต่อ|call|tel\b|phone|เบอร์โทร/u', $t)) {
            return self::INTENT_CONTACT;
        }

        // How to book
        if (preg_match('/จองอย่างไร|วิธีจอง|จองได้|จองยังไง|จองเลย|ขั้นตอน.*จอง|จอง.*ยังไง/u', $t)) {
            return self::INTENT_BOOKING_HOW;
        }

        return self::INTENT_FALLBACK;
    }

    /** ตรวจว่ามี date pattern ในข้อความ */
    private static function hasDatePattern(string $t): bool
    {
        if (self::parseRelativeDates($t) !== null) return true;
        // Thai months
        foreach (self::THAI_MONTHS as $pat => $_) {
            if (preg_match('/' . $pat . '/u', $t)) return true;
        }
        // numeric dd/mm or dd-mm
        if (preg_match('/\d{1,2}[\/]\d{1,2}/u', $t)) return true;
        return false;
    }

    // =========================================================
    // PHASE 1: FAQ REPLIES
    // =========================================================

    private static function buildGreeting(array $p): string
    {
        $typeLbl = self::TYPE_LABELS[$p['type'] ?? ''] ?? 'ที่พัก';
        $name    = $p['name'];
        return "สวัสดีค่ะ ยินดีต้อนรับสู่ {$name} 🌊\n"
             . "เราเป็น{$typeLbl}ใน" . ($p['zone'] ?? $p['district'] ?? 'กาญจนบุรี') . "ค่ะ\n\n"
             . "สอบถามได้เลยนะคะ เช่น\n"
             . "• \"ราคาเท่าไหร่\" — ดูราคาห้อง\n"
             . "• \"เดือนนี้ เสาร์ไหน ว่างบ้าง\" — ดูวันเสาร์ว่างทั้งเดือน\n"
             . "• \"15-16 มิ.ย. ว่างไหม 4 คน\" — เช็กวันว่าง\n"
             . "• \"เช็คอินกี่โมง\" — เวลาเข้าพัก\n"
             . "• \"ที่อยู่\" — แผนที่ / เส้นทาง\n"
             . "• \"มีอะไรบ้าง\" — สิ่งอำนวยความสะดวก";
    }

    private static function buildPrice(array $p, array $units): string
    {
        if (empty($units)) {
            return "ราคาห้องพัก {$p['name']} 🏕️\n\n"
                 . "กรุณาติดต่อสอบถามราคาโดยตรงค่ะ\n"
                 . self::contactLine($p);
        }

        $lines = ["ราคาห้องพัก {$p['name']} 🏕️\n"];
        foreach ($units as $u) {
            $price    = number_format((int)$u['price']);
            $weekend  = $u['price_weekend'] > 0 ? number_format((int)$u['price_weekend']) : null;
            $capMin   = (int)$u['capacity_min'];
            $capMax   = (int)$u['capacity_max'];
            $bed      = (int)$u['bedrooms'];

            $line = "🛏 {$u['name']}\n";
            $line .= "   ฿{$price}/คืน (วันธรรมดา)";
            if ($weekend) $line .= " · ฿{$weekend}/คืน (ศุกร์-เสาร์)";
            $line .= "\n   รองรับ {$capMin}–{$capMax} คน";
            if ($bed > 0) $line .= " | {$bed} ห้องนอน";
            $lines[] = $line;
        }

        $lines[] = "\nสนใจสอบถามวันว่างพิมพ์ว่า\n\"[วันที่] ว่างไหม [จำนวนคน] คน\" ได้เลยค่ะ 😊";
        return implode("\n", $lines);
    }

    private static function buildCheckinTime(array $p): string
    {
        $ci = self::fmtTime($p['check_in'] ?? '14:00');
        $co = self::fmtTime($p['check_out'] ?? '12:00');
        return "เวลาเช็คอิน-เช็คเอาท์ 🕐\n"
             . "{$p['name']}\n\n"
             . "• เช็คอิน:  {$ci} น.\n"
             . "• เช็คเอาท์: {$co} น.\n\n"
             . "หากต้องการเข้าก่อนหรือออกหลังเวลา กรุณาแจ้งล่วงหน้าค่ะ";
    }

    private static function buildLocation(array $p): string
    {
        $name = $p['name'];
        $addr = trim(implode(' ', array_filter([
            $p['address'] ?? '',
            $p['district'] ?? '',
            $p['zone'] ?? '',
            $p['province'] ?? 'กาญจนบุรี',
        ])));

        $msg = "ที่ตั้ง {$name} 📍\n";
        if ($addr) $msg .= "{$addr}\n";

        $lat = (float)($p['latitude'] ?? 0);
        $lng = (float)($p['longitude'] ?? 0);
        if ($lat && $lng) {
            $msg .= "\nGoogle Maps:\nhttps://maps.google.com/?q={$lat},{$lng}";
        }

        if (!$addr && !($lat && $lng)) {
            $msg .= "\nกรุณาติดต่อสอบถามที่อยู่โดยตรงค่ะ\n" . self::contactLine($p);
        }

        return $msg;
    }

    /** @return array<int,array> LINE messages array */
    private static function buildAmenitiesMsgs(int $propertyId, array $p, array $units): array
    {
        $amenities = Database::fetchAll(
            "SELECT a.name FROM amenities a
             JOIN property_amenities pa ON pa.amenity_id = a.id
             WHERE pa.property_id = :pid
             ORDER BY a.sort_order",
            ['pid' => $propertyId]
        );

        $msg = "สิ่งอำนวยความสะดวก {$p['name']} ✨\n";

        if (!empty($amenities)) {
            foreach ($amenities as $a) {
                $msg .= "• {$a['name']}\n";
            }
        } else {
            $msg .= "(กรุณาสอบถามรายละเอียดเพิ่มเติม)\n";
        }

        // ยูนิต features
        if (!empty($units)) {
            $msg .= "\n🛏 ห้องพัก / ยูนิต\n";
            foreach ($units as $u) {
                $feat = [];
                if ((int)$u['bedrooms'] > 0)  $feat[] = $u['bedrooms'] . ' ห้องนอน';
                if ((int)$u['bathrooms'] > 0)  $feat[] = $u['bathrooms'] . ' ห้องน้ำ';
                $feat[] = $u['capacity_min'] . '–' . $u['capacity_max'] . ' คน';
                $msg .= "• {$u['name']}: " . implode(' | ', $feat) . "\n";
            }
        }

        $msg .= "\nสอบถามเพิ่มเติมได้เลยนะคะ 😊";
        return [self::txtMsg($msg)];
    }

    private static function buildPets(array $p): string
    {
        $policy = $p['pet_policy'] ?? 'not_allowed';
        $name   = $p['name'];
        if ($policy === 'allowed') {
            return "นโยบายสัตว์เลี้ยง {$name} 🐾\n\n"
                 . "✅ รับสัตว์เลี้ยงค่ะ\n\n"
                 . "กรุณาแจ้งก่อนเข้าพักด้วยนะคะ เพื่อเตรียมความพร้อม";
        }
        if ($policy === 'on_request') {
            return "นโยบายสัตว์เลี้ยง {$name} 🐾\n\n"
                 . "⚠️ รับสัตว์เลี้ยงบางกรณี (แจ้งก่อนล่วงหน้า)\n\n"
                 . "กรุณาสอบถามรายละเอียดก่อนจองนะคะ\n" . self::contactLine($p);
        }
        return "นโยบายสัตว์เลี้ยง {$name} 🐾\n\n"
             . "❌ ขออภัย ไม่อนุญาตนำสัตว์เลี้ยงเข้าพักค่ะ";
    }

    private static function buildContact(array $p): string
    {
        $name = $p['name'];
        $msg  = "ติดต่อ {$name} 📞\n";
        if (!empty($p['phone']))   $msg .= "\n☎️ โทร: {$p['phone']}";
        if (!empty($p['line_id'])) $msg .= "\n💬 LINE: {$p['line_id']}";
        if (!empty($p['facebook_url'])) $msg .= "\n📘 Facebook: {$p['facebook_url']}";
        if (!empty($p['website_url']))  $msg .= "\n🌐 เว็บไซต์: {$p['website_url']}";

        if (empty($p['phone']) && empty($p['line_id'])) {
            $msg .= "\n(ยังไม่มีข้อมูลติดต่อ กรุณารอสักครู่)";
        }
        return $msg;
    }

    private static function buildBookingHow(array $p): string
    {
        $name = $p['name'];
        return "วิธีจอง {$name} 📋\n\n"
             . "1️⃣ แจ้งวันที่ต้องการ และจำนวนคน\n"
             . "2️⃣ เจ้าหน้าที่ยืนยันห้องว่างและราคา\n"
             . "3️⃣ โอนเงินมัดจำ (ตามเงื่อนไขที่พัก)\n"
             . "4️⃣ รับใบยืนยันการจอง\n\n"
             . "💬 พิมพ์ \"[วันที่] ว่างไหม [จำนวนคน] คน\"\n"
             . "เพื่อเช็กว่างได้เลยค่ะ!\n\n"
             . self::contactLine($p);
    }

    private static function buildFallback(array $p): string
    {
        return "ขอบคุณที่ติดต่อ {$p['name']} ค่ะ 😊\n\n"
             . "สอบถามได้เลยนะคะ เช่น\n"
             . "• \"ราคาเท่าไหร่\"\n"
             . "• \"เดือนนี้ เสาร์ไหน ว่างบ้าง\"\n"
             . "• \"15-16 มิ.ย. ว่างไหม 4 คน\"\n"
             . "• \"เช็คอินกี่โมง\"\n"
             . "• \"ที่อยู่อยู่ที่ไหน\"\n"
             . "• \"มีอะไรบ้าง\"\n"
             . "• \"รับสัตว์เลี้ยงไหม\"\n\n"
             . self::contactLine($p);
    }

    // =========================================================
    // PHASE 2: AVAILABILITY CHECK
    // =========================================================

    /** @return array<int,array> LINE messages array */
    private static function buildAvailabilityMsgs(int $propertyId, array $property, array $units, string $text): array
    {
        // "เดือนนี้ เสาร์ไหน ว่างบ้าง" → สแกนทุกวันเสาร์ในเดือน
        $listQuery = self::parseMonthWeekdayListQuery($text);
        if ($listQuery !== null) {
            return self::buildMonthWeekdayListMsgs($propertyId, $property, $text, $listQuery);
        }

        $dates  = self::parseDates($text);
        $guests = self::parseGuests($text);

        // ไม่พบวันที่ → ถามกลับ
        if (!$dates) {
            $msg = "ต้องการเช็กวันว่างค่ะ 🗓️\n\n"
                 . "กรุณาระบุวันที่ต้องการ เช่น\n"
                 . "• \"เสาร์นี้ ว่างไหม 4 คน\"\n"
                 . "• \"15-16 มิ.ย. 4 คน\"\n"
                 . "• \"20/6 - 22/6 ผู้ใหญ่ 6 คน\"";
            return [self::txtMsg($msg)];
        }

        $checkIn  = $dates['check_in'];
        $checkOut = $dates['check_out'];

        // validate dates are in future
        if ($checkIn < date('Y-m-d')) {
            // ลองปรับปีใหม่ถ้าผ่านมาแล้ว
            $checkIn  = date('Y', strtotime('+1 year')) . substr($checkIn, 4);
            $checkOut = date('Y', strtotime('+1 year')) . substr($checkOut, 4);
        }

        $nights      = max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400));
        $available   = self::queryAvailableUnits($propertyId, $checkIn, $checkOut, $guests);

        $ciTh = self::thaiDateShort($checkIn);
        $coTh = self::thaiDateShort($checkOut);

        $header = "เช็กวันว่าง {$property['name']} 🗓️\n"
                . "วันที่ {$ciTh} → {$coTh} ({$nights} คืน)"
                . ($guests > 0 ? " | {$guests} คน" : '') . "\n"
                . str_repeat('─', 28) . "\n";

        if (empty($available)) {
            $msg = $header
                 . "😔 ขออภัย ไม่มีห้องว่างในช่วงวันที่ระบุค่ะ\n\n"
                 . "ลองถามวันอื่นได้เลยนะคะ หรือติดต่อโดยตรง\n"
                 . self::contactLine($property);
            return [self::txtMsg($msg)];
        }

        $lines = [$header];
        foreach ($available as $u) {
            $price   = self::calcPrice($u, $checkIn, $nights);
            $total   = $price * $nights;
            $capRng  = "{$u['capacity_min']}–{$u['capacity_max']} คน";
            $bedInfo = (int)$u['bedrooms'] > 0 ? " | {$u['bedrooms']} ห้องนอน" : '';

            $lines[] = "✅ {$u['name']}\n"
                     . "   ฿" . number_format($price) . "/คืน{$bedInfo}\n"
                     . "   รองรับ {$capRng} · รวม ฿" . number_format($total);
        }

        $lines[] = "\n" . str_repeat('─', 28);
        $lines[] = "สนใจจองแจ้งชื่อและเบอร์โทรได้เลยค่ะ 😊\n" . self::contactLine($property);

        return [self::txtMsg(implode("\n", $lines))];
    }

    /**
     * คำถามแบบ "เดือนนี้ เสาร์ไหน ว่างบ้าง" / "เสาร์ไหนว่างบ้าง"
     * @return array{weekday:int,weekday_name:string,month_offset:int}|null
     */
    private static function parseMonthWeekdayListQuery(string $text): ?array
    {
        $t = mb_strtolower($text, 'UTF-8');

        if (!preg_match('/ไหน|บ้าง|ทุก|ทั้งหมด|มี.*ไหม|ได้.*บ้าง/u', $t)) {
            return null;
        }

        $weekday     = null;
        $weekdayName = '';
        $dayMap      = [
            'อาทิตย์' => 0, 'จันทร์' => 1, 'อังคาร' => 2, 'พุธ' => 3,
            'พฤหัส'   => 4, 'ศุกร์'  => 5, 'เสาร์'  => 6,
        ];
        foreach ($dayMap as $name => $dow) {
            if (preg_match('/' . $name . '/u', $t)) {
                $weekday     = $dow;
                $weekdayName = $name;
                break;
            }
        }
        if ($weekday === null) {
            return null;
        }

        $monthOffset = preg_match('/เดือนหน้า/u', $t) ? 1 : 0;

        return [
            'weekday'      => $weekday,
            'weekday_name' => $weekdayName,
            'month_offset' => $monthOffset,
        ];
    }

    /** @param array{weekday:int,weekday_name:string,month_offset:int} $query */
    private static function buildMonthWeekdayListMsgs(
        int $propertyId,
        array $property,
        string $text,
        array $query
    ): array {
        $guests = self::parseGuests($text);
        $offset = (int)$query['month_offset'];
        $ts     = strtotime("+{$offset} month");
        $year   = (int)date('Y', $ts);
        $month  = (int)date('n', $ts);
        $dow    = (int)$query['weekday'];
        $dname  = (string)$query['weekday_name'];

        $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                       'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $monthLabel = $thaiMonths[$month] ?? (string)$month;
        $scopeLabel = $offset === 1 ? 'เดือนหน้า' : 'เดือนนี้';

        $today    = date('Y-m-d');
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = date('Y-m-t', strtotime($firstDay));

        $lines = [
            "วัน{$dname}ที่ว่าง — {$property['name']} 🗓️",
            "{$scopeLabel} ({$monthLabel} {$year})"
                . ($guests > 0 ? " · {$guests} คน" : '') . "\n"
                . str_repeat('─', 28),
        ];

        $availableDates = [];
        $fullDates      = [];
        $cur            = strtotime($firstDay);
        $end            = strtotime($lastDay);

        while ($cur <= $end) {
            if ((int)date('w', $cur) !== $dow) {
                $cur = strtotime('+1 day', $cur);
                continue;
            }
            $ci = date('Y-m-d', $cur);
            if ($ci < $today) {
                $cur = strtotime('+1 day', $cur);
                continue;
            }
            $co = date('Y-m-d', strtotime($ci . ' +1 day'));
            $units = self::queryAvailableUnits($propertyId, $ci, $co, $guests);
            if (!empty($units)) {
                $minPrice = PHP_INT_MAX;
                foreach ($units as $u) {
                    $p = self::calcPrice($u, $ci, 1);
                    if ($p < $minPrice) $minPrice = $p;
                }
                $availableDates[] = ['date' => $ci, 'price' => $minPrice];
            } else {
                $fullDates[] = $ci;
            }
            $cur = strtotime('+1 day', $cur);
        }

        if (empty($availableDates) && empty($fullDates)) {
            $lines[] = "\nไม่พบวัน{$dname}ที่เหลือใน{$scopeLabel}ค่ะ";
            $lines[] = "\nลองถาม \"เดือนหน้า {$dname}ไหนว่างบ้าง\" หรือระบุวันที่เฉพาะได้เลย";
            $lines[] = self::contactLine($property);
            return [self::txtMsg(implode("\n", $lines))];
        }

        if (!empty($availableDates)) {
            $lines[] = "\n✅ ว่าง " . count($availableDates) . " วัน:";
            foreach ($availableDates as $row) {
                $dLabel = self::thaiDateShort($row['date']);
                $price  = $row['price'] < PHP_INT_MAX
                    ? '฿' . number_format((int)$row['price']) . '/คืน'
                    : '';
                $lines[] = "• {$dname} {$dLabel}" . ($price ? " — {$price}" : '');
            }
        }

        if (!empty($fullDates) && count($fullDates) <= 4) {
            $lines[] = "\n❌ เต็มแล้ว:";
            foreach ($fullDates as $fd) {
                $lines[] = '• ' . $dname . ' ' . self::thaiDateShort($fd);
            }
        } elseif (!empty($fullDates)) {
            $lines[] = "\n❌ เต็มอีก " . count($fullDates) . ' วัน';
        }

        $lines[] = "\n" . str_repeat('─', 28);
        $lines[] = "สนใจวันไหนแจ้งชื่อ+เบอร์ได้เลยค่ะ 😊\n" . self::contactLine($property);

        return [self::txtMsg(implode("\n", $lines))];
    }

    /**
     * คิวรีหาห้องว่างใน date range
     * @return array<int,array>
     */
    private static function queryAvailableUnits(int $propertyId, string $checkIn, string $checkOut, int $guests): array
    {
        $units = Database::fetchAll(
            "SELECT * FROM property_units
             WHERE property_id = :p AND is_active = 1
             ORDER BY price ASC",
            ['p' => $propertyId]
        );

        $available = [];
        foreach ($units as $unit) {
            // กรอง capacity
            if ($guests > 0 && (int)$unit['capacity_max'] < $guests) continue;

            // ตรวจปฏิทินที่เจ้าของบล็อก
            $blocked = Database::fetch(
                "SELECT id FROM availability
                 WHERE unit_id = :u AND date >= :s AND date < :e
                   AND status IN ('closed','blocked','fully_booked')
                 LIMIT 1",
                ['u' => $unit['id'], 's' => $checkIn, 'e' => $checkOut]
            );
            if ($blocked) continue;

            // ตรวจการจองที่มีอยู่แล้ว
            $booked = Database::fetch(
                "SELECT COUNT(*) cnt FROM bookings
                 WHERE unit_id = :u AND status IN ('pending','confirmed')
                   AND check_in < :co AND check_out > :ci",
                ['u' => $unit['id'], 'ci' => $checkIn, 'co' => $checkOut]
            );
            $bookedCount = (int)($booked['cnt'] ?? 0);
            $totalUnits  = max(1, (int)($unit['total_units'] ?? 1));

            if ($bookedCount < $totalUnits) {
                $available[] = $unit;
            }
        }

        return $available;
    }

    // =========================================================
    // DATE / TEXT PARSERS
    // =========================================================

    /** Parse dates จาก text ภาษาไทย/เลข → ['check_in'=>'Y-m-d', 'check_out'=>'Y-m-d'] หรือ null */
    private static function parseDates(string $text): ?array
    {
        $relative = self::parseRelativeDates($text);
        if ($relative !== null) return $relative;

        $year = (int)date('Y');

        // หา Thai month ในข้อความ
        $foundMonth = null;
        foreach (self::THAI_MONTHS as $pat => $m) {
            if (preg_match('/' . $pat . '/u', $text)) {
                $foundMonth = $m;
                break;
            }
        }

        // Pattern: "15-16 มิ.ย." หรือ "15 - 16 มิ.ย."
        if ($foundMonth !== null) {
            if (preg_match('/(\d{1,2})\s*[-–]\s*(\d{1,2})/u', $text, $m2)) {
                $d1 = (int)$m2[1];
                $d2 = (int)$m2[2];
                $ci = self::makeDate($year, $foundMonth, $d1);
                $co = self::makeDate($year, $foundMonth, $d2);
                if ($co <= $ci) {
                    // ข้ามเดือน
                    $co = self::makeDate($year, $foundMonth + 1, $d2);
                }
                return ['check_in' => $ci, 'check_out' => $co];
            }
            // Pattern: "15 มิ.ย." (single day → 1 คืน)
            if (preg_match('/(\d{1,2})\s/u', $text, $m2)) {
                $d1 = (int)$m2[1];
                if ($d1 >= 1 && $d1 <= 31) {
                    $ci = self::makeDate($year, $foundMonth, $d1);
                    $co = date('Y-m-d', strtotime($ci . ' +1 day'));
                    return ['check_in' => $ci, 'check_out' => $co];
                }
            }
        }

        // Pattern numeric range: "15/6 - 17/6" หรือ "15/06-17/06"
        if (preg_match('/(\d{1,2})[\/](\d{1,2})\s*[-–]\s*(\d{1,2})[\/](\d{1,2})/u', $text, $m2)) {
            $d1 = (int)$m2[1]; $mon1 = (int)$m2[2];
            $d2 = (int)$m2[3]; $mon2 = (int)$m2[4];
            if (self::validDate($year, $mon1, $d1) && self::validDate($year, $mon2, $d2)) {
                return [
                    'check_in'  => self::makeDate($year, $mon1, $d1),
                    'check_out' => self::makeDate($year, $mon2, $d2),
                ];
            }
        }

        // Pattern numeric single: "15/6"
        if (preg_match('/(\d{1,2})[\/](\d{1,2})(?:[\/]\d{2,4})?/u', $text, $m2)) {
            $d1 = (int)$m2[1]; $mon1 = (int)$m2[2];
            if (self::validDate($year, $mon1, $d1)) {
                $ci = self::makeDate($year, $mon1, $d1);
                $co = date('Y-m-d', strtotime($ci . ' +1 day'));
                return ['check_in' => $ci, 'check_out' => $co];
            }
        }

        return null;
    }

    /**
     * วันที่แบบสัมพัทธ์: เสาร์นี้, พรุ่งนี้, วันนี้ ฯลฯ
     * คืน 1 คืน (check_out = check_in + 1 วัน) เป็นค่าเริ่มต้น
     */
    private static function parseRelativeDates(string $text): ?array
    {
        // คำถามแบบ list ("เสาร์ไหนว่างบ้าง") ไม่ใช่วันเดียว
        if (self::parseMonthWeekdayListQuery($text) !== null) {
            return null;
        }

        $t = mb_strtolower($text, 'UTF-8');

        // พรุ่งนี้
        if (preg_match('/พรุ่งนี้/u', $t)) {
            $ci = date('Y-m-d', strtotime('+1 day'));
            return ['check_in' => $ci, 'check_out' => date('Y-m-d', strtotime($ci . ' +1 day'))];
        }

        // วันนี้ / คืนนี้
        if (preg_match('/วันนี้|คืนนี้/u', $t)) {
            $ci = date('Y-m-d');
            return ['check_in' => $ci, 'check_out' => date('Y-m-d', strtotime('+1 day'))];
        }

        // วันในสัปดาห์แบบเฉพาะเจาะจง: เสาร์นี้, ศุกร์นี้ (ต้องมี "นี้" — ไม่จับ "เสาร์ไหน")
        $dayThisPatterns = [
            '/อาทิตย์นี้/u' => 0,
            '/จันทร์นี้/u'   => 1,
            '/อังคารนี้/u'  => 2,
            '/พุธนี้/u'     => 3,
            '/พฤหัสนี้/u'   => 4,
            '/ศุกร์นี้/u'   => 5,
            '/เสาร์นี้/u'   => 6,
        ];
        foreach ($dayThisPatterns as $pat => $targetDow) {
            if (preg_match($pat, $t)) {
                $ci = self::nextWeekday($targetDow);
                return ['check_in' => $ci, 'check_out' => date('Y-m-d', strtotime($ci . ' +1 day'))];
            }
        }

        // "วันเสาร์" / "วันศุกร์" โดยไม่มี "ไหน/บ้าง" (เช็กวันเดียว)
        $dayBarePatterns = [
            '/วันอาทิตย์/u' => 0, '/วันจันทร์/u' => 1, '/วันอังคาร/u' => 2,
            '/วันพุธ/u'     => 3, '/วันพฤหัส/u' => 4, '/วันศุกร์/u' => 5,
            '/วันเสาร์/u'   => 6,
        ];
        foreach ($dayBarePatterns as $pat => $targetDow) {
            if (preg_match($pat, $t)) {
                $ci = self::nextWeekday($targetDow);
                return ['check_in' => $ci, 'check_out' => date('Y-m-d', strtotime($ci . ' +1 day'))];
            }
        }

        return null;
    }

    /** หาวันถัดไปที่ตรงกับ day-of-week (0=อา..6=ส) */
    private static function nextWeekday(int $targetDow): string
    {
        $now = strtotime('today');
        $dow = (int)date('w', $now);
        if ($dow === $targetDow) {
            return date('Y-m-d', $now);
        }
        $days = ($targetDow - $dow + 7) % 7;
        if ($days === 0) $days = 7;
        return date('Y-m-d', strtotime("+{$days} days", $now));
    }

    /** ดึงจำนวนคนจาก text */
    private static function parseGuests(string $text): int
    {
        if (preg_match('/(\d+)\s*คน/u', $text, $m))   return max(1, (int)$m[1]);
        if (preg_match('/(\d+)\s*ผู้ใหญ่/u', $text, $m)) return max(1, (int)$m[1]);
        if (preg_match('/(\d+)\s*ท่าน/u', $text, $m))  return max(1, (int)$m[1]);
        return 0;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /** คำนวณราคา/คืน โดยดูว่า check_in เป็นวันอะไร (อย่างน้อย 1 คืน) */
    private static function calcPrice(array $unit, string $checkIn, int $nights): float
    {
        if ($nights <= 0) $nights = 1;
        $base    = (float)$unit['price'];
        $weekend = (float)($unit['price_weekend'] ?? 0);
        if ($weekend <= 0) return $base;

        $total = 0.0;
        for ($i = 0; $i < $nights; $i++) {
            $ts  = strtotime("+{$i} day", strtotime($checkIn));
            $dow = (int)date('w', $ts); // 0=Sun, 6=Sat
            $total += ($dow === 5 || $dow === 6) ? $weekend : $base;
        }
        return $total / $nights; // return per-night avg
    }

    private static function contactLine(array $p): string
    {
        $parts = [];
        if (!empty($p['phone']))   $parts[] = '☎️ ' . $p['phone'];
        if (!empty($p['line_id'])) $parts[] = '💬 LINE: ' . $p['line_id'];
        return implode('  |  ', $parts);
    }

    private static function fmtTime(string $t): string
    {
        // รับ "14:00:00" หรือ "14:00" → "14.00"
        $parts = explode(':', $t);
        return sprintf('%02d.%02d', (int)($parts[0] ?? 0), (int)($parts[1] ?? 0));
    }

    private static function thaiDateShort(string $ymd): string
    {
        $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $ts = strtotime($ymd);
        return date('j', $ts) . ' ' . ($thMonths[(int)date('n', $ts)] ?? '');
    }

    private static function makeDate(int $y, int $m, int $d): string
    {
        if ($m > 12) { $y++; $m -= 12; }
        if ($m < 1)  { $y--; $m += 12; }
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    private static function validDate(int $y, int $m, int $d): bool
    {
        return $m >= 1 && $m <= 12 && $d >= 1 && $d <= 31;
    }

    private static function txtMsg(string $text): array
    {
        return ['type' => 'text', 'text' => mb_substr($text, 0, 4900, 'UTF-8')];
    }
}
