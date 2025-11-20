<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$slug = get('slug');
$category = dbQueryOne("SELECT * FROM categories WHERE slug = ? AND status = 1", [$slug]);

if (!$category) {
    header("HTTP/1.0 404 Not Found");
    die('Kategori bulunamadı');
}

$page = max(1, (int)get('page', 1));
$sort = get('sort', 'newest');

$sortOptions = [
    'newest' => 'created_at DESC',
    'price_low' => 'price ASC',
    'price_high' => 'price DESC',
    'name' => 'name ASC'
];

$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

$total = dbCount('products', 'category_id = ? AND status = 1', [$category['id']]);
$pagination = paginate($total, $page, ITEMS_PER_PAGE);

$products = dbQuery("
    SELECT * FROM products
    WHERE category_id = ? AND status = 1
    ORDER BY $orderBy
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
", [$category['id']]);

$pageTitle = $category['meta_title'] ?? $category['name'];
$pageDescription = $category['meta_description'] ?? $category['description'];

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <?php if ($category['description']): ?>
                <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
            <form method="GET" class="d-inline-block">
                <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort=='newest'?'selected':''; ?>>En Yeni</option>
                    <option value="price_low" <?php echo $sort=='price_low'?'selected':''; ?>>Fiyat (Düşük-Yüksek)</option>
                    <option value="price_high" <?php echo $sort=='price_high'?'selected':''; ?>>Fiyat (Yüksek-Düşük)</option>
                    <option value="name" <?php echo $sort=='name'?'selected':''; ?>>İsme Göre</option>
                </select>
            </form>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">Bu kategoride henüz ürün bulunmuyor.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <?php include 'includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-4">
                <?php echo displayPagination($pagination, 'category.php?slug=' . $slug); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
