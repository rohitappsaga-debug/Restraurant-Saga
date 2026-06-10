<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "User List:\n";
$users = DB::table('users')->get(['id', 'email', 'password']);
foreach ($users as $user) {
    echo "ID: {$user->id} | Email: {$user->email} | Hash: " . substr($user->password, 0, 8) . "...\n";
}
