<?php
/**
 * Copy to app/Config/app.local.php on local/VPS when you need overrides.
 * This file is safe to commit; app.local.php is ignored by git.
 */
return [
    'env'   => 'production',
    'debug' => false,

    'maintenance' => [
        'enabled'       => false,
        'bypass_secret' => '',
    ],
];
