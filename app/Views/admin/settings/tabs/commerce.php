<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<?php
$couponFields = [
    'coupon_face_value' => [
        'มูลค่าใช้จริง (บาท)',
        'มูลค่าที่ลูกค้าได้รับเมื่อใช้คูปองกับที่พัก — แสดงบนหน้าซื้อและ QR',
        'เช่น 500',
    ],
    'coupon_sale_price' => [
        'ราคาขาย (บาท)',
        'ราคาที่ลูกค้าจ่ายเพื่อซื้อคูปอง — ควรต่ำกว่ามูลค่าใช้จริงเพื่อให้รู้สึกคุ้ม',
        'เช่น 250',
    ],
    'coupon_validity_days' => [
        'อายุคูปอง (วัน)',
        'นับจากวันที่ซื้อสำเร็จ — หมดอายุแล้วใช้ไม่ได้',
        'เช่น 90',
    ],
];
foreach ($couponFields as $k => [$label, $hint, $example]):
    ob_start();
    ?>
    <input type="number" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" min="0" class="<?= $ic ?>">
    <?php
    settings_field($label, ob_get_clean(), $hint, $example);
endforeach;
?>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
<?php
$ctaLabelFields = [
    'coupon_cta_button_label' => [
        'ข้อความปุ่มซื้อคูปอง',
        'แสดงบนหน้ารายละเอียดที่พัก การ์ดยูนิต Sidebar และปุ่ม CTA อื่นๆ — ว่าง = ใช้ค่าเริ่มต้น «ซื้อคูปอง»',
        'เช่น ซื้อคูปอง / รับส่วนลด',
    ],
    'coupon_cta_button_label_short' => [
        'ข้อความปุ่ม (สั้น — มือถือ)',
        'ใช้ในแถบ CTA ลอยด้านล่างบนมือถือเมื่อพื้นที่จำกัด — ว่าง = ใช้ข้อความปุ่มด้านบน',
        'เช่น คูปอง',
    ],
];
foreach ($ctaLabelFields as $k => [$label, $hint, $example]):
    ob_start();
    ?>
    <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" maxlength="40" class="<?= $ic ?>" placeholder="<?= e($example) ?>">
    <?php
    settings_field($label, ob_get_clean(), $hint, $example);
endforeach;
?>
</div>
<?php
$couponContent = ob_get_clean();
settings_section(
    settings_t('commerce.coupon_section_title'),
    'ticket',
    $couponContent,
    settings_t('commerce.coupon_section_intro', ''),
    'text-rose-600'
);

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
$bankFields = [
    'bank_name' => ['ธนาคาร', 'ชื่อธนาคารที่รับโอน'],
    'bank_account' => ['เลขที่บัญชี', 'ตัวเลขเท่านั้น ไม่มีขีด'],
    'bank_holder' => ['ชื่อบัญชี', 'ต้องตรงกับสมุดบัญชี'],
    'promptpay_id' => ['PromptPay ID', 'เบอร์โทรหรือเลขบัตรประชาชน 13 หลัก'],
];
foreach ($bankFields as $k => [$label, $hint]):
    ob_start();
    ?>
    <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" class="<?= $ic ?>">
    <?php
    settings_field($label, ob_get_clean(), $hint);
endforeach;
?>
</div>
<?php
$bankContent = ob_get_clean();
settings_section(
    settings_t('commerce.bank_section_title'),
    'landmark',
    $bankContent,
    settings_t('commerce.bank_section_intro', ''),
    'text-emerald-600'
);

ob_start();
$gatewayEnabled = (string)($values['payment_gateway_enabled'] ?? '') === '1';
?>
<div class="space-y-4">
  <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50">
    <input type="hidden" name="payment_gateway_enabled" value="0">
    <input type="checkbox" name="payment_gateway_enabled" value="1" <?= $gatewayEnabled ? 'checked' : '' ?>
           class="w-5 h-5 text-primary-600 rounded">
    <div>
      <div class="font-semibold text-slate-800">เปิดใช้งานชำระผ่านบัตรเครดิต / Gateway</div>
      <div class="text-xs text-slate-500">เมื่อปิด ลูกค้าจะเห็นปุ่ม "บัตรเครดิต" แต่กดไม่ได้ (Soon)</div>
    </div>
  </label>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php
    $gatewayFields = [
        'payment_gateway_provider'  => ['ผู้ให้บริการ Gateway', 'ใส่ stripe สำหรับซื้อคูปองด้วยบัตรเครดิต'],
        'payment_gateway_public_key'  => ['Publishable Key', 'pk_test_... หรือ pk_live_... จาก Stripe Dashboard'],
        'payment_gateway_secret_key'  => ['Secret Key', 'sk_test_... หรือ sk_live_... — เก็บเป็นความลับ'],
        'payment_gateway_webhook_secret'  => ['Webhook Signing Secret', 'whsec_... จาก Stripe → Webhooks → endpoint'],
    ];
    foreach ($gatewayFields as $k => [$label, $hint]):
        $isSecret = in_array($k, ['payment_gateway_secret_key', 'payment_gateway_webhook_secret'], true);
        ob_start();
        ?>
        <input type="<?= $isSecret ? 'password' : 'text' ?>"
               name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>"
               autocomplete="off" class="<?= $ic ?>">
        <?php
        settings_field($label, ob_get_clean(), $hint);
    endforeach;
    ?>
  </div>

  <div class="text-xs text-slate-500 flex items-start gap-1.5 px-1">
    <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
    <span>Stripe Checkout ใช้กับหน้าซื้อคูปอง — ตั้ง provider = <b>stripe</b> แล้วเพิ่ม Webhook endpoint <b><?= e(url('/webhooks/stripe')) ?></b> (event: checkout.session.completed)</span>
  </div>

  <?php $activityGateway = (string)($values['activity_checkout_gateway_enabled'] ?? '') === '1'; ?>
  <label class="flex items-center gap-3 p-3 rounded-lg border border-violet-200 bg-violet-50 mt-4">
    <input type="hidden" name="activity_checkout_gateway_enabled" value="0">
    <input type="checkbox" name="activity_checkout_gateway_enabled" value="1" <?= $activityGateway ? 'checked' : '' ?>
           class="w-5 h-5 text-primary-600 rounded">
    <div>
      <div class="font-semibold text-slate-800">ใช้ Gateway กับ checkout กิจกรรม (voucher)</div>
      <div class="text-xs text-slate-500">ต้องเปิด Gateway ด้านบนด้วย · ตอนนี้ยัง fallback เป็นโอน/สลิป manual</div>
    </div>
  </label>
</div>
<?php
$gatewayContent = ob_get_clean();
settings_section(
    'ชำระผ่านบัตรเครดิต / Gateway',
    'credit-card',
    $gatewayContent,
    'Stripe Checkout สำหรับหน้าซื้อคูปอง — ต้องตั้ง Webhook ใน Stripe Dashboard',
    'text-violet-600'
);
