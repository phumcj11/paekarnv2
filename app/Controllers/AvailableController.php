<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Services\AvailablePropertiesService as APS;

class AvailableController extends Controller
{
    /** GET /available-today */
    public function today(): void
    {
        $today  = date('Y-m-d');
        $type   = $_GET['type'] ?? null;
        $rows   = APS::findAvailableOn($today, $type ?: null);
        $weekend = APS::nextWeekendDates();

        View::render('available/index', [
            'meta_title'       => 'แพและที่พักว่างวันนี้ — ' . APS::thaiDate($today),
            'meta_description' => 'รายการแพและที่พักกาญจนบุรีที่ยังว่างวันนี้ จองได้ทันที ไม่ต้องรอ',
            'rows'             => $rows,
            'targetDate'       => $today,
            'dateLabel'        => 'วันนี้ — ' . APS::thaiDate($today),
            'mode'             => 'today',
            'weekend'          => $weekend,
            'filterType'       => $type,
        ], 'layouts/app');
    }

    /** GET /available-weekend */
    public function weekend(): void
    {
        $weekend  = APS::nextWeekendDates();
        $date     = ($_GET['day'] ?? '') === 'sunday' ? $weekend['sunday'] : $weekend['saturday'];
        $type     = $_GET['type'] ?? null;
        $dayKey   = $date === $weekend['sunday'] ? 'sunday' : 'saturday';
        $rows     = APS::findAvailableOn($date, $type ?: null);

        $dayTH = $dayKey === 'saturday' ? 'เสาร์' : 'อาทิตย์';
        View::render('available/index', [
            'meta_title'       => "แพและที่พักว่าง{$dayTH}นี้ — " . APS::thaiDate($date),
            'meta_description' => "รายการแพและที่พักกาญจนบุรีที่ยังว่าง{$dayTH}นี้ จองได้ทันที",
            'rows'             => $rows,
            'targetDate'       => $date,
            'dateLabel'        => "{$dayTH}นี้ — " . APS::thaiDate($date),
            'mode'             => 'weekend',
            'weekend'          => $weekend,
            'filterType'       => $type,
        ], 'layouts/app');
    }
}
