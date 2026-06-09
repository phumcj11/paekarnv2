<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;

class LeadController extends Controller
{
    /** @var list<string> */
    private const STATUSES = [
        'new',
        'contacted',
        'coupon_purchased',
        'sent_to_owner',
        'confirmed',
        'stayed',
        'lost',
    ];

    public function index(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];

        if (!empty($_GET['status']) && in_array($_GET['status'], self::STATUSES, true)) {
            $where .= ' AND l.status = :st';
            $params['st'] = $_GET['status'];
        }
        if (!empty($_GET['q'])) {
            $where .= ' AND (l.name LIKE :q OR l.phone LIKE :q OR l.email LIKE :q OR l.message LIKE :q)';
            $params['q'] = '%' . $_GET['q'] . '%';
        }

        $rows = Database::fetchAll(
            "SELECT l.*, p.name AS property_name FROM leads l
             LEFT JOIN properties p ON p.id = l.property_id
             WHERE $where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $total = (int)Database::fetch(
            "SELECT COUNT(*) c FROM leads l WHERE $where",
            $params
        )['c'];

        $stats = [];
        foreach (self::STATUSES as $st) {
            $stats[$st] = (int)Database::fetch(
                'SELECT COUNT(*) c FROM leads WHERE status = :s',
                ['s' => $st]
            )['c'];
        }

        View::render('admin/leads/index', [
            'page_title' => 'CRM / Leads',
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'stats'      => $stats,
            'statuses'   => self::STATUSES,
        ], 'layouts/admin');
    }

    public function updateStatus(int $id): void
    {
        $st = trim((string)($_POST['status'] ?? ''));
        $allowed = array_merge(self::STATUSES, ['qualified', 'booked']); // เก่า: ก่อนรัน migration เท่านั้น
        if (!in_array($st, $allowed, true)) {
            Session::flash('error', 'สถานะไม่ถูกต้อง');
            back();
        }
        $row = Database::fetch('SELECT id, status FROM leads WHERE id = :id', ['id' => $id]);
        if (!$row) {
            Session::flash('error', 'ไม่พบ Lead');
            back();
        }
        Database::update('leads', ['status' => $st], 'id = :id', ['id' => $id]);
        AuditLog::record('lead_status_update', ['lead_id' => $id, 'from' => $row['status'], 'to' => $st], 'lead', $id);
        Session::flash('success', 'อัปเดตสถานะ Lead แล้ว');
        back();
    }

    public function updateNote(int $id): void
    {
        $note = trim((string)($_POST['note'] ?? ''));
        $row = Database::fetch('SELECT id FROM leads WHERE id = :id', ['id' => $id]);
        if (!$row) {
            Session::flash('error', 'ไม่พบ Lead');
            back();
        }
        Database::update('leads', ['note' => $note !== '' ? $note : null], 'id = :id', ['id' => $id]);
        AuditLog::record('lead_note_update', ['lead_id' => $id], 'lead', $id);
        Session::flash('success', 'บันทึกโน้ตแล้ว');
        back();
    }
}
