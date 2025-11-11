<?php
/**
 * Installation Script
 * StarAvcisi E-Commerce System
 */

// Only allow installation if not already installed
if (file_exists('includes/config.php') && !isset($_GET['force'])) {
    $checkInstall = @file_get_contents('includes/config.php');
    if (strpos($checkInstall, 'staravcisi.com') !== false) {
        die('Sistem zaten kurulu görünüyor. Tekrar kurmak için ?force=1 parametresini kullanın.');
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = '';

// Database configuration from your requirements
$dbConfig = [
    'host' => 'staravcisi.com',
    'name' => 'wawahousesql',
    'user' => 'wawahousekullanici',
    'pass' => 'guvenli_sifre'
];

// Step 1: Requirements Check
if ($step === 1) {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'JSON Extension' => extension_loaded('json'),
        'uploads/ writable' => is_writable(__DIR__ . '/uploads') || @mkdir(__DIR__ . '/uploads', 0755, true),
    ];

    $allPassed = !in_array(false, $requirements, true);
}

// Step 2: Database Installation
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "mysql:host={$dbConfig['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbConfig['name']}`");

        // Read and execute SQL file
        $sql = file_get_contents(__DIR__ . '/database.sql');

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }

        $success = 'Veritabanı başarıyla kuruldu!';

    } catch (PDOException $e) {
        $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Step 3: Configuration
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteUrl = rtrim($_POST['site_url'], '/');
    $siteName = $_POST['site_name'];
    $adminEmail = $_POST['admin_email'];

    // Create .htaccess for clean URLs (optional)
    $htaccess = <<<HTACCESS
# Apache Configuration
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to HTTPS (optional, uncomment if you have SSL)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Remove www (optional)
    # RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
    # RewriteRule ^(.*)$ http://%1/$1 [R=301,L]
</IfModule>

# Security
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect config files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
HTACCESS;

    file_put_contents(__DIR__ . '/.htaccess', $htaccess);

    $success = 'Kurulum tamamlandı! Şimdi admin paneline giriş yapabilirsiniz.';
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - StarAvcısı E-Ticaret</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0; }
        .install-card { max-width: 700px; margin: 0 auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .step { flex: 1; text-align: center; padding: 1rem; background: white; margin: 0 0.5rem; border-radius: 5px; }
        .step.active { background: #667eea; color: white; }
        .step.completed { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <div class="text-center mb-4">
                <h1 class="text-white"><i class="fas fa-shopping-cart"></i> StarAvcısı E-Ticaret</h1>
                <p class="text-white">Kurulum Sihirbazı</p>
            </div>

            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Gereksinimler
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i> Veritabanı
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Yapılandırma
                </div>
            </div>

            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <?php if ($step === 1): ?>
                        <h4>Gereksinim Kontrolü</h4>
                        <p>Sistemin çalışması için gerekli PHP eklentileri kontrol ediliyor...</p>

                        <table class="table">
                            <?php foreach ($requirements as $name => $passed): ?>
                                <tr>
                                    <td><?php echo $name; ?></td>
                                    <td class="text-end">
                                        <?php if ($passed): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i> TAMAM</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-times"></i> EKSİK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>

                        <?php if ($allPassed): ?>
                            <div class="alert alert-success">Tüm gereksinimler karşılanıyor!</div>
                            <a href="?step=2" class="btn btn-primary w-100">Sonraki Adım <i class="fas fa-arrow-right"></i></a>
                        <?php else: ?>
                            <div class="alert alert-danger">Lütfen eksik gereksinimleri tamamlayın.</div>
                        <?php endif; ?>

                    <?php elseif ($step === 2): ?>
                        <h4>Veritabanı Kurulumu</h4>
                        <p>Veritabanı bağlantı bilgileriniz:</p>

                        <table class="table table-sm">
                            <tr><th>Host:</th><td><?php echo htmlspecialchars($dbConfig['host']); ?></td></tr>
                            <tr><th>Database:</th><td><?php echo htmlspecialchars($dbConfig['name']); ?></td></tr>
                            <tr><th>User:</th><td><?php echo htmlspecialchars($dbConfig['user']); ?></td></tr>
                        </table>

                        <form method="POST">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Veritabanı tabloları oluşturulacak ve örnek veriler eklenecek.
                            </div>

                            <?php if (!$success): ?>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-database"></i> Veritabanını Kur
                                </button>
                            <?php else: ?>
                                <a href="?step=3" class="btn btn-primary w-100">Sonraki Adım <i class="fas fa-arrow-right"></i></a>
                            <?php endif; ?>
                        </form>

                    <?php elseif ($step === 3): ?>
                        <h4>Site Yapılandırması</h4>

                        <?php if (!$success): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Site URL</label>
                                    <input type="url" name="site_url" class="form-control" value="http://staravcisi.com" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Site Adı</label>
                                    <input type="text" name="site_name" class="form-control" value="StarAvcısı E-Ticaret" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Admin Email</label>
                                    <input type="email" name="admin_email" class="form-control" value="admin@staravcisi.com" required>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check"></i> Kurulumu Tamamla
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Kurulum Başarıyla Tamamlandı!</h5>
                                <p>Sistemizi kullanmaya başlayabilirsiniz.</p>
                            </div>

                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6>Admin Giriş Bilgileri:</h6>
                                    <p class="mb-0">
                                        <strong>Kullanıcı Adı:</strong> admin<br>
                                        <strong>Şifre:</strong> admin123
                                    </p>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="admin/login.php" class="btn btn-primary">
                                    <i class="fas fa-user-shield"></i> Admin Paneline Git
                                </a>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-home"></i> Ana Sayfaya Git
                                </a>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i>
                                Güvenlik için <strong>install.php</strong> dosyasını silin veya yeniden adlandırın!
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-3">
                <p class="text-white small">© 2025 StarAvcısı E-Ticaret Sistemi</p>
            </div>
        </div>
    </div>
</body>
</html>
