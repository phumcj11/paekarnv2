<?php
namespace App\Support;

use App\Core\Database;

class PropertyBookingCapabilities
{
    public static function columnsReady(): bool
    {
        try {
            return Database::tableHasColumn('properties', 'allow_contact');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{allow_contact:bool,show_line_contact:bool,coupon_enabled:bool,allow_online_booking:bool,booking_requires_payment:bool,booking_mode:string,phone:string,line_id:string} */
    public static function fromProperty(array $p): array
    {
        $lineId = trim((string)($p['line_id'] ?? ''));
        $showLine = Database::tableHasColumn('properties', 'show_line_contact')
            && (int)($p['show_line_contact'] ?? 0) === 1;

        if (self::columnsReady()) {
            return [
                'allow_contact'            => (int)($p['allow_contact'] ?? 0) === 1,
                'show_line_contact'        => $showLine,
                'coupon_enabled'         => (int)($p['coupon_enabled'] ?? 0) === 1,
                'allow_online_booking'   => (int)($p['allow_online_booking'] ?? 0) === 1,
                'booking_requires_payment' => (int)($p['booking_requires_payment'] ?? 0) === 1,
                'booking_mode'           => (string)($p['booking_mode'] ?? 'info_only'),
                'phone'                  => trim((string)($p['phone'] ?? '')),
                'line_id'                => $lineId,
            ];
        }

        $mode = (string)($p['booking_mode'] ?? 'info_only');
        return [
            'allow_contact'            => $mode === 'info_only',
            'show_line_contact'        => $showLine,
            'coupon_enabled'         => (int)($p['coupon_enabled'] ?? 0) === 1,
            'allow_online_booking'   => in_array($mode, ['coupon_assisted', 'full_booking'], true),
            'booking_requires_payment' => $mode === 'full_booking',
            'booking_mode'           => $mode,
            'phone'                  => trim((string)($p['phone'] ?? '')),
            'line_id'                => $lineId,
        ];
    }

    /** @return list<string> contact|buy_coupon|book_online */
    public static function visibleButtons(array $p): array
    {
        $c = self::fromProperty($p);
        $btns = [];
        if ($c['allow_online_booking']) {
            $btns[] = 'book_online';
        }
        if ($c['coupon_enabled']) {
            $btns[] = 'buy_coupon';
        }
        if ($c['allow_contact'] && $c['phone'] !== '') {
            $btns[] = 'contact';
        }
        return $btns;
    }

    public static function bookUrl(int $propertyId, int $unitId, string $intent): string
    {
        $intent = in_array($intent, ['book', 'coupon'], true) ? $intent : 'book';
        return url('/booking/create/' . $propertyId . '?unit=' . $unitId . '&intent=' . $intent);
    }

    /** @param array{allow_contact?:int|bool,allow_online_booking?:int|bool,booking_requires_payment?:int|bool} $flags */
    public static function syncBookingMode(array $flags): string
    {
        if (!empty($flags['allow_online_booking'])) {
            return !empty($flags['booking_requires_payment']) ? 'full_booking' : 'coupon_assisted';
        }
        return 'info_only';
    }

    /**
     * @param array<string,mixed> $post
     * @return array{allow_contact:int,coupon_enabled:int,allow_online_booking:int,booking_requires_payment:int,booking_mode:string}|null null = validation fail
     */
    public static function payloadFromPost(array $post): ?array
    {
        $allowContact = !empty($post['allow_contact']) ? 1 : 0;
        $couponEnabled = !empty($post['coupon_enabled']) ? 1 : 0;
        $allowOnline = !empty($post['allow_online_booking']) ? 1 : 0;
        $requiresPayment = !empty($post['booking_requires_payment']) ? 1 : 0;

        if ($allowContact === 0 && $couponEnabled === 0 && $allowOnline === 0) {
            return null;
        }

        if ($allowOnline === 0) {
            $requiresPayment = 0;
        }

        $flags = [
            'allow_contact'            => $allowContact,
            'coupon_enabled'           => $couponEnabled,
            'allow_online_booking'   => $allowOnline,
            'booking_requires_payment' => $requiresPayment,
        ];
        $flags['booking_mode'] = self::syncBookingMode($flags);

        return $flags;
    }

    public static function showPayment(array $p, string $intent): bool
    {
        $c = self::fromProperty($p);
        return $c['allow_online_booking'] && $c['booking_requires_payment'] && $intent === 'book';
    }

    public static function allowsIntent(array $p, string $intent): bool
    {
        $c = self::fromProperty($p);
        if ($intent === 'coupon') {
            return $c['coupon_enabled'];
        }
        if ($intent === 'book') {
            return $c['allow_online_booking'] || $c['coupon_enabled'];
        }
        return false;
    }

    /** @return array<string,string> keyed by button id */
    public static function urlsForUnit(array $p, int $propertyId, int $unitId): array
    {
        $c = self::fromProperty($p);
        $urls = [];
        if ($c['allow_contact'] && $c['phone'] !== '') {
            $urls['contact'] = self::leadUrl($propertyId, $unitId, 'phone');
        }
        if ($c['show_line_contact'] && $c['line_id'] !== '') {
            $urls['line'] = self::leadUrl($propertyId, $unitId, 'line');
        }
        if ($c['coupon_enabled']) {
            $urls['buy_coupon'] = self::leadUrl($propertyId, $unitId, 'coupon');
        }
        if ($c['allow_online_booking']) {
            $urls['book_online'] = self::leadUrl($propertyId, $unitId, 'book');
        }
        return $urls;
    }

    public static function leadUrl(int $propertyId, int $unitId, string $type): string
    {
        $params = ['type' => $type];
        if ($unitId > 0) {
            $params['unit'] = $unitId;
        }

        return url('/property/lead/' . $propertyId . '?' . http_build_query($params));
    }

    public static function lineUrl(string $lineId): string
    {
        $lineId = trim($lineId);
        if ($lineId === '') {
            return '';
        }
        if (str_starts_with($lineId, 'http')) {
            return $lineId;
        }

        return 'https://line.me/R/ti/p/' . rawurlencode($lineId);
    }
}
