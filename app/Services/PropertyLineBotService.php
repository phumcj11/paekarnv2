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
     * Entry point: อ่านข้อความ, ตัดสิน intent, ส่ง reply กลับลูกค้า
     */
    public static function handle(int $propertyId, string $replyToken, string $text): void
    {
        $property = Database::fetch(
            "SELECT id, name, type, zone, district, province, address, phone, line_id,
                    check_in, check_out, pet_policy, description, latitude, longitude,
                    facebook_url, website_url
             FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        if (!$property) return;

        $units = Database::fetchAll(
            "SELECT id, name, price, price_weekend, capacity_min, capacity_max,
                    bedrooms, bathrooms, total_units, extra_person_fee
             FROM property_units
             WHERE property_id = :p AND is_active = 1 AND moderation_status = 'published'
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

        PropertyLineService::reply($propertyId, $replyToken, $messages);
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
        $dates  = self::parseDates($text);
        $guests = self::parseGuests($text);

        // ไม่พบวันที่ → ถามกลับ
        if (!$dates) {
            $msg = "ต้องการเช็กวันว่างค่ะ 🗓️\n\n"
                 . "กรุณาระบุวันที่ต้องการ เช่น\n"
                 . "• \"15-16 มิ.ย. 4 คน\"\n"
                 . "• \"20/6 - 22/6 ผู้ใหญ่ 6 คน\"\n"
                 . "• \"30 ก.ค. ว่างไหม 2 คน\"";
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
     * คิวรีหาห้องว่างใน date range
     * @return array<int,array>
     */
    private static function queryAvailableUnits(int $propertyId, string $checkIn, string $checkOut, int $guests): array
    {
        $units = Database::fetchAll(
            "SELECT * FROM property_units
             WHERE property_id = :p AND is_active = 1 AND moderation_status = 'published'
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
