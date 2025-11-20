<?php
/**
 * Sistem Test Dosyası
 * Bu dosyayı tarayıcınızda açarak sisteminizin çalışıp çalışmadığını kontrol edebilirsiniz
 */

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Sistem Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Sistem Test Raporu</h1>";

// PHP Version Test
echo "<div class='section'>";
echo "<h2>1. PHP Versiyonu</h2>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<div class='success'>✅ PHP Version: " . PHP_VERSION . " (Uyumlu)</div>";
} else {
    echo "<div class='error'>❌ PHP Version: " . PHP_VERSION . " (Minimum 7.4 gerekli)</div>";
}
echo "</div>";

// Required Extensions
echo "<div class='section'>";
echo "<h2>2. PHP Eklentileri</h2>";
$required_extensions = ['pdo_mysql', 'json', 'mbstring', 'gd', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext yüklü</div>";
    } else {
        echo "<div class='error'>❌ $ext yüklü değil</div>";
    }
}
echo "</div>";

// Config File Test
echo "<div class='section'>";
echo "<h2>3. Config Dosyası</h2>";
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "<div class='success'>✅ config.php dosyası mevcut</div>";

    // Try to include config
    try {
        require_once __DIR__ . '/includes/config.php';
        echo "<div class='success'>✅ config.php başarıyla yüklendi</div>";

        // Check constants
        if (defined('DB_HOST')) {
            echo "<div class='info'>📊 DB_HOST: " . DB_HOST . "</div>";
        }
        if (defined('SITE_URL')) {
            echo "<div class='info'>🌐 SITE_URL: " . SITE_URL . "</div>";
        }

    } catch (Exception $e) {
        echo "<div class='error'>❌ config.php yüklenirken hata: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='error'>❌ config.php dosyası bulunamadı</div>";
}
echo "</div>";

// Database Connection Test
echo "<div class='section'>";
echo "<h2>4. Veritabanı Bağlantısı</h2>";
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div class='success'>✅ Veritabanı bağlantısı başarılı</div>";

        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='info'>📊 Toplam " . count($tables) . " tablo bulundu</div>";

        // Check specific tables
        $required_tables = ['products', 'categories', 'site_settings', 'sliders'];
        foreach ($required_tables as $table) {
            if (in_array($table, $tables)) {
                echo "<div class='success'>✅ Tablo mevcut: $table</div>";
            } else {
                echo "<div class='error'>❌ Tablo bulunamadı: $table</div>";
            }
        }

    } catch (PDOException $e) {
        echo "<div class='error'>❌ Veritabanı bağlantı hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'>💡 İpucu: config.php dosyasındaki DB_HOST değerini kontrol edin. 'localhost', '127.0.0.1' veya sunucu adınızı deneyin.</div>";
    }
} else {
    echo "<div class='error'>❌ Veritabanı sabitleri tanımlı değil</div>";
}
echo "</div>";

// Session Test
echo "<div class='section'>";
echo "<h2>5. Session (Oturum) Testi</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='success'>✅ Session aktif</div>";
    echo "<div class='info'>📊 Session ID: " . session_id() . "</div>";
} else {
    echo "<div class='error'>❌ Session aktif değil</div>";
}
echo "</div>";

// HTTPS Test
echo "<div class='section'>";
echo "<h2>6. HTTPS Kontrolü</h2>";
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($isHttps) {
    echo "<div class='success'>✅ Site HTTPS ile çalışıyor</div>";
} else {
    echo "<div class='info'>ℹ️ Site HTTP ile çalışıyor (HTTPS önerilir)</div>";
}
echo "</div>";

// Writable Directories
echo "<div class='section'>";
echo "<h2>7. Yazılabilir Klasörler</h2>";
$directories = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/sliders',
    'uploads/banners',
    'uploads/theme',
    'cache'
];

foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<div class='success'>✅ $dir yazılabilir</div>";
        } else {
            echo "<div class='error'>❌ $dir yazılabilir değil (chmod 755 veya 777 yapın)</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ $dir klasörü bulunamadı (kurulum sırasında oluşturulacak)</div>";
    }
}
echo "</div>";

// Server Info
echo "<div class='section'>";
echo "<h2>8. Sunucu Bilgileri</h2>";
echo "<div class='info'>🖥️ Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "</div>";
echo "<div class='info'>📍 Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor') . "</div>";
echo "<div class='info'>🌐 HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Bilinmiyor') . "</div>";
echo "</div>";

echo "<div class='section' style='background: #e7f3ff;'>";
echo "<h2>✅ Sonuç</h2>";
echo "<p>Yukarıdaki testlerin hepsi ✅ işaretli ise sisteminiz çalışmaya hazır!</p>";
echo "<p>Hata varsa (❌), ilgili bölümü düzeltin ve sayfayı yenileyin.</p>";
echo "<hr>";
echo "<p><strong>📌 Önemli:</strong> Bu test dosyasını (test.php) production'da silmeyi unutmayın!</p>";
echo "</div>";

echo "</body></html>";
