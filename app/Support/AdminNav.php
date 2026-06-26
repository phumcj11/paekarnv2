<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Admin sidebar navigation structure.
 */
class AdminNav
{
    /** @return list<array{href:string,icon:string,label:string}> */
    public static function pinned(): array
    {
        return [
            ['href' => '/admin/dashboard', 'icon' => 'gauge', 'label' => 'ภาพรวม'],
            ['href' => '/admin/analytics', 'icon' => 'bar-chart-3', 'label' => 'Analytics'],
            ['href' => '/admin/properties', 'icon' => 'hotel', 'label' => 'ที่พัก'],
            ['href' => '/admin/bookings', 'icon' => 'calendar-check', 'label' => 'การจอง'],
            ['href' => '/admin/owners', 'icon' => 'briefcase', 'label' => 'เจ้าของแพ'],
            ['href' => '/admin/customers', 'icon' => 'users', 'label' => 'ลูกค้า'],
        ];
    }

    /**
     * @return list<array{id:string,label:string,items:list<array{href:string,icon:string,label:string,badge?:string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'id'    => 'stay',
                'label' => 'ที่พัก & โซน',
                'items' => [
                    ['href' => '/admin/zones', 'icon' => 'layers', 'label' => 'โซนที่พัก'],
                ],
            ],
            [
                'id'    => 'marketing',
                'label' => 'คูปอง & การตลาด',
                'items' => [
                    ['href' => '/admin/coupons', 'icon' => 'ticket', 'label' => 'คูปอง'],
                    ['href' => '/admin/coupon-campaigns', 'icon' => 'tags', 'label' => 'แคมเปญคูปอง'],
                    ['href' => '/admin/zone-ads', 'icon' => 'signpost', 'label' => 'โฆษณาโซน'],
                    ['href' => '/admin/promotions', 'icon' => 'megaphone', 'label' => 'การตลาด & โปร'],
                    ['href' => '/admin/leads', 'icon' => 'sparkles', 'label' => 'CRM / Leads'],
                ],
            ],
            [
                'id'    => 'activities',
                'label' => 'กิจกรรม',
                'items' => [
                    ['href' => '/admin/activity-products', 'icon' => 'map', 'label' => 'กิจกรรม / บริการ', 'badge' => 'activity_products'],
                    ['href' => '/admin/activity-featured', 'icon' => 'star', 'label' => 'Featured กิจกรรม'],
                    ['href' => '/admin/activity-providers', 'icon' => 'handshake', 'label' => 'ผู้ให้บริการกิจกรรม', 'badge' => 'activity_providers'],
                    ['href' => '/admin/activity-orders', 'icon' => 'ticket-check', 'label' => 'คำสั่งซื้อกิจกรรม'],
                    ['href' => '/admin/visitor-places', 'icon' => 'map-pin', 'label' => 'ที่เที่ยว / POI'],
                ],
            ],
            [
                'id'    => 'content',
                'label' => 'เนื้อหา & รีวิว',
                'items' => [
                    ['href' => '/admin/reviews', 'icon' => 'message-circle', 'label' => 'รีวิว'],
                    ['href' => '/admin/review-videos', 'icon' => 'video', 'label' => 'วิดีโอแนะนำ'],
                    ['href' => '/admin/review-facebook-posts', 'icon' => 'share-2', 'label' => 'โพสต์ Facebook'],
                    ['href' => '/admin/blog', 'icon' => 'newspaper', 'label' => 'บล็อก'],
                    ['href' => '/admin/banners', 'icon' => 'layout-grid', 'label' => 'Banner หน้าเว็บ'],
                ],
            ],
            [
                'id'    => 'membership',
                'label' => 'สมาชิกแพกาญ',
                'items' => [
                    ['href' => '/admin/membership/orders', 'icon' => 'crown', 'label' => 'คำสั่งซื้อสมาชิก'],
                    ['href' => '/admin/membership/plans', 'icon' => 'package', 'label' => 'แพ็กเกจสมาชิก'],
                ],
            ],
            [
                'id'    => 'system',
                'label' => 'ระบบ & เชื่อมต่อ',
                'items' => [
                    ['href' => '/admin/automation', 'icon' => 'workflow', 'label' => 'Automation'],
                    ['href' => '/admin/ai', 'icon' => 'bot', 'label' => 'AI Settings'],
                    ['href' => '/admin/ai/kb', 'icon' => 'book-open', 'label' => 'AI Knowledge Base'],
                    ['href' => '/admin/ai/chats', 'icon' => 'message-square', 'label' => 'AI Chat History'],
                    ['href' => '/admin/line', 'icon' => 'messages-square', 'label' => 'LINE OA'],
                    ['href' => '/admin/settings', 'icon' => 'settings', 'label' => 'การตั้งค่า'],
                    ['href' => '/admin/tools/images', 'icon' => 'image-down', 'label' => 'Optimize รูป WebP'],
                    ['href' => '/admin/audit-logs', 'icon' => 'scroll-text', 'label' => 'Audit log'],
                ],
            ],
        ];
    }

    public static function groupForPath(string $path): ?string
    {
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (self::pathMatches($path, $item['href'])) {
                    return $group['id'];
                }
            }
        }

        return null;
    }

    public static function isActive(string $path, string $href): bool
    {
        return self::pathMatches($path, $href);
    }

    public static function groupHasActive(string $path, array $group): bool
    {
        foreach ($group['items'] as $item) {
            if (self::pathMatches($path, $item['href'])) {
                return true;
            }
        }

        return false;
    }

    private static function pathMatches(string $path, string $href): bool
    {
        if ($href === '/admin/dashboard') {
            return preg_match('#^/admin/dashboard/?$#', $path) === 1;
        }

        if ($href === '/admin/ai') {
            return preg_match('#^/admin/ai/?$#', $path) === 1;
        }

        return $path === $href || str_starts_with($path, $href . '/');
    }

    public static function badgeCount(string $badgeKey, int $providerPending, int $productPending): int
    {
        return match ($badgeKey) {
            'activity_providers' => $providerPending,
            'activity_products'  => $productPending,
            default              => 0,
        };
    }
}
