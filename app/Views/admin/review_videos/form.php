<?php
/** @var ?array<string,mixed> $row */
/** @var array<string,string> $categories */
/** @var list<array{id:int,name:string}> $properties */
use App\Core\Session;
use App\Models\ReviewVideo;

$isEdit = !empty($row);
$action = $isEdit ? url('/admin/review-videos/' . $row['id']) : url('/admin/review-videos');
$oldInput = Session::get('_old', []);
$selCat = (string) ($oldInput['category'] ?? ($isEdit ? ($row['category'] ?? 'general') : 'general'));
$selP = isset($oldInput['related_property_id'])
    ? (string) $oldInput['related_property_id']
    : ($isEdit ? (string) ($row['related_property_id'] ?? '') : '');
if (array_key_exists('is_active', $oldInput)) {
    $chkActive = !empty($oldInput['is_active']);
} else {
    $chkActive = $row === null ? true : !empty($row['is_active']);
}
$urlDefault = '';
if ($isEdit) {
    $urlDefault = ReviewVideo::sourceUrlOf($row);
    if ($urlDefault === '' && !empty($row['youtube_id'])) {
        $urlDefault = 'https://www.youtube.com/watch?v=' . $row['youtube_id'];
    }
}
$orientPortrait = array_key_exists('orientation_portrait', $oldInput)
    ? !empty($oldInput['orientation_portrait'])
    : ($isEdit ? (($row['orientation'] ?? 'portrait') === 'portrait') : true);
$orientOverride = !empty($oldInput['orientation_override']);
?>
<a href="<?= url('/admin/review-videos') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div>
      <label class="text-sm font-medium mb-1 block">ลิงก์วิดีโอ</label>
      <input type="url" name="video_url" required class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm"
             placeholder="YouTube / Shorts / TikTok / Instagram Reels"
             value="<?= old('video_url', old('youtube_input', $urlDefault)) ?>">
      <p class="text-xs text-slate-500 mt-1">ระบบตรวจ platform อัตโนมัติ · TikTok แนะนำลิงก์เต็ม tiktok.com/@.../video/...</p>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">หัวข้อ</label>
      <input type="text" name="title" required maxlength="200" class="w-full px-3 py-2 rounded-lg border border-slate-300"
             value="<?= old('title', $isEdit ? (string)($row['title'] ?? '') : '') ?>">
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">คำอธิบาย (SEO)</label>
      <textarea name="description" rows="4" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('description', $isEdit ? (string)($row['description'] ?? '') : '') ?></textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">หมวด</label>
        <select name="category" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php foreach ($categories as $k => $lab): ?>
            <option value="<?= e($k) ?>" <?= $selCat === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ลำดับแสดง (เลขน้อยขึ้นก่อน)</label>
        <input type="number" name="sort_order" min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300"
               value="<?= old('sort_order', $isEdit ? (string)(int)($row['sort_order'] ?? 0) : '0') ?>">
      </div>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ที่พักที่เกี่ยวข้อง (ถ้ามี)</label>
      <select name="related_property_id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        <option value="">— ไม่ผูกที่พัก —</option>
        <?php foreach ($properties as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (string)$p['id'] === $selP ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
      <label class="flex items-center gap-2 cursor-pointer text-sm">
        <input type="checkbox" name="orientation_override" value="1" class="rounded border-slate-300" <?= $orientOverride ? 'checked' : '' ?>>
        <span>กำหนดแนววิดีโอเอง (ไม่ใช้ auto-detect)</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer text-sm ml-6">
        <input type="checkbox" name="orientation_portrait" value="1" class="rounded border-slate-300" <?= $orientPortrait ? 'checked' : '' ?>>
        <span>แนวตั้ง (9:16) — เหมาะ Shorts / TikTok / Reels</span>
      </label>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
             <?= $chkActive ? 'checked' : '' ?>>
      <span class="text-sm">แสดงบนเว็บ</span>
    </label>
  </div>
  <button type="submit" class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">บันทึก</button>
</form>
