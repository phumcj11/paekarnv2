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

        // Bookings
        $r->get('/bookings',                       [OwnerBooking::class, 'index']);
        $r->get('/bookings/create',                [OwnerBooking::class, 'create']);
        $r->get('/api/line-contacts',              [OwnerBooking::class, 'lineContacts']);
        $r->get('/api/booking-quote',              [OwnerBooking::class, 'quote']);
        $r->post('/bookings',                      [OwnerBooking::class, 'store'])->middleware('csrf');
        $r->get('/bookings/{id:[0-9]+}',           [OwnerBooking::class, 'show']);
        $r->post('/bookings/{id:[0-9]+}',          [OwnerBooking::class, 'update'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/status',   [OwnerBooking::class, 'updateStatus'])->middleware('csrf');
        $r->post('/bookings/{id:[0-9]+}/payment',  [OwnerBooking::class, 'verifyPayment'])->middleware('csrf');

        // Coupon Verification
        $r->get('/coupons/verify',           [OwnerCoupon::class, 'index']);
        $r->get('/coupons/scan',             [OwnerCoupon::class, 'scan']);
        $r->post('/coupons/scan-resolve',    [OwnerCoupon::class, 'scanResolve'])->middleware('csrf');
        $r->post('/coupons/verify',          [OwnerCoupon::class, 'check'])->middleware('csrf');
        $r->post('/coupons/use',             [OwnerCoupon::class, 'markUsed'])->middleware('csrf');

        // Content Planner
        $r->get('/content-plans',                             [ContentPlanController::class, 'index']);
        $r->post('/content-plans',                            [ContentPlanController::class, 'store'])->middleware('csrf');
        $r->post('/content-plans/ai-generate',                [ContentPlanController::class, 'aiGenerate'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/update',         [ContentPlanController::class, 'update'])->middleware('csrf');
        $r->post('/content-plans/{id:[0-9]+}/delete',         [ContentPlanController::class, 'destroy'])->middleware('csrf');

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
