<?php
/**
 * Customer / Public Routes
 */
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\PropertyController;
use App\Controllers\CouponController;
use App\Controllers\BookingController;
use App\Controllers\BlogController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\NotificationController;
use App\Controllers\AIController;
use App\Controllers\LineController;
use App\Controllers\SeoController;
use App\Controllers\GuestSeekController;
use App\Controllers\ReviewVideosController;
use App\Controllers\ReviewsController;
use App\Controllers\PlacesController;
use App\Controllers\ActivitiesController;
use App\Controllers\ActivityCheckoutController;
use App\Controllers\PromptPayQrController;
use App\Controllers\TrackOrderController;
use App\Controllers\CompareController;

return function (Router $r): void {
    $r->get('/robots.txt',   [SeoController::class, 'robots']);
    $r->get('/sitemap.xml',  [SeoController::class, 'sitemap']);

    // ---------- Home ----------
    $r->get('/',                [HomeController::class, 'index']);

    // ---------- Properties ----------
    $r->get('/properties',                        [PropertyController::class, 'index']);
    $r->get('/rafts',                             [PropertyController::class, 'rafts']);
    $r->get('/resorts',                           [PropertyController::class, 'resorts']);
    $r->get('/hotels',                            [PropertyController::class, 'hotels']);
    $r->get('/stays',                             [PropertyController::class, 'stays']);
    $r->get('/pool-villas',                       [PropertyController::class, 'poolVillas']);
    $r->get('/camping',                           [PropertyController::class, 'camping']);
    $r->get('/property/lead/{id:[0-9]+}',         [PropertyController::class, 'leadClick']);
    $r->get('/property/{slug}',                   [PropertyController::class, 'show']);

    // ---------- Compare units ----------
    $r->get('/compare',                           [CompareController::class, 'index']);
    $r->get('/api/compare/items',                 [CompareController::class, 'items']);
    $r->post('/api/compare/sync',                 [CompareController::class, 'sync']);
    $r->post('/api/compare/clear',                [CompareController::class, 'clear']);

    // ---------- Coupons ----------
    $r->get('/coupons',                           [CouponController::class, 'index']);
    $r->get('/coupons/buy',                       [CouponController::class, 'buy']);
    $r->post('/coupons/checkout',                 [CouponController::class, 'checkout'])->middleware('csrf');
    $r->get('/coupons/success/{order_no}',        [CouponController::class, 'success']);

    // ---------- Guest: ขอให้ช่วยหาที่พัก ----------
    $r->get('/guest-seek',       [GuestSeekController::class, 'show']);
    $r->post('/guest-seek',      [GuestSeekController::class, 'store'])->middleware('csrf');
    $r->get('/guest-seek/thanks', [GuestSeekController::class, 'thanks']);

    // ---------- Bookings (info-only ก็ใช้ route นี้ได้) ----------
    $r->get('/booking/create/{property_id:[0-9]+}', [BookingController::class, 'create']);
    $r->post('/booking',                            [BookingController::class, 'store'])->middleware('csrf');
    $r->get('/booking/success/{code}',              [BookingController::class, 'success']);

    // ---------- Blog ----------
    $r->get('/blog',          [BlogController::class, 'index']);
    $r->get('/blog/{slug}',   [BlogController::class, 'show']);

    // ---------- Featured YouTube videos ----------
    $r->get('/videos',        [ReviewVideosController::class, 'index']);

    // ---------- Reviews hub (วิดีโอ + รีวิวผู้เข้าพัก) ----------
    $r->get('/reviews',       [ReviewsController::class, 'index']);

    // ---------- Visitor places (ที่เที่ยว / POI) ----------
    $r->get('/places',              [PlacesController::class, 'index']);
    $r->get('/places/{slug}',       [PlacesController::class, 'show']);

    // ---------- Activities marketplace ----------
    $r->get('/activities',                       [ActivitiesController::class, 'index']);
    $r->get('/activities/success/{order_no}',    [ActivityCheckoutController::class, 'success']);
    $r->get('/activities/lead/{id:[0-9]+}',      [ActivitiesController::class, 'leadClick']);
    $r->get('/activities/{slug}',                [ActivitiesController::class, 'show']);
    $r->get('/activity/checkout/{id:[0-9]+}',    [ActivityCheckoutController::class, 'buy']);
    $r->post('/activity/checkout/{id:[0-9]+}',   [ActivityCheckoutController::class, 'checkout'])->middleware('csrf');

    // ---------- Auth ----------
    $r->get('/login',         [AuthController::class, 'showLogin'])->middleware('guest');
    $r->post('/login',        [AuthController::class, 'login'])->middleware('csrf');
    $r->get('/register',      [AuthController::class, 'showRegister'])->middleware('guest');
    $r->post('/register',     [AuthController::class, 'register'])->middleware('csrf');
    $r->post('/logout',       [AuthController::class, 'logout'])->middleware('csrf');

    // ---------- Account (ต้อง login) ----------
    $r->group('/account', ['auth'], function (Router $r) {
        $r->get('',          [AccountController::class, 'index']);
        $r->get('/bookings', [AccountController::class, 'bookings']);
        $r->get('/coupons',  [AccountController::class, 'coupons']);
        $r->get('/favorites',[AccountController::class, 'favorites']);
        $r->get('/profile',  [AccountController::class, 'profile']);
        $r->get('/notifications', [NotificationController::class, 'index']);
    });

    $r->post('/notifications/read', [NotificationController::class, 'read']);

    // ---------- Payment helpers ----------
    $r->get('/api-promptpay-qr',   [PromptPayQrController::class, 'png']);
    $r->get('/api-validate-coupon', [CouponController::class, 'validateApi']);

    // ---------- Order tracking (ไม่ต้อง login) ----------
    $r->get('/track-order',  [TrackOrderController::class, 'show']);
    $r->post('/track-order', [TrackOrderController::class, 'lookup'])->middleware('csrf');

    // ---------- Phase 3: AI ----------
    $r->post('/ai/chat',          [AIController::class, 'chat']);
    $r->post('/ai/smart-search',  [AIController::class, 'smartSearch']);
    $r->post('/ai/generate',      [AIController::class, 'generate'])->middleware('auth');
    $r->post('/ai/translate',     [AIController::class, 'translate'])->middleware('auth');

    // ---------- Phase 3: LINE ----------
    $r->get('/line/login',         [LineController::class, 'login'])->middleware('auth');
    $r->get('/line/callback',      [LineController::class, 'callback']);
    $r->get('/line/unlink',        [LineController::class, 'unlink'])->middleware('auth');
    $r->post('/line/webhook',      [LineController::class, 'webhook']);

    // ---------- Static ----------
    $r->get('/about',         function () { \App\Core\View::render('static/about'); });
    $r->get('/contact',       function () {
        $site = \App\Models\Setting::get('site_name', 'แพกาญ.com');
        \App\Core\View::render('static/contact', [
            'meta_title'       => 'ติดต่อเรา — ' . $site,
            'meta_description' => 'โทร อีเมล LINE และโซเชียลมีเดียของ ' . $site . ' — ครบในหน้าเดียว',
            'meta_canonical'   => url('/contact'),
        ]);
    });
};
