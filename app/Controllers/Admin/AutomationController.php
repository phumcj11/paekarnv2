<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\CronService;

class AutomationController extends Controller
{
    public function index(): void
    {
        $logs = Database::fetchAll("SELECT * FROM cron_logs ORDER BY id DESC LIMIT 100");
        $jobs = array_keys(CronService::jobs());

        // last status per job
        $lastByJob = [];
        foreach ($jobs as $j) {
            $lastByJob[$j] = Database::fetch("SELECT * FROM cron_logs WHERE job = :j ORDER BY id DESC LIMIT 1", ['j' => $j]);
        }

        View::render('admin/automation/index', [
            'page_title' => 'Automation', 'logs' => $logs,
            'jobs' => $jobs, 'lastByJob' => $lastByJob,
        ], 'layouts/admin');
    }

    public function run(): void
    {
        $only = $_POST['job'] ?? null;
        $results = CronService::runAll($only ?: null);
        Session::flash('success', 'รัน Cron เรียบร้อย — ' . count($results) . ' jobs');
        redirect(url('/admin/automation'));
    }
}
