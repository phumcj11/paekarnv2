<?php
/**
 * แพกาญ.com — Front Controller
 * วาง public/ ไว้เป็น DocumentRoot ใน production
 * บน XAMPP local: เข้าผ่าน http://localhost/paekan_v1/
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Application.php';

\App\Core\Application::boot(dirname(__DIR__));
\App\Core\Application::run();
