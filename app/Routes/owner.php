<?php
/**
 * Owner Portal Routes  (prefix /owner, middleware owner)
 */
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\Owner\DashboardController;
use App\Controllers\Owner\PropertyController;
use App\Controllers\Owner\UnitController;
use App\Controllers\Owner\AvailabilityController;
use App\Controllers\Owner\BookingController as OwnerBooking;
use App\Controllers\Owner\CouponController as OwnerCoupon;
use App\Controllers\Owner\ProfileController;
use App\Controllers\Owner\MembershipController;
use App\Controllers\Owner\ContentPlanController;
use App\Controllers\Owner\LineHubController;
use App\Controllers\Owner\LineContactController;
use App\Controllers\Owner\AnalyticsController as OwnerAnalytics;
use App\Controllers\Owner\AutomationController as OwnerAutomation;
use App\Controllers\Owner\FacebookController;

return function (Router $r): void {
    // Auth (no middleware)
    $r->get('/owner/login',     [AuthController::class, 'showOwnerLogin']);
    $r->post('/owner/login',    [AuthController::class, 'ownerLogin'])->middleware('csrf');
    $r->get('/owner/forgot-password',  [AuthController::class, 'showOwnerForgotPassword']);
    $r->post('/owner/forgot-password', [AuthController::class, 'ownerForgotPassword'])->middleware('csrf');
    $r->get('/owner/reset-password',   [AuthController::class, 'showOwnerResetPassword']);
    $r->post('/owner/reset-password',  [AuthController::class, 'ownerResetPassword'])->middleware('csrf');
    $r->get('/owner/register',  [AuthController::class, 'showOwnerRegister']);
    $r->post('/owner/register', [AuthController::class, 'ownerRegister'])->middleware('csrf');

    // Owner-only
    $r->group('/owner', ['owner'], function (Router $r) {
        $r->get('',           [DashboardController::class, 'index']);
        $r->get('/dashboard', [DashboardController::class, 'index']);

        // Properties
        $r->get('/properties',                              [PropertyController::class, 'index']);
        $r->get('/properties/create',                       [PropertyController::class, 'create']);
        $r->post('/properties',                             [PropertyController::class, 'store'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/edit',             [PropertyController::class, 'edit']);
        $r->post('/properties/{id:[0-9]+}',                 [PropertyController::class, 'update'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/delete',          [PropertyController::class, 'delete'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/images',          [PropertyController::class, 'uploadImage'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/images/{img:[0-9]+}/delete', [PropertyController::class, 'deleteImage'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/line-test',       [PropertyController::class, 'lineTest'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/line-rich-menu', [PropertyController::class, 'lineRichMenu'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/line',             [LineHubController::class, 'index']);
        $r->post('/properties/{id:[0-9]+}/line',            [LineHubController::class, 'save'])->middleware('csrf');

        // Units: nested CRUD + ทางลัดจากเมนู
        $r->get('/units', [UnitController::class, 'hub']);
        $r->get('/properties/{id:[0-9]+}/units',                  [UnitController::class, 'index']);
        $r->get('/properties/{id:[0-9]+}/units/create',           [UnitController::class, 'create']);
        $r->post('/properties/{id:[0-9]+}/units',                 [UnitController::class, 'store'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/edit',[UnitController::class, 'edit']);
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}',    [UnitController::class, 'update'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/delete', [UnitController::class, 'delete'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/images/{img:[0-9]+}/delete', [UnitController::class, 'deleteUnitImage'])->middleware('csrf');

        // Availability
        $r->get('/properties/{id:[0-9]+}/availability',           [AvailabilityController::class, 'index']);
        $r->post('/properties/{id:[0-9]+}/availability/save',     [AvailabilityController::class, 'save'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/availability/booking', [AvailabilityController::class, 'storeBooking'])->middleware('csrf');

        // Analytics
        $r->get('/analytics',            [OwnerAnalytics::class, 'index']);
        $r->get('/analytics/ai-summary', [OwnerAnalytics::class, 'aiSummary']);

        // Automation templates
        $r->get('/automation',                [OwnerAutomation::class, 'index']);
        $r->post('/automation/save',          [OwnerAutomation::class, 'save'])->middleware('csrf');
        $r->post('/automation/ai-draft',      [OwnerAutomation::class, 'aiDraft'])->middleware('csrf');
        $r->get('/automation/ai-campaign',    [OwnerAutomation::class, 'aiCampaign']);
        $r->get('/automation/cron-preview',   [OwnerAutomation::class, 'cronPreview']);

        // Bookings
        $r->get('/bookings',                       [OwnerBooking::class, 'index']);
        $r->get('/bookings/create',                [OwnerBooking::class, 'create']);
        $r->get('/api/line-contacts',              [OwnerBooking::class, 'lineContacts']);
        $r->post('/api/line-contacts/sync',        [OwnerBooking::class, 'syncLineContacts'])->middleware('csrf');

        // LINE Contacts management page
        $r->get('/line-contacts',                              [LineContactController::class, 'index']);
        $r->post('/line-contacts/{id:[0-9]+}/phone',           [LineContactController::class, 'updatePhone'])->middleware('csrf');
        $r->post('/line-contacts/{id:[0-9]+}/message',         [LineContactController::class, 'sendMessage'])->middleware('csrf');
        $r->post('/line-contacts/{id:[0-9]+}/tags',            [LineContactController::class, 'updateTags'])->middleware('csrf');
        $r->post('/line-contacts/{id:[0-9]+}/notes',           [LineContactController::class, 'updateNotes'])->middleware('csrf');
        $r->get('/line-contacts/{id:[0-9]+}/ai-reply',         [LineContactController::class, 'aiReply']);
        $r->post('/line-contacts/broadcast',                   [LineContactController::class, 'broadcast'])->middleware('csrf');
        $r->get('/api/booking-quote',              [OwnerBooking::class, 'quote']);
        $r->post('/bookings',                      [OwnerBooking::class, 'store'])->middleware('csrf');
        $r->get('/bookings/{id:[0-9]+}',           [OwnerBooking::class, 'show']);
        $r->post('/bookings/{id:[0-9]+}',          [OwnerBooking::class, 'update'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/status',   [OwnerBooking::class, 'updateStatus'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/delete',   [OwnerBooking::class, 'destroy'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/payment',  [OwnerBooking::class, 'verifyPayment'])->middleware('csrf');

        // Coupon Verification
        $r->get('/coupons/verify',           [OwnerCoupon::class, 'index']);
        $r->get('/coupons/scan',             [OwnerCoupon::class, 'scan']);
        $r->post('/coupons/scan-resolve',    [OwnerCoupon::class, 'scanResolve'])->middleware('csrf');
        $r->post('/coupons/verify',          [OwnerCoupon::class, 'check'])->middleware('csrf');
        $r->post('/coupons/use',             [OwnerCoupon::class, 'markUsed'])->middleware('csrf');

        // Content Planner / Marketing Center
        $r->get('/content-plans',                                   [ContentPlanController::class, 'index']);
        $r->post('/content-plans',                                  [ContentPlanController::class, 'store'])->middleware('csrf');
        $r->post('/content-plans/ai-generate',                      [ContentPlanController::class, 'aiGenerate'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/update',               [ContentPlanController::class, 'update'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/delete',               [ContentPlanController::class, 'destroy'])->middleware('csrf');
        // Group Posting Helper
        $r->post('/content-plans/groups/save',                      [ContentPlanController::class, 'groupSave'])->middleware('csrf');
        $r->post('/content-plans/groups/{id:[0-9]+}/delete',        [ContentPlanController::class, 'groupDelete'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/log-post',             [ContentPlanController::class, 'logPost'])->middleware('csrf');
        $r->get('/content-plans/{id:[0-9]+}/post-logs',             [ContentPlanController::class, 'postLogs']);
        // Lead Watchlist
        $r->post('/content-plans/leads/save',                       [ContentPlanController::class, 'leadSave'])->middleware('csrf');
        $r->post('/content-plans/leads/{id:[0-9]+}/delete',         [ContentPlanController::class, 'leadDelete'])->middleware('csrf');
        $r->get('/content-plans/leads/{id:[0-9]+}/ai-comment',      [ContentPlanController::class, 'leadAiComment']);
        // Social Settings + Image Picker
        $r->get('/content-plans/property-images',                   [ContentPlanController::class, 'propertyImages']);
        $r->post('/content-plans/social-save',                      [ContentPlanController::class, 'socialSave'])->middleware('csrf');
        $r->post('/content-plans/upload-image',                     [ContentPlanController::class, 'uploadImage'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/post-facebook',        [ContentPlanController::class, 'postToFacebook'])->middleware('csrf');
        // Facebook OAuth
        $r->get('/facebook/connect/{id:[0-9]+}',  [FacebookController::class, 'connect']);
        $r->get('/facebook/callback',             [FacebookController::class, 'callback']);
        $r->get('/facebook/pick-page',            [FacebookController::class, 'pickPage']);
        $r->post('/facebook/save-page',           [FacebookController::class, 'savePage'])->middleware('csrf');
        $r->post('/facebook/disconnect/{id:[0-9]+}', [FacebookController::class, 'disconnect'])->middleware('csrf');

        // Profile + Banking
        $r->get('/profile',          [ProfileController::class, 'index']);
        $r->post('/profile',         [ProfileController::class, 'update'])->middleware('csrf');

        // Membership (standard / VIP)
        $r->get('/membership',                  [MembershipController::class, 'index']);
        $r->get('/membership/buy',              [MembershipController::class, 'buy']);
        $r->post('/membership/checkout',        [MembershipController::class, 'checkout'])->middleware('csrf');
        $r->get('/membership/success/{order_no}', [MembershipController::class, 'success']);
    });
};
