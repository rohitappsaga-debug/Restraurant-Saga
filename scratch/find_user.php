<?php

function check_user($db_name, $email) {
    try {
        $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=$db_name", "postgres", "root");
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

$email = 'admin@scrapper.com';
echo "Checking for $email in rms: " . (check_user('rms', $email) ?: 'NOT FOUND') . "\n";
echo "Checking for $email in rms-laravel: " . (check_user('rms-laravel', $email) ?: 'NOT FOUND') . "\n";
echo "Checking for $email in business_scraper: " . (check_user('business_scraper', $email) ?: 'NOT FOUND') . "\n";
