<?php
namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Csrf;
use App\Services\FacebookService;
use App\Services\OwnerFeatureGate;
use App\Services\OwnerTier;

/**
 * Facebook Page OAuth flow for Owner Portal
 *
 * 1. Owner goes to Marketing Center → ตั้งค่า → คลิก "เชื่อมต่อ Facebook Page"
 * 2. Redirect to Facebook Login
 * 3. Facebook redirects back to /owner/facebook/callback
 * 4. We exchange code → long-lived user token → Page Access Token
 * 5. If owner has multiple pages, show a picker; else save directly
 */
class FacebookController extends Controller
{
    private const STATE_KEY = '_fb_oauth_state';
    private const MSG_CONTENT = 'ฟีเจอร์การตลาดต้องสมัครแพ็กเกจ Starter ขึ้นไป';

    private function ensureContentPlan(bool $json = false): bool
    {
        if ($json) {
            return OwnerFeatureGate::denyJson($this, OwnerTier::FEATURE_CONTENT_PLAN, self::MSG_CONTENT);
        }
        return OwnerFeatureGate::denyPage(OwnerTier::FEATURE_CONTENT_PLAN, self::MSG_CONTENT);
    }

    private function ownerId(): int
    {
        return (int)(Auth::ownerId() ?? 0);
    }

