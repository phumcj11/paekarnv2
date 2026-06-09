<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = (int)Auth::id();
        $rows = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :u AND channel='in_app'
             ORDER BY id DESC LIMIT 100", ['u' => $uid]);
        Notification::markRead($uid);
        View::render('account/notifications', [
            'meta_title' => 'การแจ้งเตือน — แพกาญ.com', 'rows' => $rows,
        ]);
    }

    public function read(): void
    {
        $uid = (int)Auth::id();
        Notification::markRead($uid);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json(['ok' => true]);
            return;
        }
        back();
    }
}
