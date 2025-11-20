<?php
/**
 * Profesyonel Kurulum Sihirbazı - OpenCart Benzeri
 */
session_start();

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Kurulum tamamlandı mı kontrol et
if (file_exists(__DIR__ . '/includes/config.php')) {
    $config = file_get_contents(__DIR__ . '/includes/config.php');
    if (strpos($config, 'DB_HOST') !== false && !isset($_GET['reinstall'])) {
        require_once __DIR__ . '/includes/config.php';
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $result = $pdo->query("SELECT COUNT(*) FROM site_settings");
            if ($result && $result->fetchColumn() > 0) {
                header('Location: /');
                exit;
            }
        } catch (Exception $e) {
            // Kurulum gerekiyor
        }
    }
}

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Adım 2: Veritabanı ayarları
    if ($step === 2) {
        $db_host = trim($_POST['db_host']);
        $db_name = trim($_POST['db_name']);
        $db_user = trim($_POST['db_user']);
        $db_pass = $_POST['db_pass'];

        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $config_content = '<?php
define("DB_HOST", "' . $db_host . '");
define("DB_NAME", "' . $db_name . '");
define("DB_USER", "' . $db_user . '");
define("DB_PASS", "' . addslashes($db_pass) . '");
define("DB_CHARSET", "utf8mb4");
define("SITE_URL", "' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '");
define("SITE_NAME", "E-Ticaret");
define("SITE_EMAIL", "info@' . $_SERVER['HTTP_HOST'] . '");
define("SECRET_KEY", "' . bin2hex(random_bytes(32)) . '");
define("SESSION_LIFETIME", 3600);
define("ROOT_PATH", __DIR__ . "/..");
define("UPLOAD_PATH", ROOT_PATH . "/uploads");
define("CACHE_PATH", ROOT_PATH . "/cache");
define("CURRENCY_CODE", "TRY");
define("CURRENCY_SYMBOL", "₺");
define("TAX_RATE", 20);
define("ITEMS_PER_PAGE", 12);
define("DEBUG_MODE", false);
date_default_timezone_set("Europe/Istanbul");
if (DEBUG_MODE) { error_reporting(E_ALL); ini_set("display_errors", 1); } else { error_reporting(0); ini_set("display_errors", 0); }
';
            file_put_contents(__DIR__ . '/includes/config.php', $config_content);
            $_SESSION['install'] = ['db_host' => $db_host, 'db_name' => $db_name, 'db_user' => $db_user, 'db_pass' => $db_pass];
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            $error = 'Veritabanı bağlantı hatası: ' . $e->getMessage();
        }
    }

    // Adım 3: Tabloları oluştur
    if ($step === 3) {
        try {
            require_once __DIR__ . '/includes/config.php';
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create upload directories
            $upload_dirs = [
                __DIR__ . '/uploads',
                __DIR__ . '/uploads/products',
                __DIR__ . '/uploads/categories',
                __DIR__ . '/uploads/sliders',
                __DIR__ . '/uploads/banners',
                __DIR__ . '/uploads/theme',
                __DIR__ . '/cache'
            ];

            foreach ($upload_dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            $sql_files = ['database_complete.sql', 'database_opencart_upgrade.sql'];
            $executed = 0;
            $errors = [];

            foreach ($sql_files as $sql_file) {
                if (file_exists(__DIR__ . '/' . $sql_file)) {
                    $sql = file_get_contents(__DIR__ . '/' . $sql_file);

                    // Remove comments
                    $sql = preg_replace('/--.*$/m', '', $sql);
                    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

                    // Split into statements
                    $statements = array_filter(array_map('trim', explode(';', $sql)));

                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            try {
                                $pdo->exec($statement);
                                $executed++;
                            } catch (PDOException $e) {
                                // Ignore "table already exists" errors
                                if (strpos($e->getMessage(), 'already exists') === false) {
                                    $errors[] = substr($statement, 0, 100) . '... - ' . $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }

            $_SESSION['install_stats'] = [
                'executed' => $executed,
                'errors' => count($errors)
            ];

            if (count($errors) > 10) {
                throw new Exception('Çok fazla hata oluştu. İlk hata: ' . $errors[0]);
            }

            header('Location: ?step=4');
            exit;
        } catch (Exception $e) {
            $error = 'Tablo oluşturma hatası: ' . $e->getMessage();
        }
    }

    // Adım 4: Admin hesabı
    if ($step === 4) {
        $admin_name = trim($_POST['admin_name']);
        $admin_email = trim($_POST['admin_email']);
        $admin_pass = $_POST['admin_pass'];
        $admin_pass2 = $_POST['admin_pass2'];
        $site_name = trim($_POST['site_name']);

        if (strlen($admin_pass) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } elseif ($admin_pass !== $admin_pass2) {
            $error = 'Şifreler eşleşmiyor.';
        } else {
            try {
                require_once __DIR__ . '/includes/config.php';
                $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, 'admin', 1) ON DUPLICATE KEY UPDATE password = ?, email = ?");
                $stmt->execute([$admin_name, $admin_email, $hash, $admin_name, $hash, $admin_email]);

                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$site_name, $site_name]);

                header('Location: ?step=5');
                exit;
            } catch (PDOException $e) {
                $error = 'Admin hesabı oluşturma hatası: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum Sihirbazı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; padding: 40px 0; }
        .install-container { max-width: 700px; margin: 0 auto; }
        .install-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .install-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; }
        .install-header h1 { margin: 0; font-size: 2rem; }
        .install-body { padding: 40px; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-weight: bold; position: relative; margin: 0 20px; }
        .step.active { background: #667eea; color: white; }
        .step.completed { background: #28a745; color: white; }
        .step::after { content: ''; position: absolute; width: 40px; height: 3px; background: #e9ecef; right: -40px; top: 50%; transform: translateY(-50%); }
        .step:last-child::after { display: none; }
        .step.completed::after { background: #28a745; }
        .btn-install { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; font-size: 1.1rem; }
        .requirement-item { padding: 10px 15px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .requirement-item.success { background: #d4edda; }
        .requirement-item.error { background: #f8d7da; }
        .success-icon { font-size: 4rem; color: #28a745; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="install-card">
            <div class="install-header">
                <h1><i class="fas fa-shopping-cart"></i> E-Ticaret Kurulumu</h1>
                <p>OpenCart Benzeri Profesyonel E-Ticaret Sistemi</p>
            </div>
            <div class="install-body">
                <div class="step-indicator">
                    <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
                    <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
                    <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</div>
                    <div class="step <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</div>
                    <div class="step <?= $step >= 5 ? 'active' : '' ?>">5</div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <h4 class="mb-4">Sistem Gereksinimleri</h4>
                    <?php
                    $requirements = [
                        'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                        'PDO MySQL' => extension_loaded('pdo_mysql'),
                        'JSON' => extension_loaded('json'),
                        'GD' => extension_loaded('gd'),
                        'mbstring' => extension_loaded('mbstring'),
                        'includes/ Yazılabilir' => is_writable(__DIR__ . '/includes'),
                    ];
                    $allPassed = true;
                    foreach ($requirements as $name => $passed):
                        if (!$passed) $allPassed = false;
                    ?>
                        <div class="requirement-item <?= $passed ? 'success' : 'error' ?>">
                            <span><?= $name ?></span>
                            <i class="fas fa-<?= $passed ? 'check text-success' : 'times text-danger' ?>"></i>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($allPassed): ?>
                        <a href="?step=2" class="btn btn-install btn-primary w-100 mt-4">Devam Et <i class="fas fa-arrow-right"></i></a>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">Lütfen eksik gereksinimleri tamamlayın.</div>
                    <?php endif; ?>

                <?php elseif ($step === 2): ?>
                    <h4 class="mb-4">Veritabanı Ayarları</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Sunucu</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Veritabanı Adı</label>
                            <input type="text" name="db_name" class="form-control" value="eticaret" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı</label>
                            <input type="text" name="db_user" class="form-control" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="db_pass" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-install btn-primary w-100">Devam Et <i class="fas fa-arrow-right"></i></button>
                    </form>

                <?php elseif ($step === 3): ?>
                    <h4 class="mb-4">Veritabanı Kurulumu</h4>
                    <div class="text-center py-4">
                        <i class="fas fa-database fa-3x text-primary mb-3"></i>
                        <p>Veritabanı tabloları otomatik olarak oluşturulacak.</p>

                        <div class="alert alert-info text-start mb-4">
                            <strong>Oluşturulacak Tablolar:</strong>
                            <ul class="mb-0 mt-2" style="font-size: 0.9rem;">
                                <li>Ürün ve Kategori Tabloları (products, categories, product_images)</li>
                                <li>Sipariş Tabloları (orders, order_items)</li>
                                <li>Müşteri Tabloları (customers, customer_addresses, wishlist)</li>
                                <li>İçerik Tabloları (layouts, sliders, banners, menus, pages)</li>
                                <li>Pazarlama Tabloları (coupons, reviews, email_campaigns)</li>
                                <li>Kargo & Ödeme Tabloları (shipping_methods, payment_methods)</li>
                                <li>Ayar Tabloları (theme_settings, site_settings)</li>
                                <li>Ve 20+ ek tablo...</li>
                            </ul>
                        </div>

                        <form method="POST" id="dbForm">
                            <button type="submit" class="btn btn-install btn-primary">
                                <i class="fas fa-cog fa-spin"></i> Veritabanını Oluştur
                            </button>
                        </form>

                        <script>
                        document.getElementById('dbForm').addEventListener('submit', function() {
                            this.querySelector('button').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Oluşturuluyor...';
                            this.querySelector('button').disabled = true;
                        });
                        </script>
                    </div>

                <?php elseif ($step === 4): ?>
                    <h4 class="mb-4">Yönetici Hesabı</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Site Adı</label>
                            <input type="text" name="site_name" class="form-control" value="E-Ticaret Sitem" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Kullanıcı Adı</label>
                            <input type="text" name="admin_name" class="form-control" value="admin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin E-posta</label>
                            <input type="email" name="admin_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="admin_pass" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" name="admin_pass2" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-install btn-primary w-100">Kurulumu Tamamla <i class="fas fa-check"></i></button>
                    </form>

                <?php elseif ($step === 5): ?>
                    <div class="text-center">
                        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                        <h3 class="text-success">Kurulum Tamamlandı!</h3>
                        <p class="text-muted mb-4">E-Ticaret sisteminiz başarıyla kuruldu.</p>
                        <div class="row g-3">
                            <div class="col-6"><a href="/" class="btn btn-outline-primary w-100"><i class="fas fa-home"></i> Siteyi Gör</a></div>
                            <div class="col-6"><a href="/admin/" class="btn btn-primary w-100"><i class="fas fa-cog"></i> Admin Panel</a></div>
                        </div>
                        <div class="alert alert-warning mt-4 text-start">
                            <strong>Güvenlik:</strong> Kurulum sonrası <code>install.php</code> dosyasını silin.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
