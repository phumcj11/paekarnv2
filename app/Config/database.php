<?php
/**
 * Database Config
 *
 * แก้ที่นี่ให้ตรงกับเครื่องของคุณ
 * (XAMPP default: host=127.0.0.1, user=root, pass=)
 */
return [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'paekarnv2',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
    'options'  => [
        // PDO options ใน Database.php จะถูก merge เพิ่ม
    ],
];
