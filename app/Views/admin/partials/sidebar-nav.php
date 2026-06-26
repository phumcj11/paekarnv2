<?php
use App\Support\AdminNav;

/** @var string $cur */
/** @var int $sidebarProviderPending */
/** @var int $sidebarProductPending */

$initialGroup = AdminNav::groupForPath($cur) ?? '';

if (!function_exists('ad_link_class')) {
    function ad_link_class(string $path, string $href, bool $compact = false): string
    {
        $base = $compact ? 'ad-sidebar-link ad-sidebar-link--compact' : 'ad-sidebar-link';
        if (AdminNav::isActive($path, $href)) {
            return $base . ' ad-sidebar-link--active';
        }

        return $base;
    }
}
?>
<nav class="flex-1 overflow-y-auto p-3 space-y-1 text-sm"
     x-data="{ openGroup: <?= json_encode($initialGroup, JSON_UNESCAPED_UNICODE) ?> }">

  <div class="ad-sidebar-section">เมนูหลัก</div>
  <?php foreach (AdminNav::pinned() as $item): ?>
    <a href="<?= url($item['href']) ?>" class="<?= ad_link_class($cur, $item['href']) ?>">
      <i data-lucide="<?= e($item['icon']) ?>" class="w-4 h-4 shrink-0"></i>
      <span class="flex-1"><?= e($item['label']) ?></span>
    </a>
  <?php endforeach; ?>

  <div class="ad-sidebar-section mt-3">เมนูเพิ่มเติม</div>
  <?php foreach (AdminNav::groups() as $group):
    $groupActive = AdminNav::groupHasActive($cur, $group);
    $groupId = $group['id'];
  ?>
    <div class="ad-sidebar-group">
      <button type="button"
              class="ad-sidebar-group-toggle w-full<?= $groupActive ? ' ad-sidebar-group-toggle--active' : '' ?>"
              @click="openGroup = openGroup === '<?= e($groupId) ?>' ? '' : '<?= e($groupId) ?>'"
              :aria-expanded="openGroup === '<?= e($groupId) ?>'">
        <span class="flex-1 text-left"><?= e($group['label']) ?></span>
        <i data-lucide="chevron-down"
           class="w-4 h-4 shrink-0 transition-transform duration-200"
           :class="openGroup === '<?= e($groupId) ?>' ? 'rotate-180' : ''"></i>
      </button>
      <div class="ad-sidebar-group-items"
           x-show="openGroup === '<?= e($groupId) ?>'"
           x-cloak>
        <?php foreach ($group['items'] as $item):
          $badge = AdminNav::badgeCount(
              (string) ($item['badge'] ?? ''),
              $sidebarProviderPending,
              $sidebarProductPending
          );
        ?>
          <a href="<?= url($item['href']) ?>" class="<?= ad_link_class($cur, $item['href'], true) ?>">
            <i data-lucide="<?= e($item['icon']) ?>" class="w-3.5 h-3.5 shrink-0 opacity-80"></i>
            <span class="flex-1"><?= e($item['label']) ?></span>
            <?php if ($badge > 0):
              $badgeClass = ($item['badge'] ?? '') === 'activity_products'
                  ? 'ad-sidebar-badge ad-sidebar-badge--sky'
                  : 'ad-sidebar-badge ad-sidebar-badge--amber';
            ?>
              <span class="<?= $badgeClass ?>"><?= (int) $badge ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</nav>
