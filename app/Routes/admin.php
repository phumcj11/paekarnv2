<?php
/**
 * Admin Routes  (prefix /admin, middleware admin)
 */
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PropertyController as AdminProperty;
use App\Controllers\Admin\BookingController  as AdminBooking;
use App\Controllers\Admin\CouponController   as AdminCoupon;
use App\Controllers\Admin\OwnerController    as AdminOwner;
use App\Controllers\Admin\CustomerController as AdminCustomer;
use App\Controllers\Admin\BlogController     as AdminBlog;
use App\Controllers\Admin\ReviewController   as AdminReview;
use App\Controllers\Admin\SettingsController as AdminSettings;
use App\Controllers\Admin\LeadController     as AdminLead;
use App\Controllers\Admin\AutomationController as AdminAutomation;
use App\Controllers\Admin\AIController        as AdminAI;
use App\Controllers\Admin\LineController      as AdminLine;
use App\Controllers\Admin\BannerController    as AdminBanner;
use App\Controllers\Admin\PromotionsController as AdminPromotions;
use App\Controllers\Admin\UnitController       as AdminUnit;
use App\Controllers\Admin\MembershipController as AdminMembership;
use App\Controllers\Admin\ReviewVideoController as AdminReviewVideo;
use App\Controllers\Admin\ReviewFacebookPostController as AdminReviewFacebookPost;
use App\Controllers\Admin\VisitorPlaceController as AdminVisitorPlace;
use App\Controllers\Admin\ActivityProviderController as AdminActivityProvider;
use App\Controllers\Admin\ActivityProductController as AdminActivityProduct;
use App\Controllers\Admin\ActivityOrderController as AdminActivityOrder;
use App\Controllers\Admin\ActivityFeaturedCampaignController as AdminActivityFeatured;
use App\Controllers\Admin\ZoneController as AdminZone;
use App\Controllers\Admin\AnalyticsController as AdminAnalytics;
use App\Controllers\Admin\CouponCampaignController as AdminCouponCampaign;
use App\Controllers\Admin\ZoneAdCampaignController as AdminZoneAdCampaign;
use App\Controllers\Admin\AuditLogsController as AdminAuditLogs;
use App\Controllers\Admin\ToolsController as AdminTools;

