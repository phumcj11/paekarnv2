<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;
use App\Services\LineService;

class LineController extends Controller
{
    public function settings(): void
    {
        $hooks = Database::fetchAll("SELECT * FROM webhook_logs WHERE source='line' ORDER BY id DESC LIMIT 30");
        $linkedUsers = (int)Database::fetch("SELECT COUNT(*) c FROM users WHERE line_user_id IS NOT NULL")['c'];

        View::render('admin/line/settings', [
            'page_title' => 'LINE OA Integration',
            'enabled'    => Setting::get('line_enabled', '0'),
            'token'      => Setting::get('line_channel_access_token', ''),
            'secret'     => Setting::get('line_channel_secret', ''),
            'loginId'    => Setting::get('line_login_channel_id', ''),
            'loginSec'   => Setting::get('line_login_channel_secret', ''),
            'friendUrl'  => Setting::get('line_friend_url', ''),
            'adminUid'   => Setting::get('line_admin_user_id', ''),
            'webhookUrl' => rtrim((string)\App\Core\Application::$publicUrl, '/') . '/line/webhook',
            'callbackUrl'=> rtrim((string)\App\Core\Application::$publicUrl, '/') . '/line/callback',
            'hooks'      => $hooks, 'linkedUsers' => $linkedUsers,
        ], 'layouts/admin');
    }

    public function saveSettings(): void
    {
        foreach (['line_enabled','line_channel_access_token','line_channel_secret',
                  'line_login_channel_id','line_login_channel_secret','line_friend_url','line_admin_user_id'] as $k) {
            $v = $_POST[$k] ?? '';
            if ($k === 'line_enabled') $v = $v ? '1' : '0';
            Setting::set($k, (string)$v);
        }
        Session::flash('success', 'บันทึกการตั้งค่า LINE เรียบร้อย');
        redirect(url('/admin/line'));
    }

    public function test(): void
    {
        $uid = trim((string)($_POST['user_id'] ?? Setting::get('line_admin_user_id', '')));
        $msg = trim((string)($_POST['message'] ?? '🧪 ทดสอบจากแพกาญ.com'));
        if (!$uid) { Session::flash('error', 'กรุณาระบุ LINE User ID หรือผูก admin LINE ID ก่อน'); back(); }
        $ok = LineService::push($uid, $msg);
        Session::flash($ok ? 'success' : 'error', $ok ? '✓ ส่ง LINE สำเร็จ' : '✗ ส่งไม่สำเร็จ — ตรวจ token/userId');
        redirect(url('/admin/line'));
    }
}
