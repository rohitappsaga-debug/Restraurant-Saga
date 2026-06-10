<?php
header('Content-Type: application/json');
echo json_encode([
    'opcache' => opcache_get_status(false) ? 'Enabled' : 'Disabled',
    'php_sapi' => php_sapi_name()
]);
