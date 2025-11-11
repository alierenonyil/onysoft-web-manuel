<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = getSetting('site_name') . ' - ' . getSetting('site_description');

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
