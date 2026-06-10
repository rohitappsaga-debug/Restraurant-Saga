<?php

function get_tables($db_name) {
    try {
        $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=$db_name", "postgres", "root");
        $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

echo "RMS Database Tables:\n";
$rms_tables = get_tables('rms');
if (is_array($rms_tables)) {
    print_r($rms_tables);
} else {
    echo $rms_tables . "\n";
}

echo "\nRMS-Laravel Database Tables:\n";
$rms_laravel_tables = get_tables('rms-laravel');
if (is_array($rms_laravel_tables)) {
    print_r($rms_laravel_tables);
} else {
    echo $rms_laravel_tables . "\n";
}
