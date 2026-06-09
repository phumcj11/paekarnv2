<?php
/**
 * Reusable payment method picker + slip uploader.
 *
 * Required parent Alpine scope:
 *  - method (string)                : 'promptpay' | 'bank_transfer' | 'credit_card'
 *  - paymentAmount (number, optional): used for PromptPay QR. If absent, parent must provide via $amountVar
 *
 * @var array<string,string> $bank      bank_name, bank_account, bank_holder, promptpay
 * @var string $amountVar               Alpine expression that evaluates to the current amount (e.g. "total", "sale*qty")
 * @var bool   $showGatewaySlot         show disabled "Credit Card — เร็วๆ นี้"
 */
$bank = $bank ?? [
    'bank_name'    => \App\Models\Setting::get('bank_name', ''),
    'bank_account' => \App\Models\Setting::get('bank_account', ''),
    'bank_holder'  => \App\Models\Setting::get('bank_holder', ''),
    'promptpay'    => \App\Models\Setting::get('promptpay_id', ''),
];
$amountVar = $amountVar ?? 'total';
$showGatewaySlot = $showGatewaySlot ?? true;
$gatewayEnabled = (string) \App\Models\Setting::get('payment_gateway_enabled', '0') === '1';
$qrEndpoint = url('/api-promptpay-qr');
?>
<div class="bg-white border border-slate-200 rounded-2xl p-5"
     x-data="{
        paymentAmount: 0,
        qrSrc: '',
        qrLoading: false,
        qrError: '',
        copied: '',
        slipName: '',
        slipPreview: '',
        slipSize: '',
        copy(text, key){
          if (!text) return;
          navigator.clipboard.writeText(text).then(()=>{
            this.copied = key;
            setTimeout(()=>{ this.copied = ''; }, 1500);
          }).catch(()=>{ this.copied = ''; });
        },
        previewSlip(ev){
          const f = ev.target.files && ev.target.files[0];
          if (!f) { this.slipName=''; this.slipPreview=''; this.slipSize=''; return; }
          this.slipName = f.name;
          this.slipSize = (f.size/1024).toFixed(0) + ' KB';
          if (f.type.startsWith('image/')) {
            const r = new FileReader();
            r.onload = e => this.slipPreview = e.target.result;
            r.readAsDataURL(f);
          } else {
            this.slipPreview = '';
          }
        },
        refreshQr(){
          if (this.method !== 'promptpay') return;
          this.qrLoading = true; this.qrError = '';
          const url = '<?= e($qrEndpoint) ?>?amount=' + encodeURIComponent(this.paymentAmount || 0);
          fetch(url).then(r=>r.json()).then(d=>{
            this.qrLoading = false;
            if (d.ok) { this.qrSrc = d.image; }
            else      { this.qrError = d.msg || 'ไม่สามารถสร้าง QR ได้'; }
          }).catch(()=>{ this.qrLoading=false; this.qrError='เกิดข้อผิดพลาด'; });
        }
     }"
     x-init="
        paymentAmount = (<?= $amountVar ?>) || 0;
        $watch('method', ()=> refreshQr());
        refreshQr();
        $watch(() => (<?= $amountVar ?>), v => { paymentAmount = v||0; refreshQr(); });
     ">
  <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="credit-card" class="w-5 h-5 text-primary-600"></i> วิธีการชำระเงิน</h2>

  <!-- Method picker -->
  <div class="grid grid-cols-1 sm:grid-cols-<?= $showGatewaySlot ? '3' : '2' ?> gap-2 mt-3">
    <label class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl cursor-pointer transition"
           :class="method==='promptpay'?'border-accent-500 bg-accent-50 shadow-sm':'border-slate-200 hover:border-slate-300'">
      <input type="radio" name="payment_method" value="promptpay" x-model="method" class="hidden">
      <i data-lucide="qr-code" class="w-5 h-5 text-accent-600"></i>
      <div class="flex-1">
        <div class="font-semibold text-sm">PromptPay</div>
        <div class="text-xs text-slate-500">สแกน QR จ่ายเร็ว</div>
      </div>
    </label>
    <label class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl cursor-pointer transition"
           :class="method==='bank_transfer'?'border-accent-500 bg-accent-50 shadow-sm':'border-slate-200 hover:border-slate-300'">
      <input type="radio" name="payment_method" value="bank_transfer" x-model="method" class="hidden">
      <i data-lucide="landmark" class="w-5 h-5 text-accent-600"></i>
      <div class="flex-1">
        <div class="font-semibold text-sm">โอนผ่านธนาคาร</div>
        <div class="text-xs text-slate-500"><?= e($bank['bank_name'] ?: 'โอนเข้าบัญชี') ?></div>
      </div>
    </label>
    <?php if ($showGatewaySlot): ?>
    <label class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl transition relative
                  <?= $gatewayEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' ?>"
           :class="method==='credit_card'?'border-accent-500 bg-accent-50 shadow-sm':'border-slate-200'">
      <input type="radio" name="payment_method" value="credit_card" x-model="method"
             <?= $gatewayEnabled ? '' : 'disabled' ?> class="hidden">
      <i data-lucide="credit-card" class="w-5 h-5 text-accent-600"></i>
      <div class="flex-1">
        <div class="font-semibold text-sm">บัตรเครดิต</div>
        <div class="text-xs text-slate-500"><?= $gatewayEnabled ? 'Visa / Master / JCB' : 'เร็วๆ นี้' ?></div>
      </div>
      <?php if (!$gatewayEnabled): ?>
        <span class="absolute top-1.5 right-2 text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">Soon</span>
      <?php endif; ?>
    </label>
    <?php endif; ?>
  </div>

  <!-- PromptPay panel -->
  <div x-show="method==='promptpay'" x-transition class="mt-4 p-4 rounded-xl bg-gradient-to-br from-accent-50 to-white border border-accent-200">
    <div class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-4 items-start">
      <div class="bg-white rounded-xl p-2 border border-slate-200 grid place-items-center w-40 h-40 mx-auto">
        <template x-if="qrLoading"><i data-lucide="loader-2" class="w-8 h-8 text-slate-400 animate-spin"></i></template>
        <template x-if="!qrLoading && qrSrc">
          <img :src="qrSrc" alt="PromptPay QR" class="w-full h-full object-contain">
        </template>
        <template x-if="!qrLoading && !qrSrc">
          <div class="text-xs text-slate-500 text-center px-2" x-text="qrError || 'กำลังโหลด QR...'"></div>
        </template>
      </div>
      <div class="text-sm">
        <div class="font-semibold text-slate-800 mb-1 flex items-center gap-1.5">
          <i data-lucide="smartphone" class="w-4 h-4 text-accent-600"></i> PromptPay
        </div>
        <div class="text-xs text-slate-500 mb-1">ID / เบอร์</div>
        <div class="flex items-center gap-2 mb-2">
          <span class="font-mono font-semibold text-base"><?= e($bank['promptpay']) ?></span>
          <button type="button" @click="copy('<?= e($bank['promptpay']) ?>', 'pp')" class="text-xs px-2 py-1 rounded border border-slate-300 hover:bg-slate-100">
            <span x-show="copied!=='pp'" class="inline-flex items-center gap-1"><i data-lucide="copy" class="w-3 h-3"></i> คัดลอก</span>
            <span x-show="copied==='pp'" class="inline-flex items-center gap-1 text-emerald-600"><i data-lucide="check" class="w-3 h-3"></i> คัดลอกแล้ว</span>
          </button>
        </div>
        <div class="text-xs text-slate-500 mb-1">ชื่อบัญชี</div>
        <div class="font-semibold mb-2"><?= e($bank['bank_holder']) ?></div>
        <div class="text-xs text-slate-500 mb-1">จำนวนเงิน</div>
        <div class="font-bold text-lg text-primary-700">฿<span x-text="(paymentAmount||0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
        <div class="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-2 py-1.5 inline-flex items-center gap-1">
          <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> ระบุยอดอัตโนมัติ ไม่ต้องพิมพ์เอง
        </div>
      </div>
    </div>
  </div>

  <!-- Bank transfer panel -->
  <div x-show="method==='bank_transfer'" x-transition class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm">
    <div class="font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
      <i data-lucide="landmark" class="w-4 h-4 text-primary-600"></i> <?= e($bank['bank_name']) ?>
    </div>
    <div class="grid grid-cols-1 gap-2">
      <div>
        <div class="text-xs text-slate-500">เลขที่บัญชี</div>
        <div class="flex items-center gap-2">
          <span class="font-mono font-semibold text-base"><?= e($bank['bank_account']) ?></span>
          <button type="button" @click="copy('<?= e(preg_replace('/\D/', '', $bank['bank_account'])) ?>', 'ba')" class="text-xs px-2 py-1 rounded border border-slate-300 hover:bg-white">
            <span x-show="copied!=='ba'" class="inline-flex items-center gap-1"><i data-lucide="copy" class="w-3 h-3"></i> คัดลอก</span>
            <span x-show="copied==='ba'" class="inline-flex items-center gap-1 text-emerald-600"><i data-lucide="check" class="w-3 h-3"></i> คัดลอกแล้ว</span>
          </button>
        </div>
      </div>
      <div>
        <div class="text-xs text-slate-500">ชื่อบัญชี</div>
        <div class="font-semibold"><?= e($bank['bank_holder']) ?></div>
      </div>
      <div>
        <div class="text-xs text-slate-500">จำนวนเงิน</div>
        <div class="font-bold text-lg text-primary-700">฿<span x-text="(paymentAmount||0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
      </div>
    </div>
  </div>

  <!-- Slip uploader -->
  <div x-show="method==='promptpay' || method==='bank_transfer'" x-transition class="mt-4">
    <label class="text-sm font-medium text-slate-700 mb-1 block">อัปโหลดสลิปการโอน</label>
    <label class="relative flex flex-col items-center justify-center w-full p-4 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-accent-400 hover:bg-accent-50/30 transition"
           :class="slipPreview ? 'border-emerald-400 bg-emerald-50/40' : ''">
      <input type="file" name="slip" accept="image/*" @change="previewSlip($event)" class="absolute inset-0 opacity-0 cursor-pointer">
      <template x-if="!slipPreview">
        <div class="text-center pointer-events-none">
          <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 mx-auto mb-1"></i>
          <div class="text-sm font-semibold text-slate-700">คลิกเพื่อเลือกรูปสลิป</div>
          <div class="text-xs text-slate-500 mt-0.5">รองรับ JPG, PNG ขนาดไม่เกิน 5 MB</div>
        </div>
      </template>
      <template x-if="slipPreview">
        <div class="flex items-center gap-3 w-full pointer-events-none">
          <img :src="slipPreview" class="w-20 h-20 rounded-lg object-cover border border-slate-200">
          <div class="flex-1 text-left">
            <div class="text-sm font-semibold text-slate-800 truncate" x-text="slipName"></div>
            <div class="text-xs text-slate-500" x-text="slipSize"></div>
            <div class="text-xs text-emerald-700 mt-1 inline-flex items-center gap-1">
              <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> พร้อมส่ง — กดปุ่มยืนยันด้านล่าง
            </div>
          </div>
        </div>
      </template>
    </label>
    <div class="mt-2 text-xs text-slate-500 flex items-start gap-1.5">
      <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
      <span>หากยังไม่อัปสลิปตอนนี้ก็ส่งฟอร์มได้ คำสั่งซื้อจะอยู่ในสถานะ <b>รอชำระ</b> จนกว่าจะส่งสลิป</span>
    </div>
  </div>

  <!-- Credit card disabled panel -->
  <?php if ($showGatewaySlot && !$gatewayEnabled): ?>
  <div x-show="method==='credit_card'" x-transition class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
    <i data-lucide="info" class="w-4 h-4 inline"></i> ระบบบัตรเครดิตอยู่ระหว่างเตรียมเปิดให้บริการ กรุณาเลือก PromptPay หรือโอนผ่านธนาคารแทน
  </div>
  <?php endif; ?>
</div>
