<?php
/**
 * แพกาญ.com - Application Config
 */
return [
    'name'        => 'แพกาญ.com',
    'tagline'     => 'รวมที่พักครบทุกประเภทในกาญจนบุรี — แพ รีสอร์ท โรงแรม โฮมสเตย์',
    'env'         => 'production',          // local | production
    'debug'       => false,
    'timezone'    => 'Asia/Bangkok',

    /**
     * โหมดปิดปรับปรุง — true แล้วทุกคำขอผ่านแอปได้ 503 (ยกเว้นผู้ที่มี bypass_secret ใน query ?maint_bypass=...)
     * หลังตรวจสอบตรงแล้วจะเก็บใน session เพื่อไม่ต้องส่งคีย์ซ้ำ
     */
    'maintenance' => [
        'enabled'        => false,
        'message'        => 'เรากำลังปรับปรุงระบบชั่วคราว กรุณาลองใหม่ภายหลัง',
        'retry_after'    => 3600,
        'bypass_secret'  => '',
    ],
    'locale'      => 'th',             // default
    'fallback_locale' => 'th',
    'available_locales' => ['th', 'en'],

    // ค่า default ของระบบคูปอง (override ได้จาก settings table)
    'coupon' => [
        'face_value'      => 500,
        'sale_price'      => 250,
        'validity_days'   => 90,
    ],

    // อัปโหลดไฟล์
    'upload' => [
        'max_size'   => 5 * 1024 * 1024,                  // 5 MB
        'allow_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'allow_ext'  => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],

    // จำนวนรายการต่อหน้า (pagination)
    'paginate' => [
        'properties' => 12,
        'reviews'    => 6,
        'blog'       => 9,
        'admin'      => 20,
    ],

    /** จำกัดการล็อกอินผิด (หลังบ้าน + เจ้าของแพ + ลูกค้า) */
    'login_throttle' => [
        'max_attempts'    => 5,
        'decay_minutes'   => 15,
        'lockout_minutes' => 15,
    ],

    /** จำนวนแถวสูงสุดต่อครั้งเมื่อส่งออก CSV จากแอดมิน */
    'admin_export_max_rows' => 5000,

    /** Dashboard: แพมีปัญหา v0 */
    'dashboard' => [
        'prop_pending_warn_days'      => 7,
        'booking_pending_warn_hours'  => 48,
        'published_missing_phone'     => true,
    ],
];
