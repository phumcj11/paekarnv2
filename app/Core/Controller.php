<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($template, $data, $layout);
    }

    protected function adminView(string $template, array $data = []): void
    {
        View::render('admin/' . $template, $data, 'layouts/admin');
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(?string $key = null, $default = null)
    {
        $data = array_merge($_GET, $_POST);
        if ($key === null) return $data;
        return $data[$key] ?? $default;
    }

    protected function validate(array $rules, array $messages = []): array
    {
        $data = array_merge($_GET, $_POST);
        $v = Validator::make($data);
        if (!$v->validate($rules, $messages)) {
            Session::withOld($data);
            Session::flash('errors', json_encode($v->errors()));
            Session::flash('error', 'กรุณาตรวจสอบข้อมูลที่กรอกอีกครั้ง');
            back();
        }
        return $data;
    }
}
