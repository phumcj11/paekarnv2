<?php
/**
 * Provider Portal Routes (prefix /provider)
 */
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\Provider\DashboardController;
use App\Controllers\Provider\ProfileController;
use App\Controllers\Provider\ProductController;
use App\Controllers\Provider\OrderController;
use App\Controllers\Provider\RedeemController;

return function (Router $r): void {
    $r->get('/provider/login',     [AuthController::class, 'showProviderLogin']);
    $r->post('/provider/login',    [AuthController::class, 'providerLogin'])->middleware('csrf');
    $r->get('/provider/register',  [AuthController::class, 'showProviderRegister']);
    $r->post('/provider/register', [AuthController::class, 'providerRegister'])->middleware('csrf');

    $r->group('/provider', ['provider'], function (Router $r) {
        $r->get('',            [DashboardController::class, 'index']);
        $r->get('/dashboard',  [DashboardController::class, 'index']);

        $r->get('/profile',   [ProfileController::class, 'index']);
        $r->post('/profile',  [ProfileController::class, 'update'])->middleware('csrf');

        $r->get('/products',                    [ProductController::class, 'index']);
        $r->get('/products/create',             [ProductController::class, 'create']);
        $r->post('/products',                   [ProductController::class, 'store'])->middleware('csrf');
        $r->get('/products/{id:[0-9]+}/edit',   [ProductController::class, 'edit']);
        $r->post('/products/{id:[0-9]+}',       [ProductController::class, 'update'])->middleware('csrf');
        $r->post('/products/{id:[0-9]+}/submit-review', [ProductController::class, 'submitReview'])->middleware('csrf');

        $r->get('/orders',                  [OrderController::class, 'index']);
        $r->get('/orders/{id:[0-9]+}',      [OrderController::class, 'show']);
        $r->post('/orders/{id:[0-9]+}/confirm', [OrderController::class, 'confirm'])->middleware('csrf');

        $r->get('/redeem',  [RedeemController::class, 'index']);
        $r->post('/redeem', [RedeemController::class, 'lookup'])->middleware('csrf');
        $r->post('/redeem/use', [RedeemController::class, 'redeem'])->middleware('csrf');
    });
};
