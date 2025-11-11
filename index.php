<?php
/**
 * Temporary Installation Redirect
 * This file will redirect to installation page if database is not set up
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if database is installed
try {
    $host = 'staravcisi.com';
    $dbname = 'wawahousesql';
    $user = 'wawahousekullanici';
    $pass = '14531453aO.!';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Check if tables exist
    $result = $pdo->query("SHOW TABLES LIKE 'site_settings'")->fetch();

    if (!$result) {
        // Database not installed, redirect to installation
        header('Location: /install.php');
        exit;
    }

    // Database exists, load the real index page
    define('FRONTEND_PAGE', true);
    require_once 'includes/config.php';

    $pageTitle = getSetting('site_name', 'StarAvcısı E-Ticaret') . ' - ' . getSetting('site_description', 'Modern E-Ticaret');

    // Get active sliders
    $sliders = dbQuery("SELECT * FROM sliders WHERE status = 1 ORDER BY sort_order ASC LIMIT 5");

    // Get featured products
    $featuredProducts = dbQuery("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 1 AND p.is_featured = 1
        ORDER BY p.sort_order ASC
        LIMIT 8
    ");

    // Get new products
    $newProducts = dbQuery("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 1 AND p.is_new = 1
        ORDER BY p.created_at DESC
        LIMIT 8
    ");

    // Get categories with products
    $categories = dbQuery("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
        WHERE c.parent_id = 0 AND c.status = 1
        GROUP BY c.id
        ORDER BY c.sort_order ASC
        LIMIT 6
    ");

    require_once 'includes/header_frontend.php';

} catch (Exception $e) {
    // Database connection failed or tables don't exist
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kurulum Gerekli - StarAvcısı E-Ticaret</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
            .install-card { max-width: 600px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="install-card">
                <div class="card shadow-lg">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-database" style="font-size: 5rem; color: #667eea;"></i>
                        </div>
                        <h1 class="mb-3">🚀 Kurulum Gerekli</h1>
                        <p class="lead mb-4">Veritabanı henüz kurulmamış. Lütfen kurulum işlemini tamamlayın.</p>

                        <div class="alert alert-danger text-start">
                            <strong>Hata:</strong><br>
                            <small><?php echo htmlspecialchars($e->getMessage()); ?></small>
                        </div>

                        <a href="/install.php" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-play"></i> Kuruluma Başla
                        </a>

                        <hr class="my-4">

                        <div class="text-muted small text-start">
                            <strong>Kurulum Adımları:</strong>
                            <ol class="mt-2">
                                <li>"Kuruluma Başla" butonuna tıklayın</li>
                                <li>Otomatik kurulum tamamlanacak</li>
                                <li>Admin panele giriş yapın</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!-- Slider -->
<?php if (!empty($sliders)): ?>
<div id="mainSlider" class="carousel slide mb-5" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach ($sliders as $index => $slider): ?>
            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($sliders as $index => $slider): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <img src="<?php echo siteUrl('uploads/sliders/' . $slider['image']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($slider['title']); ?>" style="max-height: 500px; object-fit: cover;">
                <div class="carousel-caption">
                    <h2><?php echo htmlspecialchars($slider['title']); ?></h2>
                    <?php if ($slider['subtitle']): ?>
                        <p><?php echo htmlspecialchars($slider['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if ($slider['link_url']): ?>
                        <a href="<?php echo htmlspecialchars($slider['link_url']); ?>" class="btn btn-primary btn-lg">
                            <?php echo htmlspecialchars($slider['link_text'] ?? 'Daha Fazla'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>
<?php endif; ?>

<div class="container">
    <!-- Categories -->
    <?php if (!empty($categories)): ?>
    <section class="mb-5">
        <h2 class="mb-4">Kategoriler</h2>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4 col-lg-2">
                    <a href="<?php echo siteUrl('category.php?slug=' . $category['slug']); ?>" class="text-decoration-none">
                        <div class="card text-center h-100 category-card">
                            <div class="card-body">
                                <?php if ($category['image']): ?>
                                    <img src="<?php echo siteUrl('uploads/categories/' . $category['image']); ?>" class="mb-2" style="width:60px;height:60px;object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-folder fa-3x text-primary mb-2"></i>
                                <?php endif; ?>
                                <h6 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h6>
                                <small class="text-muted"><?php echo $category['product_count']; ?> ürün</small>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="mb-5">
        <h2 class="mb-4">Öne Çıkan Ürünler</h2>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <?php include 'includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- New Products -->
    <?php if (!empty($newProducts)): ?>
    <section class="mb-5">
        <h2 class="mb-4">Yeni Ürünler</h2>
        <div class="row g-4">
            <?php foreach ($newProducts as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <?php include 'includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
