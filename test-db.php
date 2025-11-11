<?php
// Database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

$host = 'staravcisi.com';
$dbname = 'wawahousesql';
$user = 'wawahousekullanici';
$pass = 'guvenli_sifre';

echo "<p>Trying to connect to: $host / $dbname</p>";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Tables in database (" . count($tables) . "):</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Check site_settings table
    if (in_array('site_settings', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM site_settings");
        $count = $stmt->fetch()['count'];
        echo "<p>site_settings table has $count rows</p>";
    } else {
        echo "<p style='color: red;'>❌ site_settings table NOT FOUND!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
}
