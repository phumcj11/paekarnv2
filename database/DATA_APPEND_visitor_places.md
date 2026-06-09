# เพิ่มที่เที่ยวกาญจนบุรี (ชั่วคราวสำหรับคัดลอก)

โหมด Plan ใน Cursor บล็อกการแก้ `.php` โดยตรง — คัดลอกบล็อก PHP ด้านล่างไปแทรกในไฟล์ `database/data/kanchanaburi_visitor_places.php` **แทนที่** `];` สุดท้ายของ `return [` โดย:
1. ลบ `];` ปิดท้ายไฟล์เดิมหลัง `sai-yok-national-park`
2. วางคอมมาหลัง `],` ของแถวสุดท้ายเดิม แล้วตามด้วยบล็อกนี้
3. ปิดด้วย `];`

จากนั้นรัน: `php database/import_kanchanaburi_visitor_places.php`

```php

    // ---------- เมืองกาญจนบุรี (เพิ่มเติม) ----------
    [
        'slug' => 'thailand-burma-railway-centre', 'name' => 'พิพิธภัณฑ์ทางรถไฟไทย–พม่า (Thailand–Burma Railway Centre)', 'excerpt' => 'พิพิธภัณฑ์เรื่องทางรถไฟสายมรณะและเชลยศึก — จัดแสดงละเอียดเป็นภาษาไทยและอังกฤษ',
        'description' => null, 'category' => 'attraction', 'district' => 'เมืองกาญจนบุรี', 'zone' => 'ริมแม่น้ำแคว',
        'latitude' => 14.0493, 'longitude' => 99.5277, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0493,99.5277', 'sort_order' => 150, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'allied-cemetery-kanchan-main', 'name' => 'สุสานทหารสัมพันธมิตรดอนเมืองกาญจนบุรี', 'excerpt' => 'สุสานทหารพันธมิตรใจกลางเมือง — เหมาะไหว้และเรียนรู้ประวัติศาสตร์',
        'description' => null, 'category' => 'attraction', 'district' => 'เมืองกาญจนบุรี', 'zone' => 'ริมแม่น้ำแคว',
        'latitude' => 14.0366, 'longitude' => 99.5257, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0366,99.5257', 'sort_order' => 151, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'chongkai-allied-cemetery', 'name' => 'สุสานทหารสัมพันธมิตรช่องไก่', 'excerpt' => 'สุสานริมแม่น้ำแควใกล้ชุมชนช่องไก่ — เชิงประวัติศาสตร์ไม่ไกลจากสะพานข้ามแคว',
        'description' => null, 'category' => 'attraction', 'district' => 'เมืองกาญจนบุรี', 'zone' => 'ริมแม่น้ำแคว',
        'latitude' => 14.0328, 'longitude' => 99.5106, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0328,99.5106', 'sort_order' => 152, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'kanchanaburi-railway-station', 'name' => 'สถานีรถไฟกาญจนบุรี', 'excerpt' => 'สถานีหลักของเมือง — ต้นทางรถไฟสายน้ำตกและขบวนท่องเที่ยวสายมรณะ',
        'description' => null, 'category' => 'attraction', 'district' => 'เมืองกาญจนบุรี', 'zone' => 'ริมแม่น้ำแคว',
        'latitude' => 14.0311, 'longitude' => 99.5237, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0311,99.5237', 'sort_order' => 153, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-chai-chumphon-kanchan', 'name' => 'วัดไชยชุมพลชนะสงคราม', 'excerpt' => 'วัดเก่าใกล้แม่น้ำแคว — ผูกพันกับประวัติเมืองและแลนด์มาร์คชมสะพานในระยะเดินได้',
        'description' => null, 'category' => 'temple', 'district' => 'เมืองกาญจนบุรี', 'zone' => 'ริมแม่น้ำแคว',
        'latitude' => 14.0396, 'longitude' => 99.5039, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0396,99.5039', 'sort_order' => 154, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'city-pillar-shrine-kanchan', 'name' => 'ศาลหลักเมืองกาญจนบุรี', 'excerpt' => 'ศาลศักดิ์สิทธิ์ของเมือง — ไหว้ขอพรและชมสถาปัตยกรรมท้องถิ่น',
        'description' => null, 'category' => 'temple', 'district' => 'เมืองกาญจนบุรี', 'zone' => null,
        'latitude' => 14.0239, 'longitude' => 99.5409, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0239,99.5409', 'sort_order' => 155, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'walking-street-kanchan', 'name' => 'ถนนคนเดินปากแพรก / ตลาดโต้รุ่งกาญจนบุรี', 'excerpt' => 'ของกิน ของฝาก และบรรยากาศเดินเล่นยามค่ำในเมืองเก่า',
        'description' => null, 'category' => 'market', 'district' => 'เมืองกาญจนบุรี', 'zone' => null,
        'latitude' => 14.0228, 'longitude' => 99.5389, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0228,99.5389', 'sort_order' => 156, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'prommitr-film-studio-kanchan', 'name' => 'เมืองมายาพรหมมิตร (โลเคชันถ่ายภาพยนตร์)', 'excerpt' => 'โลเคชันถ่ายทำขนาดใหญ่ในเขต ทอ. กาญจนบุรี — เช็กการเปิดให้เข้าชมล่วงหน้า',
        'description' => null, 'category' => 'attraction', 'district' => 'เมืองกาญจนบุรี', 'zone' => null,
        'latitude' => 13.9878, 'longitude' => 99.6518, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.9878,99.6518', 'sort_order' => 157, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ท่าม่วง (เพิ่มเติม) ----------
    [
        'slug' => 'wat-ban-tham-dragon-mouth', 'name' => 'วัดถ้ำบ้านถ้ำ (ปากมังกร)', 'excerpt' => 'เข้าถ้ำทางปากมังกรจำลอง — จุดถ่ายรูปไฮไลต์ของท่าม่วง',
        'description' => null, 'category' => 'temple', 'district' => 'ท่าม่วง', 'zone' => null,
        'latitude' => 13.9698, 'longitude' => 99.5589, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.9698,99.5589', 'sort_order' => 210, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-khao-wong', 'name' => 'วัดถ้ำเขาวง', 'excerpt' => 'ถ้ำหินงอกหินย้อยใหญ่ในเขตท่าม่วง — เดินชมโถงถ้ำและแสงธรรมชาติ',
        'description' => null, 'category' => 'temple', 'district' => 'ท่าม่วง', 'zone' => null,
        'latitude' => 13.9358, 'longitude' => 99.6168, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.9358,99.6168', 'sort_order' => 211, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-khao-laem-tha-muang', 'name' => 'วัดเขาแหลม', 'excerpt' => 'วัดบนเนินเขาในละแวกแควใหญ่และเขื่อนวชิราลงกรณ์ฝั่งใต้',
        'description' => null, 'category' => 'temple', 'district' => 'ท่าม่วง', 'zone' => null,
        'latitude' => 14.0522, 'longitude' => 99.6425, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0522,99.6425', 'sort_order' => 212, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ท่ามะกา (เพิ่มเติม) ----------
    [
        'slug' => 'death-railway-tha-kilen', 'name' => 'ชุมชนปิ่นโตน / แนวทางรถไฟถากะลิ่น', 'excerpt' => 'บรรยากาศชุมชนริมทางรถไฟในละแวกท่ามะกา — เช็กขบวนและความปลอดภัยล่วงหน้า',
        'description' => null, 'category' => 'attraction', 'district' => 'ท่ามะกา', 'zone' => 'ริมแม่น้ำแควน้อย',
        'latitude' => 13.8875, 'longitude' => 99.7265, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.8875,99.7265', 'sort_order' => 250, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-khao-tham-tha-maka', 'name' => 'วัดเขาถ้ำ', 'excerpt' => 'วัดบนเนินเขาในอำเภอท่ามะกา — ชมวิวธรรมชาติและสักการะ',
        'description' => null, 'category' => 'temple', 'district' => 'ท่ามะกา', 'zone' => null,
        'latitude' => 13.9412, 'longitude' => 99.6915, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.9412,99.6915', 'sort_order' => 251, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-muangphon-tha-maka', 'name' => 'วัดถ้ำเมืองโพธิ์', 'excerpt' => 'วัดและถ้ำในเขตท่ามะกา — สถาปัตยกรรมและประวัติชุมชน',
        'description' => null, 'category' => 'temple', 'district' => 'ท่ามะกา', 'zone' => null,
        'latitude' => 13.9188, 'longitude' => 99.7018, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.9188,99.7018', 'sort_order' => 252, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ทองผาภูมิ (เพิ่มเติม) ----------
    [
        'slug' => 'namtok-kroeng-kra-weng', 'name' => 'น้ำตกเกร็งกระวิ้ง', 'excerpt' => 'น้ำตกธรรมชาติในเขตทองผาภูมิ — เช็กระดับน้ำและทางเข้าก่อนเดินทาง',
        'description' => null, 'category' => 'nature', 'district' => 'ทองผาภูมิ', 'zone' => null,
        'latitude' => 14.6712, 'longitude' => 98.4318, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.6712,98.4318', 'sort_order' => 310, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'ban-kroeng-kra-weaving-village', 'name' => 'หมู่บ้านทอผ้าบ้านเกร็งกระวิ้ง', 'excerpt' => 'ชมผ้าทอมือและวิถีชาวเขาแถบชายแดนตะวันตก',
        'description' => null, 'category' => 'market', 'district' => 'ทองผาภูมิ', 'zone' => null,
        'latitude' => 14.6745, 'longitude' => 98.4289, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.6745,98.4289', 'sort_order' => 311, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'noen-maprang-viewpoint-thongphaphum', 'name' => 'จุดชมวิวเนินมะปราง', 'excerpt' => 'วิวภูเขาและป่าในละแวกทองผาภูมิ — เหมาะถ่ายหมอกและพระอาทิตย์ขึ้น',
        'description' => null, 'category' => 'viewpoint', 'district' => 'ทองผาภูมิ', 'zone' => null,
        'latitude' => 14.5825, 'longitude' => 98.5189, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.5825,98.5189', 'sort_order' => 312, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- สังขละบุรี (เพิ่มเติม) ----------
    [
        'slug' => 'songkalia-river-sangkhlaburi', 'name' => 'แม่น้ำซองเกลียร์ / ท่าเรือชุมชนสังขละบุรี', 'excerpt' => 'บรรยากาศริมน้ำและชุมชนมอญ — ถ่ายรูปสะพานและชิมอาหารท้องถิ่น',
        'description' => null, 'category' => 'nature', 'district' => 'สังขละบุรี', 'zone' => 'สังขละบุรี',
        'latitude' => 15.1568, 'longitude' => 98.4398, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=15.1568,98.4398', 'sort_order' => 430, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-sam-prasob-sangkhlaburi', 'name' => 'วัดสามประสบ', 'excerpt' => 'วัดใหญ่ของชาวมอญในเมืองสังขละ — โบสถ์และสถาปัตยกรรมโดดเด่น',
        'description' => null, 'category' => 'temple', 'district' => 'สังขละบุรี', 'zone' => 'สังขละบุรี',
        'latitude' => 15.1518, 'longitude' => 98.4472, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=15.1518,98.4472', 'sort_order' => 431, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-phu-wa-sangkhlaburi', 'name' => 'วัดถ้ำผาหวาย', 'excerpt' => 'วัดและถ้ำในเขตสังขละบุรี — ผสมธรรมชาติกับความเชื่อท้องถิ่น',
        'description' => null, 'category' => 'temple', 'district' => 'สังขละบุรี', 'zone' => 'เขื่อนเขาแหลม',
        'latitude' => 15.0895, 'longitude' => 98.4958, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=15.0895,98.4958', 'sort_order' => 432, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'khlong-jao-community-sangkhla', 'name' => 'ชุมชนคลังจาวหลวง', 'excerpt' => 'มุมถ่ายรูปสะพานมอญและชมวิถีชุมชนริมเขื่อน',
        'description' => null, 'category' => 'market', 'district' => 'สังขละบุรี', 'zone' => 'สังขละบุรี',
        'latitude' => 15.1468, 'longitude' => 98.4458, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=15.1468,98.4458', 'sort_order' => 433, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- พนมทวน (เพิ่มเติม) ----------
    [
        'slug' => 'khuean-thap-sala-phonthawan', 'name' => 'เขื่อนทับเสลา', 'excerpt' => 'เขื่อนเล็กในแม่น้ำเขื่อนวชิราลงกรณ์ — แวะชมวิวและชุมชนประมง',
        'description' => null, 'category' => 'viewpoint', 'district' => 'พนมทวน', 'zone' => null,
        'latitude' => 14.1428, 'longitude' => 99.6628, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.1428,99.6628', 'sort_order' => 520, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-thung-samphao-phonthawan', 'name' => 'วัดทุ่งสำเภา', 'excerpt' => 'วัดในละแวกพนมทวน — ศิลปะและบรรยากาศท้องถิ่น',
        'description' => null, 'category' => 'temple', 'district' => 'พนมทวน', 'zone' => null,
        'latitude' => 14.0989, 'longitude' => 99.7328, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0989,99.7328', 'sort_order' => 521, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-sophon-thong-phonthawan', 'name' => 'วัดถ้ำโสภณทอง', 'excerpt' => 'ถ้ำและพระพุทธรูปในเขตพนมทวน — แวะสักการะระหว่างทางเขื่อน',
        'description' => null, 'category' => 'temple', 'district' => 'พนมทวน', 'zone' => null,
        'latitude' => 14.0688, 'longitude' => 99.7088, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0688,99.7088', 'sort_order' => 522, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- เลาขวัญ (เพิ่มเติม) ----------
    [
        'slug' => 'wat-tham-khao-noi-historic-lao-khwan', 'name' => 'วัดถ้ำเขาน้อย (เลาขวัญ)', 'excerpt' => 'ถ้ำและจุดชมวิวเขาในละแวกเลาขวัญ — โปรดตรวจพิกัดก่อนเดินทาง อย่าสับสนกับพระธาตุเขาน้อยพนมทวน',
        'description' => null, 'category' => 'temple', 'district' => 'เลาขวัญ', 'zone' => null,
        'latitude' => 14.4218, 'longitude' => 99.4058, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.4218,99.4058', 'sort_order' => 620, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-khao-luang-lao-khwan', 'name' => 'วัดถ้ำเขาหลวง', 'excerpt' => 'วัดถ้ำและผาหินในเขตเลาขวัญ — เดินชมธรรมชาติใกล้อุทยานประวัติศาสตร์เมืองสิงห์',
        'description' => null, 'category' => 'temple', 'district' => 'เลาขวัญ', 'zone' => null,
        'latitude' => 14.3789, 'longitude' => 99.3718, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3789,99.3718', 'sort_order' => 621, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'dao-tung-waterfall-lao-khwan', 'name' => 'น้ำตกดาวตกเขาพังไกร', 'excerpt' => 'น้ำตกในเขตเลาขวัญ — เช็กฤดูกาลและความปลอดภัยเส้นทางเดินป่า',
        'description' => null, 'category' => 'nature', 'district' => 'เลาขวัญ', 'zone' => null,
        'latitude' => 14.5189, 'longitude' => 99.2988, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.5189,99.2988', 'sort_order' => 622, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-phraphutthabat-tham-krachaeng-lao-khwan', 'name' => 'วัดพระพุทธบาทถ้ำแกร่ง', 'excerpt' => 'วัดถ้ำและผาหินใกล้เมืองสิงห์ — เดินทางคู่ปราสาทเมืองสิงห์ได้',
        'description' => null, 'category' => 'temple', 'district' => 'เลาขวัญ', 'zone' => null,
        'latitude' => 14.4018, 'longitude' => 99.3918, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.4018,99.3918', 'sort_order' => 623, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- หนองปรือ (เพิ่มเติม) ----------
    [
        'slug' => 'wat-phraphutthabat-tham-ruesi-nongprue', 'name' => 'วัดพระพุทธบาทถ้ำฤษี', 'excerpt' => 'วัดและถ้ำในเขตหนองปรือ — ธรรมชาติและจุดสักการะ',
        'description' => null, 'category' => 'temple', 'district' => 'หนองปรือ', 'zone' => null,
        'latitude' => 14.6289, 'longitude' => 99.0928, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.6289,99.0928', 'sort_order' => 720, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-khao-chakan-nongprue', 'name' => 'วัดถ้ำเขาชะคาน', 'excerpt' => 'ถ้ำและจุดชมธรรมชาติในเขตหนองปรือ',
        'description' => null, 'category' => 'temple', 'district' => 'หนองปรือ', 'zone' => null,
        'latitude' => 14.5789, 'longitude' => 99.0388, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.5789,99.0388', 'sort_order' => 721, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ห้วยกระเจา (เพิ่มเติม) ----------
    [
        'slug' => 'wat-tham-khun-in-huai-kra-chao', 'name' => 'วัดถ้ำขุนอินทร์', 'excerpt' => 'วัดถ้ำในละแวกห้วยกระเจา — ธรรมชาติและความเชื่อท้องถิ่น',
        'description' => null, 'category' => 'temple', 'district' => 'ห้วยกระเจา', 'zone' => null,
        'latitude' => 14.3128, 'longitude' => 99.6388, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3128,99.6388', 'sort_order' => 820, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ด่านมะขามเตี้ย (เพิ่มเติม) ----------
    [
        'slug' => 'wat-tham-khao-pun-dan-makham-tia', 'name' => 'วัดถ้ำขุนชน', 'excerpt' => 'ถ้ำใหญ่ในเขตด่านมะขามเตี้ย — เที่ยวคู่เส้นทางแควน้อยตอนใต้',
        'description' => null, 'category' => 'temple', 'district' => 'ด่านมะขามเตี้ย', 'zone' => null,
        'latitude' => 13.8518, 'longitude' => 99.4188, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.8518,99.4188', 'sort_order' => 920, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-sila-thong-dan-makham-tia', 'name' => 'วัดถ้ำศิลาทอง', 'excerpt' => 'วัดและถ้ำในเขตด่านมะขามเตี้ย — ชมพระพุทธรูปและธรรมชาติใกล้ถนนหลัก',
        'description' => null, 'category' => 'temple', 'district' => 'ด่านมะขามเตี้ย', 'zone' => null,
        'latitude' => 13.8728, 'longitude' => 99.3828, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.8728,99.3828', 'sort_order' => 921, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- บ่อพลอย (เพิ่มเติม) ----------
    [
        'slug' => 'namtok-kradong-ngae-bo-phloi', 'name' => 'น้ำตกกระโดงแย้', 'excerpt' => 'น้ำตกในเขตบ่อพลอย — จับคู่ทุ่งบัวแดงได้ในฤดูที่เหมาะสม',
        'description' => null, 'category' => 'nature', 'district' => 'บ่อพลอย', 'zone' => null,
        'latitude' => 13.2489, 'longitude' => 99.4789, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.2489,99.4789', 'sort_order' => 1020, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'wat-tham-khao-pin-bo-phloi', 'name' => 'วัดถ้ำเขาพิน', 'excerpt' => 'วัดและถ้ำในละแวกบ่อพลอย — ชมวิวภูเขาและชุมชนท้องถิ่น',
        'description' => null, 'category' => 'temple', 'district' => 'บ่อพลอย', 'zone' => null,
        'latitude' => 13.1928, 'longitude' => 99.5228, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=13.1928,99.5228', 'sort_order' => 1021, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ศรีสวัสดิ์ (เพิ่มเติม) ----------
    [
        'slug' => 'elephants-world-srisawat', 'name' => 'ElephantsWorld', 'excerpt' => 'ศูนย์ช้างเชิงอนุรักษ์ศรีสวัสดิ์ — กิจกรรมเลี้ยงและเรียนรู้พฤติกรรมช้างอย่างมีจริยธรรม',
        'description' => null, 'category' => 'attraction', 'district' => 'ศรีสวัสดิ์', 'zone' => null,
        'latitude' => 14.0928, 'longitude' => 99.1089, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.0928,99.1089', 'sort_order' => 1140, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'namtok-hin-dat-erawan-np', 'name' => 'น้ำตกหินดาด (อุทยานแห่งชาติเอราวัณ)', 'excerpt' => 'น้ำตกชั้นบนในเขตเอราวัณ — เดินป่าต่อจากชั้นล่างและเช็กระดับน้ำ',
        'description' => null, 'category' => 'nature', 'district' => 'ศรีสวัสดิ์', 'zone' => 'อุทยานแห่งชาติเอราวัณ',
        'latitude' => 14.3718, 'longitude' => 99.1288, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3718,99.1288', 'sort_order' => 1141, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'namtok-phon-phoem-erawan-np', 'name' => 'น้ำตกผผึม (โซนเอราวัณ)', 'excerpt' => 'ธารน้ำและธรรมชาติในเส้นทางเที่ยวเอราวัณ — เตรียมกำลังและน้ำดื่มให้พร้อม',
        'description' => null, 'category' => 'nature', 'district' => 'ศรีสวัสดิ์', 'zone' => 'อุทยานแห่งชาติเอราวัณ',
        'latitude' => 14.3598, 'longitude' => 99.1418, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3598,99.1418', 'sort_order' => 1142, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'rajjaprabha-dam-view-east-srisawat', 'name' => 'จุดชมวิวฝั่งตะวันออกเขื่อนศรีนครินทร์', 'excerpt' => 'มุมมองทะเลสาบและภูเขาปูนจากฝั่งศรีสวัสดิ์',
        'description' => null, 'category' => 'viewpoint', 'district' => 'ศรีสวัสดิ์', 'zone' => 'เขื่อนศรีนครินทร์',
        'latitude' => 14.3728, 'longitude' => 99.0188, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3728,99.0188', 'sort_order' => 1143, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

    // ---------- ไทรโยค (เพิ่มเติม) ----------
    [
        'slug' => 'namtok-sai-yok-yai', 'name' => 'น้ำตกไทรโยคใหญ่', 'excerpt' => 'น้ำตกใหญ่ในอุทยานแห่งชาติไทรโยค — ลงเล่นน้ำได้ตามระเบียบอุทยาน',
        'description' => null, 'category' => 'nature', 'district' => 'ไทรโยค', 'zone' => 'อุทยานไทรโยค',
        'latitude' => 14.4418, 'longitude' => 98.9168, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.4418,98.9168', 'sort_order' => 1240, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'namtok-dti-ngong-saiyok', 'name' => 'น้ำตกจิกกะโหลก', 'excerpt' => 'น้ำตกธรรมชาติในเขตไทรโยค — ศึกษาทางเข้าและความปลอดภัยก่อนเดินทาง',
        'description' => null, 'category' => 'nature', 'district' => 'ไทรโยค', 'zone' => 'อุทยานไทรโยค',
        'latitude' => 14.4698, 'longitude' => 98.9388, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.4698,98.9388', 'sort_order' => 1241, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'tham-daowadueng-saiyok', 'name' => 'ถ้ำดาวดึงส์', 'excerpt' => 'ถ้ำใหญ่ในเขตไทรโยค — ทัวร์เรือหรือล่องแพในแควน้อยตามฤดูกาล',
        'description' => null, 'category' => 'nature', 'district' => 'ไทรโยค', 'zone' => 'ริมแม่น้ำแควน้อย',
        'latitude' => 14.4188, 'longitude' => 98.9898, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.4188,98.9898', 'sort_order' => 1242, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'tham-lawana-saiyok', 'name' => 'ถ้ำละว้า', 'excerpt' => 'ถ้ำหินปูนที่เข้าชมได้ในเขตไทรโยค — เช็กเส้นทางเรือหรือเดินป่ากับเจ้าหน้าที่',
        'description' => null, 'category' => 'nature', 'district' => 'ไทรโยค', 'zone' => 'อุทยานไทรโยค',
        'latitude' => 14.5688, 'longitude' => 98.8958, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.5688,98.8958', 'sort_order' => 1243, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'ban-khao-lam-viewpoint-saiyok', 'name' => 'จุดชมวิวบ้านเขาลาม', 'excerpt' => 'จุดถ่ายรูปวิวหมอกและแม่น้ำแควน้อยไฮไลต์ของไทรโยค',
        'description' => null, 'category' => 'viewpoint', 'district' => 'ไทรโยค', 'zone' => 'ริมแม่น้ำแควน้อย',
        'latitude' => 14.3578, 'longitude' => 98.9698, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.3578,98.9698', 'sort_order' => 1244, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'sai-yok-noi-railway-station', 'name' => 'สถานีรถไฟน้ำตกไทรโยคน้อย', 'excerpt' => 'สถานีขบวนท่องเที่ยวสายน้ำตก — ต่อทริปชมน้ำตกไทรโยคน้อยและทางรถไฟ',
        'description' => null, 'category' => 'attraction', 'district' => 'ไทรโยค', 'zone' => 'น้ำตกไทรโยคน้อย',
        'latitude' => 14.2578, 'longitude' => 98.9648, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.2578,98.9648', 'sort_order' => 1245, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'pak-saeng-viewpoint-saiyok', 'name' => 'จุดชมวิวปากแซง', 'excerpt' => 'จุดชมโค้งแม่น้ำและหุบเขาในละแวกไทรโยค — เช็กทางเข้ากับคนท้องถิ่น',
        'description' => null, 'category' => 'viewpoint', 'district' => 'ไทรโยค', 'zone' => 'ริมแม่น้ำแควน้อย',
        'latitude' => 14.2958, 'longitude' => 98.9588, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.2958,98.9588', 'sort_order' => 1246, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],
    [
        'slug' => 'river-kwai-jungle-rafts-saiyok', 'name' => 'River Kwai Jungle Rafts', 'excerpt' => 'ที่พักแพลอยน้ำริมแควน้อย — แวะชมหรือพักค้างคืนตามการเปิดรับของรีสอร์ท',
        'description' => null, 'category' => 'attraction', 'district' => 'ไทรโยค', 'zone' => 'ริมแม่น้ำแควน้อย',
        'latitude' => 14.2688, 'longitude' => 98.9588, 'address' => null, 'cover_image' => null,
        'google_maps_url' => 'https://www.google.com/maps?q=14.2688,98.9588', 'sort_order' => 1247, 'is_active' => 1,
        'meta_title' => null, 'meta_description' => null,
    ],

```
