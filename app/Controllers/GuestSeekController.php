<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Property;
use App\Services\GuestSeekLeadService;

class GuestSeekController extends Controller
{
    public function show(): void
    {
        $zones = Property::zonesForSelect();
        $types = [
            ''          => 'ไม่ระบุ / ทุกประเภท',
            'raft'      => 'แพพัก',
            'resort'    => 'รีสอร์ท',
            'homestay'  => 'โฮมสเตย์',
            'house'     => 'บ้านพัก',
            'pool_villa'=> 'บ้านพูลวิลล่า',
            'hotel'     => 'โรงแรม',
            'camping'   => 'แคมป์ปิ้ง',
        ];
        $typeParam = trim((string)($_GET['type'] ?? ''));
        $initialPropertyType = array_key_exists($typeParam, $types) ? $typeParam : '';

        $this->view('guest_seek/form', [
            'meta_title'       => 'ขอให้ช่วยหาที่พักกาญจนบุรี — แพกาญ.com',
            'meta_description' => 'พักกาญแบบตรงใจในคลิกเดียว บอกโซน งบ และความชอบ เราช่วยให้มีคนพร้อมโต้ตอบคำถามจากฝั่งที่พัก',
            'zones'            => $zones,
            'types'            => $types,
            'initial_property_type' => $initialPropertyType,
        ]);
    }

    public function store(): void
    {
        if (!empty($_POST['website'])) {
            http_response_code(400);
            exit;
        }

        $data = $this->validate([
            'name'           => 'required|max:120',
            'phone'          => 'required|phone',
            'email'          => 'email|max:160',
            'preferred_zone' => 'required|max:80',
            'message'        => 'max:2000',
            'budget_max'     => 'numeric',
            'consent'        => 'required',
        ]);

        $cin = trim((string)($_POST['check_in'] ?? ''));
        $cout = trim((string)($_POST['check_out'] ?? ''));
        if ($cin !== '' && strtotime($cin) === false) {
            Session::flash('error', 'วันที่เข้าพักไม่ถูกต้อง');
            back();
        }
        if ($cout !== '' && strtotime($cout) === false) {
            Session::flash('error', 'วันที่ออกไม่ถูกต้อง');
            back();
        }

        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $n  = (int)Database::fetch(
            "SELECT COUNT(*) c FROM leads WHERE source = 'guest_seek' AND request_ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            ['ip' => $ip]
        )['c'];
        if ($n >= 5) {
            Session::flash('error', 'ส่งคำขอบ่อยเกินไป กรุณารอสักครู่');
            back();
        }

        $zone  = trim((string)$data['preferred_zone']);
        $ptype = trim((string)($_POST['preferred_property_type'] ?? ''));
        $allowedPt = ['raft', 'resort', 'homestay', 'house', 'pool_villa', 'hotel', 'camping'];
        if ($ptype !== '' && !in_array($ptype, $allowedPt, true)) {
            $ptype = '';
        }
        $budgetRaw = trim((string)($_POST['budget_max'] ?? ''));
        $budgetMax = $budgetRaw !== '' ? (float)$budgetRaw : null;
        $guestCountRaw = trim((string)($_POST['guest_count'] ?? ''));

        $recipients = GuestSeekLeadService::matchRecipientOwners(
            $zone !== '' ? $zone : null,
            $ptype !== '' ? $ptype : null,
            $budgetMax
        );

        $leadId = Database::insert('leads', [
            'source'                  => 'guest_seek',
            'name'                    => $data['name'],
            'phone'                   => $data['phone'],
            'email'                   => $data['email'] ?? null,
            'preferred_zone'          => $zone !== '' ? $zone : null,
            'preferred_property_type' => $ptype !== '' ? $ptype : null,
            'budget_max'              => $budgetMax,
            'line_contact'            => trim((string)($_POST['line_contact'] ?? '')) ?: null,
            'request_ip'              => $ip ?: null,
            'message'                 => $data['message'] ?? null,
            'check_in'                => $cin !== '' ? $cin : null,
            'check_out'               => $cout !== '' ? $cout : null,
            'guest_count'             => $guestCountRaw !== '' ? max(0, (int)$guestCountRaw) : null,
            'status'                  => 'new',
        ]);

        $leadRow = Database::fetch('SELECT * FROM leads WHERE id = :id', ['id' => $leadId]);
        if ($leadRow && $recipients !== []) {
            GuestSeekLeadService::notifyRecipients($leadRow, $recipients);
        }

        Session::flash(
            'success',
            $recipients === []
                ? 'บันทึกคำขอของคุณแล้ว — ตอนนี้ในโซนที่เลือกอาจยังไม่มีที่พักตอบรับว่าง เราจะประสานต่อและอาจติดต่อกลับเมื่อมีตัวเลือกเหมาะ ๆ'
                : 'ส่งคำขอแล้ว — อยู่ในโหมดรอฟังจากที่พักที่ตรงกับที่คุณต้องการ ตรวจสายจากเบอร์ไม่คุ้นเป็นพิเศษนะครับ'
        );
        redirect(url('/guest-seek/thanks'));
    }

    public function thanks(): void
    {
        $this->view('guest_seek/thanks', [
            'meta_title' => 'ขอบคุณ — แพกาญ.com',
        ]);
    }
}
