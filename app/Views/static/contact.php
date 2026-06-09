<?php
use App\Models\Setting;

$siteName = Setting::get('site_name', 'แพกาญ.com');
$phone    = trim((string) Setting::get('site_phone', ''));
$email    = trim((string) Setting::get('site_email', ''));
$address  = trim((string) Setting::get('site_address', ''));
$hours    = trim((string) Setting::get('contact_hours', 'จันทร์–อาทิตย์ 8:00–22:00 น.'));
$intro    = trim((string) Setting::get(
    'contact_linktree_intro',
    'ช่องทางของเราอยู่ที่เดียว — แตะลิงก์ด้านล่างได้เลย'
));

$lineFriend = trim((string) Setting::get('line_friend_url', ''));
$lineOa     = trim((string) Setting::get('line_oa', ''));
if ($lineFriend === '' && $lineOa !== '') {
    $handle     = str_starts_with($lineOa, '@') ? $lineOa : '@' . $lineOa;
    $lineFriend = 'https://line.me/R/ti/p/' . rawurlencode($handle);
}

$waDigits = preg_replace('/\D/', '', trim((string) Setting::get('social_whatsapp', '')));
if ($waDigits === '' && $phone !== '') {
    $waDigits = preg_replace('/\D/', '', $phone);
}

/** @var list<array{label:string,href:string,gradient:string,icon:string,right:string,target:string,rel:string}> $links */
$links = [];

if ($phone !== '') {
    $tel = preg_replace('/\D/', '', $phone);
    if ($tel !== '') {
        $links[] = [
            'label'     => 'โทรหาเรา',
            'href'      => 'tel:' . $tel,
            'gradient'  => 'bg-gradient-to-r from-sky-500 to-blue-700',
            'icon'      => 'phone',
            'right'     => 'chevron-right',
            'target'    => '_self',
            'rel'       => '',
        ];
    }
}

if ($email !== '') {
    $links[] = [
        'label'     => 'ส่งอีเมล',
        'href'      => 'mailto:' . $email,
        'gradient'  => 'bg-gradient-to-r from-amber-400 to-orange-600',
        'icon'      => 'mail',
        'right'     => 'chevron-right',
        'target'    => '_self',
        'rel'       => '',
    ];
}

