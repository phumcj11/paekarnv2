<?php

declare(strict_types=1);

namespace App\Controllers\Provider;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;

class ProfileController extends Controller
{
    public function index(): void
    {
        View::render('provider/profile', [
            'page_title' => 'โปรไฟล์ & ธนาคาร',
            'provider'   => Auth::providerRow(),
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function update(): void
    {
        $pid = Auth::providerId();
        if (!$pid) {
            Session::flash('error', 'ไม่พบข้อมูลผู้ให้บริการ');
            back();
        }

        $provider = Auth::providerRow();
        $payload = [
            'contact_name' => trim((string)($_POST['contact_name'] ?? '')) ?: null,
            'phone'        => trim((string)($_POST['phone'] ?? '')) ?: null,
            'line_id'      => trim((string)($_POST['line_id'] ?? '')) ?: null,
            'email'        => trim((string)($_POST['email'] ?? '')) ?: null,
            'address'      => trim((string)($_POST['address'] ?? '')) ?: null,
        ];
        if (Database::tableHasColumn('activity_providers', 'bank_name')) {
            $payload['bank_name']    = trim((string)($_POST['bank_name'] ?? '')) ?: null;
            $payload['bank_account'] = trim((string)($_POST['bank_account'] ?? '')) ?: null;
            $payload['bank_holder']  = trim((string)($_POST['bank_holder'] ?? '')) ?: null;
        }
        if (Database::tableHasColumn('activity_providers', 'logo_image')) {
            $logo = isset($provider['logo_image']) ? (trim((string)$provider['logo_image']) ?: null) : null;
            if (!empty($_POST['remove_logo'])) {
                $logo = null;
            } elseif (!empty($_FILES['logo_image']['tmp_name'])) {
                try {
                    $uploaded = Upload::image('logo_image', 'activity-providers');
                    if ($uploaded) {
                        $logo = $uploaded;
                    }
                } catch (\Throwable $e) {
                    Session::flash('error', $e->getMessage());
                    back();
                }
            }
            $payload['logo_image'] = $logo;
        }

        Database::update('activity_providers', $payload, 'id = :id', ['id' => $pid]);
        Session::flash('success', 'บันทึกโปรไฟล์เรียบร้อย');
        redirect(url('/provider/profile'));
    }
}