    /** GET /owner/facebook/connect/{property_id} — start OAuth */
    public function connect(int $propertyId): void
    {
        if (!$this->ensureContentPlan()) {
            return;
        }
        $ownerId = $this->ownerId();
        if (!$ownerId) { redirect(url('/owner/login')); return; }

        if (!FacebookService::isConfigured()) {
            Session::flash('error', 'ยังไม่ได้ตั้งค่า Facebook App ID/Secret — ติดต่อผู้ดูแลระบบ');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        $prop = Database::fetch("SELECT id FROM properties WHERE id = :id AND owner_id = :o", ['id' => $propertyId, 'o' => $ownerId]);
        if (!$prop) { http_response_code(404); echo 'Not found'; return; }

        // CSRF-like state
        $state = bin2hex(random_bytes(16)) . ':' . $propertyId;
        Session::set(self::STATE_KEY, $state);

        $redirectUri = url('/owner/facebook/callback');
        redirect(FacebookService::oauthUrl($redirectUri, $state));
    }

    /** GET /owner/facebook/callback — handle OAuth code */
    public function callback(): void
    {
        if (!$this->ensureContentPlan()) {
            return;
        }
        $ownerId = $this->ownerId();
        if (!$ownerId) { redirect(url('/owner/login')); return; }

        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error_description'] ?? ($_GET['error'] ?? '');

        if ($error) {
            Session::flash('error', 'Facebook: ' . $error);
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        // Validate state
        $savedState = Session::get(self::STATE_KEY, '');
        Session::set(self::STATE_KEY, '');
        if (!$savedState || !hash_equals($savedState, $state)) {
            Session::flash('error', 'OAuth state ไม่ถูกต้อง — ลองใหม่');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        [, $propertyId] = explode(':', $state, 2) + ['', '0'];
        $propertyId = (int)$propertyId;

        $prop = Database::fetch("SELECT id FROM properties WHERE id = :id AND owner_id = :o", ['id' => $propertyId, 'o' => $ownerId]);
        if (!$prop) { http_response_code(404); echo 'Not found'; return; }

        // Exchange code → short-lived token
        $redirectUri  = url('/owner/facebook/callback');
        $shortToken   = FacebookService::exchangeCode($code, $redirectUri);
        if (!$shortToken) {
            Session::flash('error', 'ไม่สามารถรับ Access Token จาก Facebook ได้');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        // Exchange → long-lived user token (60 days)
        $userToken = FacebookService::longLivedUserToken($shortToken) ?? $shortToken;

        // Get pages this user manages
        $pages = FacebookService::getUserPages($userToken);
        if (empty($pages)) {
            Session::flash('error', 'ไม่พบ Facebook Page ที่คุณดูแลอยู่ — ตรวจสอบว่า App มี pages_manage_posts permission');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        if (count($pages) === 1) {
            // Save directly
            $this->storePageToken($propertyId, $pages[0]);
            Session::flash('success', 'เชื่อมต่อ Facebook Page "' . $pages[0]['name'] . '" สำเร็จ!');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        // Multiple pages — store pages + user token in session, show picker
        Session::set('_fb_pages', json_encode($pages));
        Session::set('_fb_prop_id', $propertyId);
        redirect(url('/owner/facebook/pick-page'));
    }

    /** GET /owner/facebook/pick-page — choose which page to connect */
    public function pickPage(): void
    {
        if (!$this->ensureContentPlan()) {
            return;
        }
        $ownerId = $this->ownerId();
        if (!$ownerId) { redirect(url('/owner/login')); return; }

        $pagesJson = Session::get('_fb_pages', '');
        $propId    = (int)Session::get('_fb_prop_id', 0);
        $pages     = $pagesJson ? json_decode($pagesJson, true) : [];

        if (!$pages || !$propId) {
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        // Render simple page-picker view
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>เลือก Facebook Page</title>';
        echo '<link rel="stylesheet" href="' . asset('css/app.css') . '"></head><body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">';
        echo '<div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">';
        echo '<h1 class="text-lg font-bold text-slate-800 mb-1">เลือก Facebook Page</h1>';
        echo '<p class="text-sm text-slate-500 mb-4">เลือก Page ที่ต้องการเชื่อมต่อกับที่พักนี้</p>';
        echo '<div class="space-y-2">';
        foreach ($pages as $page) {
            echo '<form method="post" action="' . e(url('/owner/facebook/save-page')) . '" class="block">';
            echo Csrf::field();
            echo '<input type="hidden" name="prop" value="' . (int)$propId . '">';
            echo '<input type="hidden" name="page_id" value="' . e($page['id']) . '">';
            echo '<button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:border-primary-400 hover:bg-primary-50 transition text-left">';
            echo '<span class="text-2xl">📘</span>';
            echo '<div><div class="font-semibold text-slate-800 text-sm">' . e($page['name']) . '</div>';
            echo '<div class="text-xs text-slate-400">' . e($page['category'] ?? '') . ' · ID: ' . e($page['id']) . '</div></div>';
            echo '</button></form>';
        }
        echo '</div></div></body></html>';
    }

    /** POST /owner/facebook/save-page — save selected page from picker */
    public function savePage(): void
    {
        if (!$this->ensureContentPlan()) {
            return;
        }
        $ownerId = $this->ownerId();
        if (!$ownerId) { redirect(url('/owner/login')); return; }

        $propId    = (int)($_POST['prop'] ?? $_GET['prop'] ?? 0);
        $pageId    = trim((string)($_POST['page_id'] ?? $_GET['page_id'] ?? ''));
        $pagesJson = Session::get('_fb_pages', '');
        $pages     = $pagesJson ? json_decode($pagesJson, true) : [];

        $page = current(array_filter($pages, fn($p) => $p['id'] === $pageId));
        if (!$page || !$propId) {
            Session::flash('error', 'ไม่พบข้อมูล Page');
            redirect(url('/owner/content-plans?tab=settings'));
            return;
        }

        $prop = Database::fetch("SELECT id FROM properties WHERE id = :id AND owner_id = :o", ['id' => $propId, 'o' => $ownerId]);
        if (!$prop) { http_response_code(403); echo 'Forbidden'; return; }

        Session::set('_fb_pages', '');
        Session::set('_fb_prop_id', '');

        $this->storePageToken($propId, $page);
        Session::flash('success', 'เชื่อมต่อ Facebook Page "' . $page['name'] . '" สำเร็จ!');
        redirect(url('/owner/content-plans?tab=settings'));
    }

    /** POST /owner/facebook/disconnect/{property_id} */
    public function disconnect(int $propertyId): void
    {
        if (!$this->ensureContentPlan()) {
            return;
        }
        $ownerId = $this->ownerId();
        if (!$ownerId) { redirect(url('/owner/login')); return; }

        $prop = Database::fetch("SELECT id FROM properties WHERE id = :id AND owner_id = :o", ['id' => $propertyId, 'o' => $ownerId]);
        if (!$prop) { http_response_code(404); echo 'Not found'; return; }

        $update = [
            'facebook_page_id'    => null,
            'facebook_page_name'  => null,
            'facebook_page_token' => null,
        ];
        if (Database::tableHasColumn('properties', 'facebook_token_expiry')) {
            $update['facebook_token_expiry'] = null;
        }
        Database::update('properties', $update, 'id = :id', ['id' => $propertyId]);

        Session::flash('success', 'ตัดการเชื่อมต่อ Facebook Page แล้ว');
        redirect(url('/owner/content-plans?tab=settings'));
    }

    /** Persist page access token to properties table */
    private function storePageToken(int $propId, array $page): void
    {
        $update = [
            'facebook_page_id'    => $page['id'],
            'facebook_page_name'  => mb_substr($page['name'], 0, 200),
            'facebook_page_token' => $page['access_token'],
        ];
        if (Database::tableHasColumn('properties', 'facebook_token_expiry')) {
            // Page tokens from long-lived user tokens don't expire but set a safe marker
            $update['facebook_token_expiry'] = date('Y-m-d H:i:s', strtotime('+60 days'));
        }
        Database::update('properties', $update, 'id = :id', ['id' => $propId]);
    }
}
