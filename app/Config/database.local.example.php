<?php
/**
 * Copy to app/Config/database.local.php on local/VPS and fill real credentials.
 * database.local.php is ignored by git.
 */
return [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'paekarnv2',
    'username'  => 'paekarn_user',
    'password'  => 'change-me',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [],
];
