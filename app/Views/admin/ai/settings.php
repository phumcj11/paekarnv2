<?php /** @var string $enabled @var string $provider @var string $apiUrl @var string $apiKey @var string $model
 *  @var string $chatEnabled @var string $greeting @var string $persona @var int $chats7d @var int $kbCount */ ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <form method="post" action="<?= url('/admin/ai') ?>" class="lg:col-span-2 space-y-4">
    <?= csrf() ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="bot" class="w-6 h-6 text-purple-600"></i> AI Provider</h3>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="ai_enabled" value="1" <?= $enabled?'checked':'' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-300 peer-checked:bg-accent-500 rounded-full relative transition">
            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full peer-checked:translate-x-5 transition"></div>
          </div>
          <span class="text-sm font-medium">เปิดใช้งาน AI</span>
        </label>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">Provider</label>
          <select name="ai_provider" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <?php foreach (['openai'=>'OpenAI','openrouter'=>'OpenRouter','together'=>'Together AI','custom'=>'Custom (Self-hosted)'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $provider===$k?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Model</label>
          <input name="ai_model" value="<?= e($model) ?>" placeholder="gpt-4o-mini" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium mb-1 block">API URL</label>
          <input name="ai_api_url" value="<?= e($apiUrl) ?>" placeholder="https://api.openai.com/v1" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium mb-1 block">API Key</label>
          <input name="ai_api_key" type="password" value="<?= e($apiKey) ?>" placeholder="sk-..." class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm">
          <div class="text-xs text-slate-500 mt-1">เก็บอย่างปลอดภัย ไม่แสดงต่อผู้ใช้</div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="message-circle" class="w-6 h-6 text-accent-600"></i> AI Chatbot Widget</h3>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="ai_chatbot_enabled" value="1" <?= $chatEnabled?'checked':'' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-300 peer-checked:bg-accent-500 rounded-full relative transition">
            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full peer-checked:translate-x-5 transition"></div>
          </div>
          <span class="text-sm font-medium">แสดงน้องแพ</span>
        </label>
      </div>
      <div class="space-y-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ข้อความทักทาย</label>
          <input name="ai_chatbot_greeting" value="<?= e($greeting) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Persona / System Prompt</label>
          <textarea name="ai_chatbot_persona" rows="5" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm"><?= e($persona) ?></textarea>
          <div class="text-xs text-slate-500 mt-1">บอกบุคลิก น้ำเสียง และขอบเขตที่ AI ต้องตอบ</div>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl">บันทึก</button>
    </div>
  </form>

  <aside class="space-y-4">
    <div class="bg-gradient-to-br from-purple-500 to-pink-500 text-white rounded-2xl shadow-soft p-5">
      <div class="text-xs uppercase opacity-80">บทสนทนา 7 วันที่ผ่านมา</div>
      <div class="text-3xl font-bold mt-1"><?= number_format($chats7d) ?></div>
      <a href="<?= url('/admin/ai/chats') ?>" class="text-xs underline opacity-80">ดูรายละเอียด</a>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 text-sm">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs text-slate-500">Knowledge Base</div>
          <div class="text-2xl font-bold"><?= number_format($kbCount) ?></div>
        </div>
        <a href="<?= url('/admin/ai/kb') ?>" class="px-3 py-1.5 bg-accent-500 text-white rounded-lg text-xs">จัดการ</a>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <h4 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="flask-conical" class="w-5 h-5 text-amber-500"></i> ทดสอบ</h4>
      <form method="post" action="<?= url('/admin/ai/test') ?>" class="space-y-2">
        <?= csrf() ?>
        <input name="message" placeholder="สวัสดี ทดสอบ" value="คูปองคืออะไร?" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <button class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold">ทดสอบ AI</button>
      </form>
    </div>
  </aside>
</div>
