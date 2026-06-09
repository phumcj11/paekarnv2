<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\View;

/**
 * ศูนย์กลางการตลาด — ลิงก์ไปโมดูลที่มีอยู่ (บทความ, Banner, คูปอง, รีวิว)
 */
class PromotionsController extends Controller
{
    public function index(): void
    {
        View::render(
            'admin/promotions/index',
            ['page_title' => 'การตลาด & โปรโมชัน'],
            'layouts/admin'
        );
    }
}
