<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\MenuItem;

$category = Category::first();
if (!$category) {
    echo "No category found to test with.";
    exit;
}

$data = [
    'name' => 'Test Item ' . time(),
    'category' => $category->name,
    'category_id' => $category->id,
    'price' => 10.00,
    'description' => 'Test description',
    'available' => true,
    'preparation_time' => 10,
    'is_veg' => true,
];

try {
    $item = MenuItem::create($data);
    echo "Successfully created menu item: " . $item->name . " (ID: " . $item->id . ")\n";
    $item->delete();
    echo "Successfully deleted test item.\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
