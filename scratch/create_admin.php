<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

$email = 'admin@scrapper.com';
$password = 'password';

echo "Creating user $email...\n";

try {
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "User already exists. Updating password...\n";
        $user->password = Hash::make($password);
        $user->save();
    } else {
        $user = new User();
        $user->name = 'Admin Scrapper';
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = UserRole::ADMIN;
        $user->active = true;
        $user->save();
        echo "User created successfully.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
