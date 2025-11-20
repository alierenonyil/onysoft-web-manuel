<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$slug = get('slug');
$product = dbQueryOne("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 1
", [$slug]);

if (!$product) {
    header("HTTP/1.0 404 Not Found");
    die('Ürün bulunamadı');
}

// Update views
dbExecute("UPDATE products SET views = views + 1 WHERE id = ?", [$product['id']]);

// Get related products
$relatedProducts = dbQuery("
    SELECT * FROM products
    WHERE category_id = ? AND id != ? AND status = 1
    ORDER BY RAND()
    LIMIT 4
", [$product['category_id'], $product['id']]);

$pageTitle = $product['meta_title'] ?? $product['name'];
$pageDescription = $product['meta_description'] ?? $product['short_description'];
$pageKeywords = $product['meta_keywords'] ?? '';

// Get images
$additionalImages = !empty($product['images']) ? json_decode($product['images'], true) : [];

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo siteUrl(); ?>">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="<?php echo siteUrl('category.php?slug=' . $product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="product-images">
                <div class="main-image mb-3">
                    <?php if ($product['main_image']): ?>
                        <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>" class="img-fluid rounded" id="mainProductImage" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="bg-light text-center p-5">
                            <i class="fas fa-image fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($additionalImages)): ?>
                    <div class="thumbnails">
                        <div class="row g-2">
                            <?php if ($product['main_image']): ?>
                                <div class="col-3">
                                    <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>" class="img-thumbnail thumbnail-image" style="cursor:pointer;">
                                </div>
                            <?php endif; ?>
                            <?php foreach ($additionalImages as $image): ?>
                                <div class="col-3">
                                    <img src="<?php echo siteUrl('uploads/products/' . $image); ?>" class="img-thumbnail thumbnail-image" style="cursor:pointer;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>

            <?php if ($product['short_description']): ?>
                <p class="lead"><?php echo htmlspecialchars($product['short_description']); ?></p>
            <?php endif; ?>

            <div class="product-meta mb-3">
                <p>
                    <strong>Kategori:</strong>
                    <a href="<?php echo siteUrl('category.php?slug=' . $product['category_slug']); ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </p>
                <?php if ($product['sku']): ?>
                    <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
                <?php endif; ?>
                <p><strong>Stok Durumu:</strong>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="text-success">Stokta var (<?php echo $product['stock']; ?> adet)</span>
                    <?php else: ?>
                        <span class="text-danger">Stokta yok</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="product-price mb-4">
                <?php if ($product['sale_price']): ?>
                    <h4 class="text-muted"><del><?php echo formatPrice($product['price']); ?></del></h4>
                    <h2 class="text-danger"><?php echo formatPrice($product['sale_price']); ?></h2>
                    <span class="badge bg-danger">
                        %<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?> İndirim
                    </span>
                <?php else: ?>
                    <h2><?php echo formatPrice($product['price']); ?></h2>
                <?php endif; ?>
            </div>

            <form action="<?php echo siteUrl('cart-add.php'); ?>" method="POST" class="mb-4">
                <?php echo csrfField(); ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Adet</label>
                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" style="max-width:100px;" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-shopping-cart"></i> Sepete Ekle
                </button>
            </form>
        </div>
    </div>

    <!-- Product Description -->
    <?php if ($product['description']): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Ürün Açıklaması</h4>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mt-5">
        <h3 class="mb-4">Benzer Ürünler</h3>
        <div class="row g-4">
            <?php foreach ($relatedProducts as $product): ?>
                <div class="col-md-3">
                    <?php include 'includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Thumbnail image click
document.querySelectorAll('.thumbnail-image').forEach(img => {
    img.addEventListener('click', function() {
        document.getElementById('mainProductImage').src = this.src;
    });
});
</script>

<?php require_once 'includes/footer_frontend.php'; ?>
