<?php
/**
 * One-shot Installer (XAMPP friendly)
 * - สร้าง DB + import schema.sql + seed.sql
 * - regenerate password hash ของบัญชี seed (กัน hash เพี้ยน)
 * - สร้างโฟลเดอร์ uploads/ + storage/
 *
 * เมื่อใช้งานเสร็จ ขอแนะนำให้ "ลบไฟล์นี้" ออกจาก production
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Application.php';
\App\Core\Application::boot(dirname(__DIR__));

use App\Core\Application;

$cfg     = Application::$config['db'];
$logs    = [];
$success = true;

function step(array &$logs, string $title, callable $fn): void {
    try {
        $msg = $fn();
        $logs[] = ['ok' => true, 'title' => $title, 'msg' => $msg ?: 'OK'];
    } catch (\Throwable $e) {
        $GLOBALS['success'] = false;
        $logs[] = ['ok' => false, 'title' => $title, 'msg' => $e->getMessage()];
    }
}

if (($_GET['run'] ?? '') === '1') {

    // 1) เชื่อมต่อ MySQL (ไม่เลือก DB)
    step($logs, '1) เชื่อมต่อ MySQL', function () use ($cfg) {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $GLOBALS['_root_pdo'] = $pdo;
        return "เชื่อมต่อ {$cfg['host']}:{$cfg['port']} สำเร็จ";
    });

    // 2) สร้าง DB ถ้ายังไม่มี
    step($logs, '2) สร้างฐานข้อมูล', function () use ($cfg) {
        /** @var PDO $pdo */
        $pdo = $GLOBALS['_root_pdo'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}` DEFAULT CHARACTER SET {$cfg['charset']} COLLATE {$cfg['collation']}");
        return "DB `{$cfg['database']}` พร้อมใช้งาน";
    });

    // 3) import schema.sql
    step($logs, '3) Import schema.sql', function () use ($cfg) {
        $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}", $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        if (!$sql) throw new \RuntimeException('ไม่พบไฟล์ database/schema.sql');
        $pdo->exec($sql);
        $GLOBALS['_app_pdo'] = $pdo;
        return 'สร้างตารางครบแล้ว';
    });

    // 4) import seed.sql
    step($logs, '4) Import seed.sql', function () use ($cfg) {
        /** @var PDO $pdo */
        $pdo = $GLOBALS['_app_pdo'];
        $sql = file_get_contents(dirname(__DIR__) . '/database/seed.sql');
        if (!$sql) throw new \RuntimeException('ไม่พบไฟล์ database/seed.sql');
        $pdo->exec($sql);
        return 'นำเข้า mockup data สำเร็จ';
    });

    // 5) regenerate password (กรณี hash ใน seed ใช้ไม่ได้)
    step($logs, '5) ตั้งรหัสผ่าน default', function () {
        $pdo = $GLOBALS['_app_pdo'];
        $defaults = [
            'admin@paekan.com'    => 'admin1234',
            'owner@paekan.com'    => 'owner1234',
            'owner2@paekan.com'   => 'owner1234',
            'customer@paekan.com' => 'customer1234',
            'praew@paekan.com'    => 'customer1234',
        ];
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE email = :e");
        foreach ($defaults as $email => $pass) {
            $stmt->execute(['p' => password_hash($pass, PASSWORD_BCRYPT), 'e' => $email]);
        }
        return 'อัปเดตรหัสผ่านบัญชีตัวอย่างเรียบร้อย';
    });

    // 6) สร้างโฟลเดอร์ uploads & storage
    step($logs, '6) สร้างโฟลเดอร์ uploads/storage', function () {
        $base = dirname(__DIR__);
        foreach (['public/uploads', 'public/uploads/slips', 'public/uploads/properties', 'storage/logs', 'storage/cache'] as $d) {
            $p = $base . '/' . $d;
            if (!is_dir($p)) mkdir($p, 0775, true);
        }
        return 'พร้อมใช้งาน';
    });
}

$baseUrl = Application::$publicUrl;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ติดตั้งแพกาญ.com</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= e(rtrim($baseUrl, '/')) ?>/assets/css/app.css">
<style>body{font-family:"Sarabun","Noto Sans Thai",ui-sans-serif,system-ui}</style>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="max-w-3xl mx-auto py-12 px-4">
  <div class="flex items-center gap-3 mb-6">
    <div class="w-12 h-12 rounded-2xl bg-primary text-white grid place-items-center font-bold text-xl">PK</div>
    <div>
      <h1 class="text-2xl font-bold">ติดตั้งแพกาญ.com</h1>
      <p class="text-slate-500 text-sm">One-shot installer สำหรับ XAMPP local</p>
    </div>
  </div>

  <?php if (empty($_GET['run'])): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-4">
    <h2 class="font-semibold text-lg mb-3">ขั้นตอนที่จะดำเนินการ</h2>
    <ol class="list-decimal list-inside space-y-1 text-slate-700">
      <li>เชื่อมต่อ MySQL ของ XAMPP</li>
      <li>สร้างฐานข้อมูล <code class="bg-slate-100 px-1 rounded">paekan_db</code> (ถ้ายังไม่มี)</li>
      <li>Import <code>database/schema.sql</code></li>
      <li>Import <code>database/seed.sql</code> (mockup ที่พัก/รีวิว/บล็อก)</li>
      <li>ตั้งรหัสผ่าน default ของบัญชีตัวอย่าง</li>
      <li>สร้างโฟลเดอร์ <code>public/uploads</code>, <code>storage/</code></li>
    </ol>
    <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-700">
      ⚠️ การติดตั้งจะ <b>truncate ข้อมูลเดิม</b> ในฐานข้อมูลนี้ทั้งหมด
    </div>
    <a href="?run=1" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-teal-700 transition">
      🚀 เริ่มติดตั้ง
    </a>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <ul class="divide-y divide-slate-100">
      <?php foreach ($logs as $log): ?>
      <li class="flex items-start gap-3 p-4">
        <div class="mt-0.5 w-7 h-7 rounded-full grid place-items-center text-white text-sm
          <?= $log['ok'] ? 'bg-emerald-500' : 'bg-rose-500' ?>">
          <?= $log['ok'] ? '✓' : '✕' ?>
        </div>
        <div class="flex-1">
          <div class="font-medium"><?= htmlspecialchars($log['title']) ?></div>
          <div class="text-sm <?= $log['ok'] ? 'text-slate-500' : 'text-rose-600' ?>"><?= htmlspecialchars($log['msg']) ?></div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <div class="p-5 border-t border-slate-100 bg-slate-50 flex items-center gap-3 flex-wrap">
      <?php if ($success): ?>
        <a href="<?= htmlspecialchars($baseUrl) ?>/" class="px-5 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-slate-700">→ เปิดหน้าเว็บ</a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/login" class="px-5 py-2.5 bg-accent text-white rounded-lg font-semibold hover:bg-teal-700">→ Admin Login</a>
        <span class="text-sm text-slate-500">admin@paekan.com / admin1234</span>
      <?php else: ?>
        <a href="?run=1" class="px-5 py-2.5 bg-amber-500 text-white rounded-lg">ลองติดตั้งใหม่</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <p class="text-center text-xs text-slate-400 mt-6">© แพกาญ.com — Installer</p>
</div>
</body>
</html>