if ($lineFriend !== '') {
    $links[] = [
        'label'     => 'LINE Official',
        'href'      => $lineFriend,
        'gradient'  => 'bg-gradient-to-r from-[#06C755] to-[#00a344]',
        'icon'      => 'message-circle',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

if ($waDigits !== '' && strlen($waDigits) >= 9) {
    $links[] = [
        'label'     => 'WhatsApp',
        'href'      => 'https://wa.me/' . $waDigits,
        'gradient'  => 'bg-gradient-to-r from-emerald-600 to-green-900',
        'icon'      => 'phone',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$wechatUrl = trim((string) Setting::get('wechat_url', ''));
if ($wechatUrl !== '') {
    $links[] = [
        'label'     => 'WeChat',
        'href'      => $wechatUrl,
        'gradient'  => 'bg-gradient-to-r from-teal-500 to-emerald-800',
        'icon'      => 'messages-square',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$tiktokUrl = trim((string) Setting::get('tiktok_url', ''));
if ($tiktokUrl !== '') {
    $links[] = [
        'label'     => 'TikTok',
        'href'      => $tiktokUrl,
        'gradient'  => 'bg-gradient-to-r from-slate-900 via-slate-800 to-fuchsia-800',
        'icon'      => 'music-2',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$facebookUrl = trim((string) Setting::get('facebook_url', ''));
if ($facebookUrl !== '') {
    $links[] = [
        'label'     => 'Facebook',
        'href'      => $facebookUrl,
        'gradient'  => 'bg-gradient-to-r from-blue-600 to-blue-900',
        'icon'      => 'facebook',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$instagramUrl = trim((string) Setting::get('instagram_url', ''));
if ($instagramUrl !== '') {
    $links[] = [
        'label'     => 'Instagram',
        'href'      => $instagramUrl,
        'gradient'  => 'bg-[linear-gradient(135deg,#833ab4_0%,#fd1d1d_50%,#fcb045_100%)]',
        'icon'      => 'instagram',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$youtubeUrl = trim((string) Setting::get('youtube_url', ''));
if ($youtubeUrl !== '') {
    $links[] = [
        'label'     => 'YouTube',
        'href'      => $youtubeUrl,
        'gradient'  => 'bg-gradient-to-r from-red-600 to-red-900',
        'icon'      => 'youtube',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}

$xhsUrl = trim((string) Setting::get('xiaohongshu_url', ''));
if ($xhsUrl !== '') {
    $links[] = [
        'label'     => 'RED / Xiaohongshu',
        'href'      => $xhsUrl,
        'gradient'  => 'bg-gradient-to-r from-rose-600 to-red-800',
        'icon'      => 'bookmark-heart',
        'right'     => 'external-link',
        'target'    => '_blank',
        'rel'       => 'noopener noreferrer',
    ];
}
?>
<section class="min-h-[65vh] bg-gradient-to-b from-white via-orange-50/40 to-accent-50/50 pb-28 md:pb-16">
  <div class="max-w-md mx-auto px-4 pt-8 pb-4 flex flex-col items-center text-center">
    <img src="<?= e(asset('site-logo.png')) ?>" alt="<?= e($siteName) ?>" width="112" height="112"
      class="w-28 h-28 rounded-full object-cover shadow-lg ring-4 ring-white/90 mb-4" loading="lazy" decoding="async">

    <h1 class="text-2xl sm:text-[1.65rem] font-extrabold text-forest-900 tracking-tight"><?= e($siteName) ?></h1>

    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white text-xs font-bold uppercase tracking-wide px-3.5 py-1.5 shadow-md">
      <i data-lucide="messages-square" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
      ติดต่อ
    </span>

    <p class="mt-4 text-sm sm:text-base text-slate-600 leading-relaxed max-w-sm"><?= e($intro) ?></p>

    <?php if ($address !== ''): ?>
    <p class="mt-5 flex items-start justify-center gap-2 text-left text-sm text-slate-700 max-w-sm mx-auto">
      <i data-lucide="map-pin" class="w-4 h-4 text-red-600 shrink-0 mt-0.5" aria-hidden="true"></i>
      <span><?= nl2br(e($address)) ?></span>
    </p>
    <?php endif; ?>

    <?php if ($hours !== ''): ?>
    <p class="mt-2 flex items-center justify-center gap-2 text-sm text-slate-700">
      <i data-lucide="clock" class="w-4 h-4 text-amber-500 shrink-0" aria-hidden="true"></i>
      <span><?= e($hours) ?></span>
    </p>
    <?php endif; ?>

    <div class="w-full mt-8 space-y-3">
      <?php foreach ($links as $link): ?>
      <a href="<?= e($link['href']) ?>"
        <?= $link['target'] !== '_self' ? ' target="' . e($link['target']) . '"' : '' ?>
        <?= $link['rel'] !== '' ? ' rel="' . e($link['rel']) . '"' : '' ?>
        class="<?= e($link['gradient']) ?> flex items-center gap-4 w-full rounded-2xl px-5 py-4 text-white font-bold shadow-lg shadow-slate-900/15 hover:shadow-xl hover:shadow-slate-900/20 hover:scale-[1.02] active:scale-[0.99] transition duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-accent-400/60">

        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
          <?php if (in_array($link['icon'], ['facebook', 'instagram', 'youtube'], true)): ?>
            <?php \App\Core\View::partial('partials/brand-icon', ['name' => $link['icon'], 'class' => 'w-6 h-6 text-white']); ?>
          <?php else: ?>
            <i data-lucide="<?= e($link['icon']) ?>" class="w-6 h-6 text-white" aria-hidden="true"></i>
          <?php endif; ?>
        </span>

        <span class="flex-1 text-center text-[15px] sm:text-base drop-shadow-sm"><?= e($link['label']) ?></span>

        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
          <i data-lucide="<?= e($link['right']) ?>" class="w-5 h-5 text-white/95" aria-hidden="true"></i>
        </span>
      </a>
      <?php endforeach; ?>

      <?php if ($links === []): ?>
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 px-4 py-8 text-slate-600 text-sm leading-relaxed">
        ยังไม่ได้ตั้งค่าช่องทางติดต่อเพิ่มเติมในระบบ คุณสามารถแก้ค่าได้ที่แผงผู้ดูแลระบบ (การตั้งค่าเว็บไซต์)
        หรือกลับไปที่หน้าแรกเพื่อค้นหาที่พัก
        <a href="<?= e(url('/')) ?>" class="mt-4 inline-flex font-semibold text-accent-700 hover:text-accent-900 underline underline-offset-2">ไปหน้าแรก</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
