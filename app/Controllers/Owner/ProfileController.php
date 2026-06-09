<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $owner = Database::fetch("SELECT * FROM owners WHERE user_id = :u", ['u' => $user['id']]);
        View::render('owner/profile/index', [
            'page_title' => 'โปรไฟล์ + บัญชีธนาคาร',
            'user' => $user, 'owner' => $owner,
        ], 'layouts/owner');
    }

    public function update(): void
    {
        $user = Auth::user();

        // Update users
        $userUpdate = [
            'name'  => trim((string)($_POST['name']  ?? $user['name'])),
            'phone' => trim((string)($_POST['phone'] ?? $user['phone'])),
        ];
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                Session::flash('error', 'รหัสผ่านอย่างน้อย 8 ตัวอักษร'); back();
            }
            $userUpdate['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }
        User::update($user['id'], $userUpdate);

        // Upsert owner
        $exists = Database::fetch("SELECT id FROM owners WHERE user_id = :u", ['u' => $user['id']]);
        $ownerData = [
            'business_name' => $_POST['business_name'] ?? null,
            'tax_id'        => $_POST['tax_id'] ?? null,
            'bank_name'     => $_POST['bank_name'] ?? null,
            'bank_account'  => $_POST['bank_account'] ?? null,
            'bank_holder'   => $_POST['bank_holder'] ?? null,
            'notes'         => $_POST['notes'] ?? null,
        ];
        if ($exists) {
            Database::update('owners', $ownerData, 'user_id = :u', ['u' => $user['id']]);
        } else {
            $ownerData['user_id'] = $user['id'];
            $ownerData['partner_status'] = 'pending';
            $newOwnerId = (int) Database::insert('owners', $ownerData);
            try {
                \App\Services\AdminApprovalNotifyService::partnerProfileRecorded(
                    $newOwnerId,
                    isset($ownerData['business_name']) ? (string) $ownerData['business_name'] : null
                );
            } catch (\Throwable $e) {
            }
        }

        Session::flash('success', 'บันทึกโปรไฟล์เรียบร้อย');
        redirect(url('/owner/profile'));
    }
}
