<?php
/**
 * Modern E-Ticaret Ana Sayfa - OpenCart Benzeri Dinamik Yapı
 */

// Check if database is installed
try {
    if (!file_exists(__DIR__ . '/includes/config.php')) {
        header('Location: /install.php');
        exit;
    }

    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/functions.php';
    require_once 'includes/security.php';

    // Check if tables exist
    $result = dbQueryOne("SELECT COUNT(*) as cnt FROM site_settings", []);
    if (!$result || $result['cnt'] == 0) {
        // Kurulum yapılmamış, install sayfasına yönlendir
        header('Location: /install.php');
        exit;
    }

} catch (Exception $e) {
    // Hata durumunda log kaydı
    error_log("Index.php Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Geliştirme modunda hatayı göster
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Hata: " . $e->getMessage() . "<br>Dosya: " . $e->getFile() . "<br>Satır: " . $e->getLine());
    }

    // Production'da install sayfasına yönlendir
    header('Location: /install.php');
    exit;
}

$pageTitle = getSetting('site_name', 'E-Ticaret') . ' - Ana Sayfa';

// Get active slider with items
$slider = dbQueryOne("SELECT * FROM sliders WHERE status = 1 AND type = 'main' ORDER BY id ASC LIMIT 1");
$sliderItems = [];
if ($slider) {
    $now = date('Y-m-d H:i:s');
    $sliderItems = dbQuery("
        SELECT * FROM slider_items
        WHERE slider_id = ? AND status = 1
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY sort_order ASC
    ", [$slider['id'], $now, $now]);
}

// Get featured products
$featuredProducts = dbQuery("
    SELECT p.*, c.name as category_name, p.main_image as image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.is_featured = 1
    ORDER BY p.sort_order ASC LIMIT 8
");

// Get new products
$newProducts = dbQuery("
    SELECT p.*, c.name as category_name, p.main_image as image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.is_new = 1
    ORDER BY p.created_at DESC LIMIT 8
");

// Get bestseller products (most ordered)
$bestsellerProducts = dbQuery("
    SELECT p.*, c.name as category_name, p.main_image as image, COALESCE(SUM(oi.quantity), 0) as sold
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.status = 1
    GROUP BY p.id
    ORDER BY sold DESC LIMIT 8
");

// Get special offers (discounted products)
$specialProducts = dbQuery("
    SELECT p.*, c.name as category_name, p.main_image as image,
    ROUND(((p.price - p.sale_price) / p.price) * 100) as discount_percent
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.sale_price > 0 AND p.sale_price < p.price
    ORDER BY discount_percent DESC LIMIT 8
");

// Get categories
$categories = dbQuery("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
    WHERE c.parent_id = 0 AND c.status = 1
    GROUP BY c.id
    ORDER BY c.sort_order ASC LIMIT 8
");

// Get banners
$homeBanners = dbQuery("
    SELECT bi.* FROM banner_items bi
    JOIN banners b ON bi.banner_id = b.id
    WHERE b.position = 'home_middle' AND bi.status = 1
    AND (bi.start_date IS NULL OR bi.start_date <= NOW())
    AND (bi.end_date IS NULL OR bi.end_date >= NOW())
    ORDER BY bi.sort_order ASC LIMIT 3
");

// Theme settings
$themeSettings = [];
$themeResult = dbQuery("SELECT setting_key, setting_value FROM theme_settings");
foreach ($themeResult as $row) {
    $themeSettings[$row['setting_key']] = $row['setting_value'];
}

$primaryColor = $themeSettings['primary_color'] ?? '#2563eb';
$secondaryColor = $themeSettings['secondary_color'] ?? '#7c3aed';
$accentColor = $themeSettings['accent_color'] ?? '#f59e0b';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars(getSetting('site_description', 'Profesyonel E-Ticaret Sitesi')) ?>">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: <?= $primaryColor ?>;
            --secondary: <?= $secondaryColor ?>;
            --accent: <?= $accentColor ?>;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        /* Header */
        .top-bar {
            background: var(--primary);
            color: white;
            padding: 8px 0;
            font-size: 13px;
        }

        .main-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .search-box {
            max-width: 500px;
        }

        .search-box input {
            border-radius: 25px 0 0 25px;
            border: 2px solid #e5e7eb;
            padding: 10px 20px;
        }

        .search-box button {
            border-radius: 0 25px 25px 0;
            background: var(--primary);
            border: 2px solid var(--primary);
            padding: 10px 20px;
        }

        .header-icons a {
            color: #4b5563;
            font-size: 1.2rem;
            margin-left: 20px;
            position: relative;
        }

        .header-icons a:hover {
            color: var(--primary);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Slider */
        .hero-slider {
            position: relative;
            overflow: hidden;
        }

        .hero-slide {
            position: relative;
            height: 500px;
        }

        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-content {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            padding: 40px;
            max-width: 600px;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 25px;
        }

        /* Section */
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
        }

        /* Product Card */
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #e5e7eb;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .product-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .badge-new {
            background: var(--primary);
        }

        .badge-sale {
            background: #ef4444;
        }

        .badge-featured {
            background: var(--accent);
        }

        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .product-actions button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .product-actions button:hover {
            background: var(--primary);
            color: white;
        }

        .product-body {
            padding: 15px;
        }

        .product-category {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 14px;
            font-weight: 600;
            margin: 8px 0;
            color: #1f2937;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-rating {
            color: #fbbf24;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .current-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .old-price {
            font-size: 0.9rem;
            color: #9ca3af;
            text-decoration: line-through;
        }

        .btn-add-cart {
            width: 100%;
            margin-top: 10px;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-add-cart:hover {
            background: var(--secondary);
            transform: scale(1.02);
        }

        /* Category Card */
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
            height: 100%;
        }

        .category-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .category-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        /* Banner */
        .promo-banner {
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .promo-banner img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        /* Features */
        .feature-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            height: 100%;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary);
            font-size: 1.5rem;
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 60px 0 30px;
            margin-top: 60px;
        }

        .footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: white;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .newsletter-form input {
            border-radius: 8px 0 0 8px;
        }

        .newsletter-form button {
            border-radius: 0 8px 8px 0;
        }

        @media (max-width: 768px) {
            .hero-slide {
                height: 300px;
            }
            .hero-content h1 {
                font-size: 1.8rem;
            }
            .hero-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <i class="fas fa-phone-alt me-2"></i> <?= htmlspecialchars(getSetting('site_phone', '0850 123 45 67')) ?>
                <span class="mx-3">|</span>
                <i class="fas fa-envelope me-2"></i> <?= htmlspecialchars(getSetting('site_email', 'info@eticaret.com')) ?>
            </div>
            <div class="col-md-6 text-end">
                <i class="fas fa-truck me-2"></i> 500 TL Üzeri Ücretsiz Kargo
            </div>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-3">
                <a href="/" class="logo">
                    <?php if (!empty($themeSettings['logo'])): ?>
                        <img src="/uploads/theme/<?= htmlspecialchars($themeSettings['logo']) ?>" alt="Logo" style="max-height: 50px;">
                    <?php else: ?>
                        <i class="fas fa-shopping-cart"></i> <?= htmlspecialchars(getSetting('site_name', 'E-Ticaret')) ?>
                    <?php endif; ?>
                </a>
            </div>
            <div class="col-md-6">
                <form action="/search.php" method="GET" class="search-box d-flex mx-auto">
                    <input type="text" name="q" class="form-control" placeholder="Ürün ara...">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="col-md-3">
                <div class="header-icons text-end">
                    <?php if (isLoggedIn()): ?>
                        <a href="/account.php" title="Hesabım"><i class="fas fa-user"></i></a>
                    <?php else: ?>
                        <a href="/login.php" title="Giriş Yap"><i class="fas fa-user"></i></a>
                    <?php endif; ?>
                    <a href="/wishlist.php" title="İstek Listesi"><i class="fas fa-heart"></i></a>
                    <a href="/cart.php" title="Sepet">
                        <i class="fas fa-shopping-cart"></i>
                        <?php $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Hero Slider -->
<?php if (!empty($sliderItems)): ?>
<section class="hero-slider">
    <div class="swiper mainSwiper">
        <div class="swiper-wrapper">
            <?php foreach ($sliderItems as $item): ?>
                <div class="swiper-slide">
                    <div class="hero-slide">
                        <img src="/uploads/sliders/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <?php if ($item['title'] || $item['subtitle']): ?>
                            <div class="hero-content" style="<?= $item['text_position'] === 'right' ? 'right: 0;' : ($item['text_position'] === 'center' ? 'left: 50%; transform: translate(-50%, -50%); text-align: center;' : '') ?>; color: <?= $item['text_color'] ?>;">
                                <?php if ($item['title']): ?>
                                    <h1><?= htmlspecialchars($item['title']) ?></h1>
                                <?php endif; ?>
                                <?php if ($item['subtitle']): ?>
                                    <p><?= htmlspecialchars($item['subtitle']) ?></p>
                                <?php endif; ?>
                                <?php if ($item['button_text'] && $item['button_url']): ?>
                                    <a href="<?= htmlspecialchars($item['button_url']) ?>" class="btn btn-<?= htmlspecialchars($item['button_style']) ?> btn-lg">
                                        <?= htmlspecialchars($item['button_text']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($slider && !empty($slider['show_dots'])): ?>
            <div class="swiper-pagination"></div>
        <?php endif; ?>
        <?php if ($slider && !empty($slider['show_arrows'])): ?>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<div class="container py-5">

    <!-- Features -->
    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <h6>Ücretsiz Kargo</h6>
                    <small class="text-muted">500 TL üzeri siparişlerde</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h6>Güvenli Ödeme</h6>
                    <small class="text-muted">256-bit SSL güvenlik</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-undo"></i></div>
                    <h6>Kolay İade</h6>
                    <small class="text-muted">14 gün içinde iade</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h6>7/24 Destek</h6>
                    <small class="text-muted">Her zaman yanınızdayız</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <?php if (!empty($categories)): ?>
    <section class="mb-5">
        <h2 class="section-title">Kategoriler</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="/category.php?slug=<?= htmlspecialchars($category['slug']) ?>" class="text-decoration-none">
                        <div class="category-card">
                            <div class="category-icon">
                                <?php if ($category['image']): ?>
                                    <img src="/uploads/categories/<?= htmlspecialchars($category['image']) ?>" alt="" style="width: 40px;">
                                <?php else: ?>
                                    <i class="fas fa-folder"></i>
                                <?php endif; ?>
                            </div>
                            <h6 class="mb-1"><?= htmlspecialchars($category['name']) ?></h6>
                            <small class="text-muted"><?= $category['product_count'] ?> Ürün</small>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Öne Çıkan Ürünler</h2>
            <a href="/category.php?filter=featured" class="btn btn-outline-primary btn-sm">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/product.php?slug=<?= htmlspecialchars($product['slug']) ?>">
                                <img src="/uploads/products/<?= htmlspecialchars($product['image'] ?: 'no-image.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            </a>
                            <div class="product-badges">
                                <?php if ($product['is_new']): ?><span class="badge badge-new">Yeni</span><?php endif; ?>
                                <?php if ($product['is_featured']): ?><span class="badge badge-featured">Öne Çıkan</span><?php endif; ?>
                                <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                                    <span class="badge badge-sale">-%<?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <button onclick="addToWishlist(<?= $product['id'] ?>)" title="İstek Listesine Ekle"><i class="fas fa-heart"></i></button>
                                <button onclick="quickView(<?= $product['id'] ?>)" title="Hızlı Bakış"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Genel') ?></div>
                            <h5 class="product-title">
                                <a href="/product.php?slug=<?= htmlspecialchars($product['slug']) ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h5>
                            <div class="product-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?= $i <= ($product['rating'] ?? 0) ? 's' : 'r' ?> fa-star"></i>
                                <?php endfor; ?>
                                <span class="text-muted ms-1">(<?= $product['review_count'] ?? 0 ?>)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price"><?= formatPrice($product['sale_price'] > 0 ? $product['sale_price'] : $product['price']) ?></span>
                                <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                                    <span class="old-price"><?= formatPrice($product['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-primary btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fas fa-cart-plus me-2"></i> Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Banners -->
    <?php if (!empty($homeBanners)): ?>
    <section class="mb-5">
        <div class="row g-4">
            <?php foreach ($homeBanners as $banner): ?>
                <div class="col-md-4">
                    <a href="<?= htmlspecialchars($banner['link'] ?? '#') ?>" class="promo-banner d-block">
                        <img src="/uploads/banners/<?= htmlspecialchars($banner['image']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- New Products -->
    <?php if (!empty($newProducts)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Yeni Ürünler</h2>
            <a href="/category.php?filter=new" class="btn btn-outline-primary btn-sm">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($newProducts as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/product.php?slug=<?= htmlspecialchars($product['slug']) ?>">
                                <img src="/uploads/products/<?= htmlspecialchars($product['image'] ?: 'no-image.jpg') ?>" alt="">
                            </a>
                            <div class="product-badges">
                                <span class="badge badge-new">Yeni</span>
                            </div>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? '') ?></div>
                            <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <div class="product-price">
                                <span class="current-price"><?= formatPrice($product['sale_price'] > 0 ? $product['sale_price'] : $product['price']) ?></span>
                            </div>
                            <button class="btn btn-primary btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fas fa-cart-plus me-2"></i> Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Special Offers -->
    <?php if (!empty($specialProducts)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">İndirimli Ürünler</h2>
            <a href="/category.php?filter=sale" class="btn btn-outline-danger btn-sm">Tümünü Gör <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($specialProducts, 0, 4) as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/product.php?slug=<?= htmlspecialchars($product['slug']) ?>">
                                <img src="/uploads/products/<?= htmlspecialchars($product['image'] ?: 'no-image.jpg') ?>" alt="">
                            </a>
                            <div class="product-badges">
                                <span class="badge badge-sale">-%<?= $product['discount_percent'] ?></span>
                            </div>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? '') ?></div>
                            <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <div class="product-price">
                                <span class="current-price"><?= formatPrice($product['sale_price']) ?></span>
                                <span class="old-price"><?= formatPrice($product['price']) ?></span>
                            </div>
                            <button class="btn btn-primary btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fas fa-cart-plus me-2"></i> Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><?= htmlspecialchars(getSetting('site_name', 'E-Ticaret')) ?></h5>
                <p><?= htmlspecialchars(getSetting('site_description', 'Profesyonel E-Ticaret Sistemi')) ?></p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h5>Hesabım</h5>
                <ul class="footer-links">
                    <li><a href="/account.php">Hesabım</a></li>
                    <li><a href="/account.php?tab=orders">Siparişlerim</a></li>
                    <li><a href="/wishlist.php">İstek Listesi</a></li>
                    <li><a href="/cart.php">Sepetim</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h5>Bilgi</h5>
                <ul class="footer-links">
                    <li><a href="/page.php?slug=hakkimizda">Hakkımızda</a></li>
                    <li><a href="/contact.php">İletişim</a></li>
                    <li><a href="/page.php?slug=sss">S.S.S</a></li>
                    <li><a href="/page.php?slug=gizlilik">Gizlilik</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Bülten</h5>
                <p>Kampanyalardan haberdar olun!</p>
                <form class="newsletter-form d-flex" action="/newsletter.php" method="POST">
                    <input type="email" name="email" class="form-control" placeholder="E-posta adresiniz" required>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </form>
            </div>
        </div>
        <hr class="my-4" style="border-color: #374151;">
        <div class="text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(getSetting('site_name', 'E-Ticaret')) ?>. Tüm hakları saklıdır.</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
// Initialize Swiper
const swiper = new Swiper('.mainSwiper', {
    loop: true,
    autoplay: {
        delay: <?= $slider['autoplay_speed'] ?? 5000 ?>,
        disableOnInteraction: false,
    },
    effect: '<?= $slider['animation'] ?? 'slide' ?>',
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});

// Add to Cart
function addToCart(productId) {
    fetch('/cart-add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Ürün sepete eklendi!');
            location.reload();
        } else {
            alert(data.message || 'Hata oluştu');
        }
    });
}

// Add to Wishlist
function addToWishlist(productId) {
    fetch('/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
    });
}
</script>
</body>
</html>