return function (Router $r): void {
    // login admin (ใช้ AuthController ตัวเดียวกัน แต่ redirect ไป /admin)
    $r->get('/admin/login',  [AuthController::class, 'showAdminLogin']);
    $r->post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('csrf');

    $r->group('/admin', ['admin'], function (Router $r) {
        $r->get('',              [DashboardController::class, 'index']);
        $r->get('/dashboard',    [DashboardController::class, 'index']);
        $r->get('/analytics',    [AdminAnalytics::class, 'index']);

        // Properties
        $r->get('/properties',                       [AdminProperty::class, 'index']);
        $r->get('/properties/create',                [AdminProperty::class, 'create']);
        $r->post('/properties',                     [AdminProperty::class, 'store'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/edit',     [AdminProperty::class, 'edit']);
        $r->post('/properties/{id:[0-9]+}',         [AdminProperty::class, 'update'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/delete',  [AdminProperty::class, 'delete'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/images', [AdminProperty::class, 'uploadImage'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/images/{img:[0-9]+}/delete', [AdminProperty::class, 'deleteImage'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/approve',  [AdminProperty::class, 'approve'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/reject',   [AdminProperty::class, 'reject'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/feature',  [AdminProperty::class, 'feature'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/units',                  [AdminUnit::class, 'index']);
        $r->get('/properties/{id:[0-9]+}/units/create',           [AdminUnit::class, 'create']);
        $r->post('/properties/{id:[0-9]+}/units',                 [AdminUnit::class, 'store'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/edit',[AdminUnit::class, 'edit']);
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}',    [AdminUnit::class, 'update'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/approve', [AdminUnit::class, 'approve'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/reject',  [AdminUnit::class, 'reject'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/delete', [AdminUnit::class, 'delete'])->middleware('csrf');
        $r->post('/properties/{id:[0-9]+}/units/{uid:[0-9]+}/images/{img:[0-9]+}/delete', [AdminUnit::class, 'deleteUnitImage'])->middleware('csrf');
        $r->get('/properties/{id:[0-9]+}',           [AdminProperty::class, 'show']);

        // Bookings
        $r->get('/bookings',                         [AdminBooking::class, 'index']);
        $r->get('/bookings/create',                  [AdminBooking::class, 'create']);
        $r->post('/bookings',                        [AdminBooking::class, 'store'])->middleware('csrf');
        $r->get('/bookings/export.csv',              [AdminBooking::class, 'exportCsv']);
        $r->get('/bookings/{id:[0-9]+}/edit',        [AdminBooking::class, 'edit']);
        $r->post('/bookings/{id:[0-9]+}',            [AdminBooking::class, 'update'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/delete',     [AdminBooking::class, 'destroy'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/payment',     [AdminBooking::class, 'verifyPayment'])->middleware('csrf');
        $r->get('/bookings/{id:[0-9]+}',             [AdminBooking::class, 'show']);
        $r->post('/bookings/{id:[0-9]+}/status',     [AdminBooking::class, 'updateStatus'])->middleware('csrf');

        // Coupons
        $r->get('/coupons',                          [AdminCoupon::class, 'index']);
        $r->get('/coupons/create',                   [AdminCoupon::class, 'create']);
        $r->post('/coupons',                         [AdminCoupon::class, 'store'])->middleware('csrf');
        $r->get('/coupons/export.csv',               [AdminCoupon::class, 'exportCsv']);
        $r->get('/coupons/orders',                   [AdminCoupon::class, 'orders']);
        $r->get('/coupons/orders/create',            [AdminCoupon::class, 'orderCreate']);
        $r->post('/coupons/orders',                  [AdminCoupon::class, 'orderStore'])->middleware('csrf');
        $r->get('/coupons/orders/export.csv',        [AdminCoupon::class, 'exportOrdersCsv']);
        $r->get('/coupons/orders/{id:[0-9]+}',       [AdminCoupon::class, 'orderShow']);
        $r->post('/coupons/orders/{id:[0-9]+}',      [AdminCoupon::class, 'orderUpdate'])->middleware('csrf');
        $r->post('/coupons/orders/{id:[0-9]+}/cancel', [AdminCoupon::class, 'orderCancel'])->middleware('csrf');
        $r->post('/coupons/orders/{id:[0-9]+}/approve', [AdminCoupon::class, 'approveOrder'])->middleware('csrf');
        $r->get('/coupons/{id:[0-9]+}/edit',         [AdminCoupon::class, 'edit']);
        $r->get('/coupons/{id:[0-9]+}',              [AdminCoupon::class, 'show']);
        $r->post('/coupons/{id:[0-9]+}',             [AdminCoupon::class, 'update'])->middleware('csrf');
        $r->post('/coupons/{id:[0-9]+}/delete',       [AdminCoupon::class, 'destroy'])->middleware('csrf');
        $r->post('/coupons/{id:[0-9]+}/status',       [AdminCoupon::class, 'setCouponStatus'])->middleware('csrf');

        // Coupon campaigns (หลายมูลค่า — scaffolding)
        $r->get('/coupon-campaigns',                       [AdminCouponCampaign::class, 'index']);
        $r->get('/coupon-campaigns/create',               [AdminCouponCampaign::class, 'create']);
        $r->post('/coupon-campaigns',                     [AdminCouponCampaign::class, 'store'])->middleware('csrf');
        $r->get('/coupon-campaigns/{id:[0-9]+}/edit',      [AdminCouponCampaign::class, 'edit']);
        $r->post('/coupon-campaigns/{id:[0-9]+}',       [AdminCouponCampaign::class, 'update'])->middleware('csrf');
        $r->post('/coupon-campaigns/{id:[0-9]+}/delete', [AdminCouponCampaign::class, 'delete'])->middleware('csrf');

        // Zone ads (แบนเนอร์โซนหน้าแรก)
        $r->get('/zone-ads',                       [AdminZoneAdCampaign::class, 'index']);
        $r->get('/zone-ads/create',               [AdminZoneAdCampaign::class, 'create']);
        $r->post('/zone-ads',                     [AdminZoneAdCampaign::class, 'store'])->middleware('csrf');
        $r->get('/zone-ads/{id:[0-9]+}/edit',      [AdminZoneAdCampaign::class, 'edit']);
        $r->post('/zone-ads/{id:[0-9]+}',       [AdminZoneAdCampaign::class, 'update'])->middleware('csrf');
        $r->post('/zone-ads/{id:[0-9]+}/delete', [AdminZoneAdCampaign::class, 'delete'])->middleware('csrf');

        // Audit trail
        $r->get('/audit-logs', [AdminAuditLogs::class, 'index']);

        // Owner membership orders & plans
        $r->get('/membership/plans',                        [AdminMembership::class, 'plans']);
        $r->get('/membership/plans/create',                [AdminMembership::class, 'planCreate']);
        $r->post('/membership/plans',                      [AdminMembership::class, 'planStore'])->middleware('csrf');
        $r->get('/membership/plans/{id:[0-9]+}/edit',       [AdminMembership::class, 'planEdit']);
        $r->post('/membership/plans/{id:[0-9]+}',          [AdminMembership::class, 'planUpdate'])->middleware('csrf');
        $r->post('/membership/plans/{id:[0-9]+}/delete',   [AdminMembership::class, 'planDelete'])->middleware('csrf');
        $r->post('/membership/plans/{id:[0-9]+}/toggle-active', [AdminMembership::class, 'planToggleActive'])->middleware('csrf');
        $r->post('/membership/tier-features',             [AdminMembership::class, 'saveTierFeatures']);
        $r->get('/membership/orders',                      [AdminMembership::class, 'orders']);
        $r->post('/membership/orders/{id:[0-9]+}/approve', [AdminMembership::class, 'approve'])->middleware('csrf');
        $r->post('/membership/orders/{id:[0-9]+}/cancel',  [AdminMembership::class, 'cancel'])->middleware('csrf');
        // Owners
        $r->get('/owners',                           [AdminOwner::class, 'index']);
        $r->get('/owners/create',                    [AdminOwner::class, 'create']);
        $r->post('/owners',                          [AdminOwner::class, 'store'])->middleware('csrf');
        $r->get('/owners/{id:[0-9]+}/edit',         [AdminOwner::class, 'edit']);
        $r->post('/owners/{id:[0-9]+}',              [AdminOwner::class, 'update'])->middleware('csrf');
        $r->post('/owners/{id:[0-9]+}/delete',       [AdminOwner::class, 'destroy'])->middleware('csrf');
        $r->post('/owners/{id:[0-9]+}/status',       [AdminOwner::class, 'status'])->middleware('csrf');
        $r->get('/owners/{id:[0-9]+}',               [AdminOwner::class, 'show']);

        // Customers
        $r->get('/customers',                           [AdminCustomer::class, 'index']);
        $r->get('/customers/create',                    [AdminCustomer::class, 'create']);
        $r->post('/customers',                          [AdminCustomer::class, 'store'])->middleware('csrf');
        $r->get('/customers/{id:[0-9]+}/edit',         [AdminCustomer::class, 'edit']);
        $r->post('/customers/{id:[0-9]+}',              [AdminCustomer::class, 'update'])->middleware('csrf');
        $r->post('/customers/{id:[0-9]+}/delete',       [AdminCustomer::class, 'destroy'])->middleware('csrf');
        $r->get('/customers/{id:[0-9]+}',               [AdminCustomer::class, 'show']);

        // Reviews
        $r->get('/reviews',                          [AdminReview::class, 'index']);
        $r->get('/reviews/create',                   [AdminReview::class, 'create']);
        $r->post('/reviews',                         [AdminReview::class, 'store'])->middleware('csrf');
        $r->get('/reviews/{id:[0-9]+}/edit',         [AdminReview::class, 'edit']);
        $r->post('/reviews/{id:[0-9]+}',             [AdminReview::class, 'update'])->middleware('csrf');
        $r->post('/reviews/{id:[0-9]+}/approve',     [AdminReview::class, 'approve'])->middleware('csrf');
        $r->post('/reviews/{id:[0-9]+}/delete',      [AdminReview::class, 'delete'])->middleware('csrf');

        // Review videos (YouTube)
        $r->get('/review-videos',                           [AdminReviewVideo::class, 'index']);
        $r->get('/review-videos/create',                    [AdminReviewVideo::class, 'create']);
        $r->post('/review-videos',                          [AdminReviewVideo::class, 'store'])->middleware('csrf');
        $r->get('/review-videos/{id:[0-9]+}/edit',           [AdminReviewVideo::class, 'edit']);
        $r->post('/review-videos/{id:[0-9]+}',               [AdminReviewVideo::class, 'update'])->middleware('csrf');
        $r->post('/review-videos/{id:[0-9]+}/delete',       [AdminReviewVideo::class, 'delete'])->middleware('csrf');

        // Review Facebook posts (Embedded Post)
        $r->get('/review-facebook-posts',                    [AdminReviewFacebookPost::class, 'index']);
        $r->get('/review-facebook-posts/create',             [AdminReviewFacebookPost::class, 'create']);
        $r->post('/review-facebook-posts',                   [AdminReviewFacebookPost::class, 'store'])->middleware('csrf');
        $r->get('/review-facebook-posts/{id:[0-9]+}/edit', [AdminReviewFacebookPost::class, 'edit']);
        $r->post('/review-facebook-posts/{id:[0-9]+}',       [AdminReviewFacebookPost::class, 'update'])->middleware('csrf');
        $r->post('/review-facebook-posts/{id:[0-9]+}/delete', [AdminReviewFacebookPost::class, 'delete'])->middleware('csrf');

        // Visitor places (ที่เที่ยว)
        $r->get('/visitor-places',                           [AdminVisitorPlace::class, 'index']);
        $r->get('/visitor-places/create',                    [AdminVisitorPlace::class, 'create']);
        $r->post('/visitor-places',                          [AdminVisitorPlace::class, 'store'])->middleware('csrf');
        $r->get('/visitor-places/{id:[0-9]+}/edit',           [AdminVisitorPlace::class, 'edit']);
        $r->post('/visitor-places/{id:[0-9]+}',               [AdminVisitorPlace::class, 'update'])->middleware('csrf');
        $r->post('/visitor-places/{id:[0-9]+}/delete',       [AdminVisitorPlace::class, 'delete'])->middleware('csrf');

        // Activities marketplace
        $r->get('/activity-providers',                         [AdminActivityProvider::class, 'index']);
        $r->get('/activity-providers/create',                  [AdminActivityProvider::class, 'create']);
        $r->post('/activity-providers',                        [AdminActivityProvider::class, 'store'])->middleware('csrf');
        $r->get('/activity-providers/{id:[0-9]+}/edit',         [AdminActivityProvider::class, 'edit']);
        $r->post('/activity-providers/{id:[0-9]+}',             [AdminActivityProvider::class, 'update'])->middleware('csrf');
        $r->post('/activity-providers/{id:[0-9]+}/delete',     [AdminActivityProvider::class, 'delete'])->middleware('csrf');
        $r->post('/activity-providers/{id:[0-9]+}/partner-status', [AdminActivityProvider::class, 'partnerStatus'])->middleware('csrf');
        $r->post('/activity-providers/{id:[0-9]+}/subscription', [AdminActivityProvider::class, 'saveSubscription'])->middleware('csrf');
        $r->get('/activity-products',                          [AdminActivityProduct::class, 'index']);
        $r->get('/activity-products/create',                   [AdminActivityProduct::class, 'create']);
        $r->post('/activity-products',                         [AdminActivityProduct::class, 'store'])->middleware('csrf');
        $r->get('/activity-products/{id:[0-9]+}/edit',          [AdminActivityProduct::class, 'edit']);
        $r->post('/activity-products/{id:[0-9]+}',              [AdminActivityProduct::class, 'update'])->middleware('csrf');
        $r->post('/activity-products/{id:[0-9]+}/delete',      [AdminActivityProduct::class, 'delete'])->middleware('csrf');
        $r->post('/activity-products/{id:[0-9]+}/publish',     [AdminActivityProduct::class, 'publish'])->middleware('csrf');
        $r->post('/activity-products/{id:[0-9]+}/reject',      [AdminActivityProduct::class, 'reject'])->middleware('csrf');
        $r->get('/activity-orders',                            [AdminActivityOrder::class, 'index']);
        $r->get('/activity-orders/{id:[0-9]+}',                [AdminActivityOrder::class, 'show']);
        $r->post('/activity-orders/{id:[0-9]+}/status',        [AdminActivityOrder::class, 'updateStatus'])->middleware('csrf');
        $r->post('/activity-orders/{id:[0-9]+}/mark-payout',   [AdminActivityOrder::class, 'markPayout'])->middleware('csrf');
        $r->post('/activity-orders/{id:[0-9]+}/clear-payout',  [AdminActivityOrder::class, 'clearPayout'])->middleware('csrf');
        $r->get('/activity-featured',                          [AdminActivityFeatured::class, 'index']);
        $r->get('/activity-featured/create',                   [AdminActivityFeatured::class, 'create']);
        $r->post('/activity-featured',                         [AdminActivityFeatured::class, 'store'])->middleware('csrf');
        $r->get('/activity-featured/{id:[0-9]+}/edit',         [AdminActivityFeatured::class, 'edit']);
        $r->post('/activity-featured/{id:[0-9]+}',             [AdminActivityFeatured::class, 'update'])->middleware('csrf');
        $r->post('/activity-featured/{id:[0-9]+}/delete',      [AdminActivityFeatured::class, 'delete'])->middleware('csrf');

        // Zones master list (dropdown / ordering)
        $r->get('/zones',                                  [AdminZone::class, 'index']);
        $r->get('/zones/create',                           [AdminZone::class, 'create']);
        $r->post('/zones',                                 [AdminZone::class, 'store'])->middleware('csrf');
        $r->get('/zones/{id:[0-9]+}/edit',                [AdminZone::class, 'edit']);
        $r->post('/zones/{id:[0-9]+}',                    [AdminZone::class, 'update'])->middleware('csrf');
        $r->post('/zones/{id:[0-9]+}/delete',             [AdminZone::class, 'delete'])->middleware('csrf');

        // Leads
        $r->get('/leads',                            [AdminLead::class, 'index']);
        $r->post('/leads/{id:[0-9]+}/status',       [AdminLead::class, 'updateStatus'])->middleware('csrf');
        $r->post('/leads/{id:[0-9]+}/note',         [AdminLead::class, 'updateNote'])->middleware('csrf');

        // Marketing hub (บทความ / Banner / คูปอง — ลิงก์รวม)
        $r->get('/promotions',                       [AdminPromotions::class, 'index']);

        // Blog
        $r->get('/blog',                             [AdminBlog::class, 'index']);
        $r->get('/blog/create',                      [AdminBlog::class, 'create']);
        $r->post('/blog',                            [AdminBlog::class, 'store'])->middleware('csrf');
        $r->get('/blog/{id:[0-9]+}/edit',            [AdminBlog::class, 'edit']);
        $r->post('/blog/{id:[0-9]+}',                [AdminBlog::class, 'update'])->middleware('csrf');
        $r->post('/blog/{id:[0-9]+}/delete',         [AdminBlog::class, 'delete'])->middleware('csrf');

        // Homepage banners
        $r->get('/banners',                          [AdminBanner::class, 'index']);
        $r->get('/banners/create',                   [AdminBanner::class, 'create']);
        $r->post('/banners',                         [AdminBanner::class, 'store'])->middleware('csrf');
        $r->get('/banners/{id:[0-9]+}/edit',         [AdminBanner::class, 'edit']);
        $r->post('/banners/{id:[0-9]+}',            [AdminBanner::class, 'update'])->middleware('csrf');
        $r->post('/banners/{id:[0-9]+}/delete',      [AdminBanner::class, 'delete'])->middleware('csrf');

        // Settings
        $r->get('/settings',                         [AdminSettings::class, 'index']);
        $r->post('/settings',                        [AdminSettings::class, 'update'])->middleware('csrf');

        // Tools (one-shot maintenance)
        $r->get('/tools/images',                     [AdminTools::class, 'imageOptimizer']);
        $r->post('/tools/images/run',                [AdminTools::class, 'runImageOptimizer'])->middleware('csrf');

        // Phase 3: Automation
        $r->get('/automation',                       [AdminAutomation::class, 'index']);
        $r->post('/automation/run',                  [AdminAutomation::class, 'run'])->middleware('csrf');

        // Phase 3: AI
        $r->get('/ai',                               [AdminAI::class, 'settings']);
        $r->post('/ai',                              [AdminAI::class, 'saveSettings'])->middleware('csrf');
        $r->post('/ai/test',                         [AdminAI::class, 'test'])->middleware('csrf');
        $r->get('/ai/kb',                            [AdminAI::class, 'kbIndex']);
        $r->get('/ai/kb/form',                       [AdminAI::class, 'kbForm']);
        $r->post('/ai/kb',                           [AdminAI::class, 'kbSave'])->middleware('csrf');
        $r->post('/ai/kb/{id:[0-9]+}/delete',        [AdminAI::class, 'kbDelete'])->middleware('csrf');
        $r->get('/ai/chats',                         [AdminAI::class, 'chats']);

        // Phase 3: LINE
        $r->get('/line',                             [AdminLine::class, 'settings']);
        $r->post('/line',                            [AdminLine::class, 'saveSettings'])->middleware('csrf');
        $r->post('/line/test',                       [AdminLine::class, 'test'])->middleware('csrf');
    });
};
