<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Services\BookingConfirmationService;

class BookingConfirmationController extends Controller
{
    public function show(string $code): void
    {
        $token   = $_GET['t'] ?? '';
        $booking = BookingConfirmationService::verify($code, $token);

        if (!$booking) {
            http_response_code(403);
            View::render('errors/403', ['message' => 'ลิงก์ใบยืนยันไม่ถูกต้องหรือหมดอายุ'], 'layouts/app');
            return;
        }

        View::render('bookings/confirmation', [
            'page_title' => 'ใบยืนยันการจอง #' . $booking['code'],
            'booking'    => $booking,
            'print_url'  => BookingConfirmationService::publicUrl($booking),
        ], 'layouts/app');
    }
}
