<?php
/** @var string $slot @var array<string,mixed>|null $banner @var bool $eagerLoad */
if (empty($banner) || !is_array($banner)) {
    return;
}

if (\App\Models\Banner::isNativeSlot($slot)) {
    \App\Core\View::partial('partials/banners/banner-native-card', ['banner' => $banner]);
    return;
}

\App\Core\View::partial('partials/banners/banner-wide', [
    'banner' => $banner,
    'slot' => $slot,
    'eagerLoad' => !empty($eagerLoad),
]);
