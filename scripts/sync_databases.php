<?php
$conn_src = "host=localhost port=5432 dbname=rms user=postgres password=root";
$conn_dst = "host=localhost port=5432 dbname=rms-laravel user=postgres password=root";

$db_src = pg_connect($conn_src);
$db_dst = pg_connect($conn_dst);

if (!$db_src || !$db_dst) {
    die("Database connection failed\n");
}

// Get all tables except some Laravel specific ones if needed
// But since they match, we'll sync all
$tables = [
    'users', 'categories', 'ingredients', 'suppliers', 'tables',
    'menu_items', 'menu_item_modifiers', 'recipes', 'purchase_orders',
    'purchase_order_items', 'orders', 'order_items', 'delivery_details',
    'payment_transactions', 'reservations', 'notifications', 'activity_logs',
    'daily_sales', 'settings', 'sessions', 'cache', 'cache_locks',
    'failed_jobs', 'job_batches', 'jobs', 'password_reset_tokens',
    'personal_access_tokens'
];

// Disable constraints
pg_query($db_dst, "SET session_replication_role = 'replica'");

foreach ($tables as $table) {
    echo "Syncing table: $table... ";
    
    // Truncate destination
    pg_query($db_dst, "TRUNCATE TABLE \"$table\" CASCADE");
    
    // Fetch from source
    $result = pg_query($db_src, "SELECT * FROM \"$table\"");
    $count = 0;
    while ($row = pg_fetch_assoc($result)) {
        $cols = array_keys($row);
        $vals = array_map(function($v) use ($db_dst) {
            if ($v === null) return 'NULL';
            return "'" . pg_escape_string($db_dst, $v) . "'";
        }, array_values($row));
        
        $sql = "INSERT INTO \"$table\" (\"" . implode('", "', $cols) . "\") VALUES (" . implode(', ', $vals) . ")";
        $insert = pg_query($db_dst, $sql);
        if (!$insert) {
            echo "\nError in $table: " . pg_last_error($db_dst) . "\n";
        } else {
            $count++;
        }
    }
    echo "Done ($count rows).\n";
}

// Re-enable constraints
pg_query($db_dst, "SET session_replication_role = 'origin'");

// Patch password hashes for Laravel compatibility (fix $2a$ and $2b$ prefixes)
echo "Patching user password hashes... ";
$patch = pg_query($db_dst, "UPDATE users SET password = REPLACE(REPLACE(password, '$2a$', '$2y$'), '$2b$', '$2y$') WHERE password LIKE '$2a$%' OR password LIKE '$2b$%'");
if ($patch) {
    echo "Done.\n";
} else {
    echo "Error: " . pg_last_error($db_dst) . "\n";
}

// Update sequences for tables with serial/bigserial columns
$sequences = [
    ['failed_jobs', 'id', 'failed_jobs_id_seq'],
    ['jobs', 'id', 'jobs_id_seq'],
    ['migrations', 'id', 'migrations_id_seq'],
    ['orders', 'order_number', 'orders_order_number_seq'],
    ['personal_access_tokens', 'id', 'personal_access_tokens_id_seq']
];

foreach ($sequences as $seq) {
    echo "Updating sequence for {$seq[0]}... ";
    pg_query($db_dst, "SELECT setval('{$seq[2]}', COALESCE((SELECT MAX({$seq[1]}) FROM \"{$seq[0]}\"), 1), true)");
    echo "Done.\n";
}

pg_close($db_src);
pg_close($db_dst);
echo "\nSynchronization complete!\n";
