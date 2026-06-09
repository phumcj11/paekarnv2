<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;
use App\Support\PromptPayQr;

class PromptPayQrController extends Controller
{
    public function png(): void
    {
        $amount = (float) ($_GET['amount'] ?? 0);
        if ($amount < 0) {
            $amount = 0.0;
        }
        if ($amount > 1000000) {
            $amount = 1000000.0;
        }

        $id = (string) Setting::get('promptpay_id', '');
        if ($id === '') {
            $this->json(['ok' => false, 'msg' => 'PromptPay ID not configured'], 422);
        }

        $base64 = PromptPayQr::pngBase64($id, $amount);
        if ($base64 === null) {
            $this->json(['ok' => false, 'msg' => 'QR generation failed (gd extension required)'], 500);
        }

        if (!headers_sent()) {
            header('Cache-Control: public, max-age=60');
        }

        $this->json([
            'ok'      => true,
            'image'   => 'data:image/png;base64,' . $base64,
            'amount'  => $amount,
            'holder'  => (string) Setting::get('bank_holder', ''),
        ]);
    }
}
