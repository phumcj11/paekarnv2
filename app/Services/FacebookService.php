<?php
namespace App\Services;

use App\Models\Setting;

/**
 * Facebook Graph API v19 — Page posting, OAuth helpers
 */
class FacebookService
{
    private const GRAPH   = 'https://graph.facebook.com/v19.0';
    private const DIALOG  = 'https://www.facebook.com/v19.0/dialog/oauth';
    private const SCOPES  = 'pages_manage_posts,pages_read_engagement,pages_show_list';

    // ─────────────────────────────────────────────────────────────
    // App credentials (from admin settings)
    // ─────────────────────────────────────────────────────────────

    public static function appId(): string
    {
        return (string)Setting::get('fb_app_id', '');
    }

    public static function appSecret(): string
    {
        return (string)Setting::get('fb_app_secret', '');
    }

    public static function isConfigured(): bool
    {
        return self::appId() !== '' && self::appSecret() !== '';
    }

    // ─────────────────────────────────────────────────────────────
    // OAuth helpers
    // ─────────────────────────────────────────────────────────────

    /** Build Facebook Login OAuth URL */
    public static function oauthUrl(string $redirectUri, string $state): string
    {
        return self::DIALOG . '?' . http_build_query([
            'client_id'     => self::appId(),
            'redirect_uri'  => $redirectUri,
            'scope'         => self::SCOPES,
            'state'         => $state,
            'response_type' => 'code',
        ]);
    }

    /** Exchange short-lived code for short-lived user access token */
    public static function exchangeCode(string $code, string $redirectUri): ?string
    {
        $res = self::get(self::GRAPH . '/oauth/access_token', [
            'client_id'     => self::appId(),
            'client_secret' => self::appSecret(),
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);
        return $res['access_token'] ?? null;
    }

    /** Exchange short-lived user token for long-lived (60-day) user token */
    public static function longLivedUserToken(string $shortToken): ?string
    {
        $res = self::get(self::GRAPH . '/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => self::appId(),
            'client_secret'     => self::appSecret(),
            'fb_exchange_token' => $shortToken,
        ]);
        return $res['access_token'] ?? null;
    }

    /** List pages the user manages → [{id, name, access_token, category}] */
    public static function getUserPages(string $userToken): array
    {
        $res = self::get(self::GRAPH . '/me/accounts', [
            'fields'       => 'id,name,access_token,category',
            'access_token' => $userToken,
        ]);
        return $res['data'] ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    // Page posting
    // ─────────────────────────────────────────────────────────────

    /**
     * Post text + optional images to a Facebook Page.
     * Images are public URLs — Facebook fetches them directly.
     * Returns the new FB post ID/URL on success, null on failure.
     */
    public static function postToPage(
        string $pageId,
        string $pageToken,
        string $message,
        array  $imageUrls = []
    ): ?array {
        $imageUrls = array_values(array_filter($imageUrls));

        if (!$imageUrls) {
            // Text-only post
            $res = self::post(self::GRAPH . "/{$pageId}/feed", [
                'message'      => $message,
                'access_token' => $pageToken,
            ]);
            if (isset($res['id'])) {
                return ['post_id' => $res['id'], 'url' => "https://www.facebook.com/{$res['id']}"];
            }
            return isset($res['error']) ? ['error' => $res['error']['message'] ?? 'Facebook error'] : null;
        }

        // Upload images as unpublished photos, collect media_fbid
        $mediaFbids = [];
        foreach ($imageUrls as $imgUrl) {
            $photoRes = self::post(self::GRAPH . "/{$pageId}/photos", [
                'url'          => $imgUrl,
                'published'    => 'false',
                'access_token' => $pageToken,
            ]);
            if (!empty($photoRes['id'])) {
                $mediaFbids[] = ['media_fbid' => $photoRes['id']];
            }
        }

        // Post with attached media
        $payload = ['message' => $message, 'access_token' => $pageToken];
        foreach ($mediaFbids as $i => $m) {
            $payload["attached_media[{$i}]"] = json_encode($m);
        }
        $res = self::post(self::GRAPH . "/{$pageId}/feed", $payload);
        if (isset($res['id'])) {
            return ['post_id' => $res['id'], 'url' => "https://www.facebook.com/{$res['id']}"];
        }
        return isset($res['error']) ? ['error' => $res['error']['message'] ?? 'Facebook error'] : null;
    }

    // ─────────────────────────────────────────────────────────────
    // HTTP helpers
    // ─────────────────────────────────────────────────────────────

    private static function get(string $url, array $params = []): array
    {
        $full = $url . ($params ? '?' . http_build_query($params) : '');
        $ctx  = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => 'User-Agent: PaekarnBot/1.0',
            'timeout' => 15,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($full, false, $ctx);
        return $body ? (json_decode($body, true) ?: []) : [];
    }

    private static function post(string $url, array $data): array
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: PaekarnBot/1.0",
            'content' => http_build_query($data),
            'timeout' => 20,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body ? (json_decode($body, true) ?: []) : [];
    }
}
