<?php
/**
 * Quick Installation Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'staravcisi.com';
$dbname = 'wawahousesql';
$user = 'wawahousekullanici';
$pass = '14531453aO.!';

echo "<!DOCTYPE html><html><head><title>Database Installation</title><style>
body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
</style></head><body>";

echo "<h1>🚀 Veritabanı Kurulum</h1>";

$sqlFile = __DIR__ . '/database_complete.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ database_complete.sql bulunamadı!</div>";
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "<div class='success'>✅ Bağlantı başarılı!</div>";

    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $success = 0;
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                $success++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }

    echo "<div class='success'>✅ $success komut çalıştırıldı!</div>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='info'>📋 " . count($tables) . " tablo mevcut</div>";

    echo "<h2>🎉 Kurulum Tamamlandı!</h2>";
    echo "<p><a href='/' class='btn'>Ana Sayfa</a> <a href='/admin/' class='btn'>Admin Panel</a></p>";
    echo "<p><strong>Admin:</strong> admin / admin123</p>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
