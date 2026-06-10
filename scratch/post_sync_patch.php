<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Password Hash Patching...\n";

try {
    $users = DB::table('users')->get();
    $patched = 0;

    foreach ($users as $user) {
        if (str_starts_with($user->password, '$2a$')) {
            $newPassword = str_replace('$2a$', '$2y$', $user->password);
            DB::table('users')->where('id', $user->id)->update(['password' => $newPassword]);
            echo "Patched user: {$user->email}\n";
            $patched++;
        }
    }

    echo "Completed. Patched {$patched} users.\n";
    
    echo "Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
