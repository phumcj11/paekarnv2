<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;

class AuditLogsController extends Controller
{
    public function index(): void
    {
        $tableOk = true;
        $rows = [];
        try {
            $where = '1=1';
            $params = [];
            $action = trim((string)($_GET['action'] ?? ''));
            if ($action !== '') {
                $where .= ' AND action LIKE :a';
                $params['a'] = '%' . $action . '%';
            }
            $entity = trim((string)($_GET['entity'] ?? ''));
            if ($entity !== '') {
                $where .= ' AND entity_type LIKE :e';
                $params['e'] = '%' . $entity . '%';
            }
            $rows = Database::fetchAll(
                "SELECT * FROM audit_logs WHERE $where ORDER BY id DESC LIMIT 250",
                $params
            );
        } catch (\Throwable) {
            $tableOk = false;
        }
        View::render('admin/audit_logs/index', [
            'page_title' => 'Audit log',
            'rows'       => $rows,
            'table_ok'   => $tableOk,
        ], 'layouts/admin');
    }
}
