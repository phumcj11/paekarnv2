<?php
namespace App\Services;

use App\Core\Database;
use App\Models\ContentPlan;

/**
 * Publish marketing content plans to Facebook Page, LINE OA broadcast, or Instagram.
 */
class ContentPlanPublishService
{
    public static function buildMessage(array $plan): string
    {
        $body     = trim((string)($plan['body'] ?? ''));
        $hashtags = trim((string)($plan['hashtags'] ?? ''));
        return $body . ($hashtags !== '' ? "\n\n" . $hashtags : '');
    }

    /** @return list<string> */
    public static function resolveImageUrls(array $plan): array
    {
        $urls = [];
        foreach (ContentPlan::parseImages($plan['image_url'] ?? null) as $img) {
            $urls[] = preg_match('#^https?://#i', (string)$img) ? (string)$img : upload_url((string)$img);
        }
        return $urls;
    }

    /**
     * @return array{ok:bool,error?:string,post_url?:string,post_id?:string,sent?:int,failed?:int,total?:int}
     */
    public static function publishFacebook(array $plan, array $prop): array
    {
        if (!FacebookService::isConfigured()) {
            return ['ok' => false, 'error' => 'ยังไม่ได้ตั้งค่า Facebook App'];
        }
        if (empty($prop['facebook_page_token']) || empty($prop['facebook_page_id'])) {
            return ['ok' => false, 'error' => 'ยังไม่ได้เชื่อมต่อ Facebook Page'];
        }

        $message   = self::buildMessage($plan);
        $imageUrls = self::resolveImageUrls($plan);
        $result    = FacebookService::postToPage(
            (string)$prop['facebook_page_id'],
            (string)$prop['facebook_page_token'],
            $message,
            $imageUrls
        );

        if (!$result) {
            return ['ok' => false, 'error' => 'ไม่สามารถโพสต์ได้'];
        }
        if (isset($result['error'])) {
            return ['ok' => false, 'error' => 'Facebook: ' . $result['error']];
        }

        return [
            'ok'       => true,
            'post_url' => $result['url'] ?? '',
            'post_id'  => $result['post_id'] ?? '',
        ];
    }

    /**
     * Broadcast to all LINE followers of the property.
     *
     * @return array{ok:bool,error?:string,sent?:int,failed?:int,total?:int}
     */
    public static function publishLine(array $plan, int $propertyId): array
    {
        if ($propertyId <= 0) {
            return ['ok' => false, 'error' => 'ต้องเลือกที่พัก'];
        }
        if (!Database::tableHasColumn('properties', 'line_channel_access_token')) {
            return ['ok' => false, 'error' => 'ยังไม่ได้ตั้งค่า LINE OA'];
        }

        $prop = Database::fetch(
            "SELECT line_channel_access_token FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        if (empty(trim((string)($prop['line_channel_access_token'] ?? '')))) {
            return ['ok' => false, 'error' => 'ยังไม่ได้บันทึก LINE Channel Access Token — ไปที่ LINE Hub'];
        }

        $contacts = Database::fetchAll(
            "SELECT line_user_id FROM property_line_contacts
             WHERE property_id = :p AND unfollowed_at IS NULL
             ORDER BY last_seen_at DESC",
            ['p' => $propertyId]
        );
        if (empty($contacts)) {
            return ['ok' => false, 'error' => 'ไม่มีผู้ติดตาม LINE'];
        }

        $message   = mb_substr(self::buildMessage($plan), 0, 2000);
        $imageUrls = self::resolveImageUrls($plan);
        $msgs      = [];
        if (!empty($imageUrls)) {
            $msgs[] = [
                'type'               => 'image',
                'originalContentUrl' => $imageUrls[0],
                'previewImageUrl'    => $imageUrls[0],
            ];
        }
        $msgs[] = ['type' => 'text', 'text' => $message];

        $sent   = 0;
        $failed = 0;
        foreach ($contacts as $c) {
            $ok = PropertyLineService::push($propertyId, (string)$c['line_user_id'], $msgs);
            $ok ? $sent++ : $failed++;
            if (($sent + $failed) % 10 === 0) {
                usleep(100000);
            }
        }

        return [
            'ok'     => $sent > 0,
            'sent'   => $sent,
            'failed' => $failed,
            'total'  => count($contacts),
            'error'  => $sent === 0 ? 'ส่งไม่สำเร็จเลย — ตรวจสอบ LINE token' : null,
        ];
    }

    /**
     * @return array{ok:bool,error?:string,post_url?:string,post_id?:string}
     */
    public static function publishInstagram(array $plan, array $prop): array
    {
        if (!FacebookService::isConfigured()) {
            return ['ok' => false, 'error' => 'ยังไม่ได้ตั้งค่า Facebook App'];
        }
        if (empty($prop['facebook_page_token']) || empty($prop['facebook_page_id'])) {
            return ['ok' => false, 'error' => 'ต้องเชื่อมต่อ Facebook Page ที่ผูก Instagram Business ก่อน'];
        }

        $message   = self::buildMessage($plan);
        $imageUrls = self::resolveImageUrls($plan);
        $result    = FacebookService::postToInstagram(
            (string)$prop['facebook_page_id'],
            (string)$prop['facebook_page_token'],
            $message,
            $imageUrls
        );

        if (!$result) {
            return ['ok' => false, 'error' => 'ไม่สามารถโพสต์ Instagram ได้'];
        }
        if (isset($result['error'])) {
            return ['ok' => false, 'error' => 'Instagram: ' . $result['error']];
        }

        return [
            'ok'       => true,
            'post_url' => $result['url'] ?? '',
            'post_id'  => $result['post_id'] ?? '',
        ];
    }

    /**
     * @return array{ok:bool,error?:string,post_url?:string,post_id?:string,sent?:int,failed?:int,total?:int}
     */
    public static function publish(array $plan, ?array $prop = null): array
    {
        $platform = (string)($plan['platform'] ?? 'facebook');
        $propId   = (int)($plan['property_id'] ?? 0);

        if (!$prop && $propId > 0) {
            $prop = Database::fetch("SELECT * FROM properties WHERE id = :i LIMIT 1", ['i' => $propId]);
        }
        $prop = $prop ?: [];

        return match ($platform) {
            'facebook'  => self::publishFacebook($plan, $prop),
            'line'      => self::publishLine($plan, $propId),
            'instagram' => self::publishInstagram($plan, $prop),
            default     => ['ok' => false, 'error' => 'แพลตฟอร์มนี้ไม่รองรับ auto-post'],
        };
    }

    public static function markPublished(int $planId, int $ownerId): void
    {
        ContentPlan::update($planId, ['status' => 'published', 'owner_id' => $ownerId]);
    }
}
