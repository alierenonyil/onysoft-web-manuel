<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$query = get('q', '');
$pageTitle = 'Arama Sonuçları: ' . htmlspecialchars($query);

$page = max(1, (int)get('page', 1));

if (!empty($query)) {
    $searchTerm = '%' . dbEscapeLike($query) . '%';
    $where = "(name LIKE ? OR description LIKE ? OR sku LIKE ?) AND status = 1";
    $params = [$searchTerm, $searchTerm, $searchTerm];

    $total = dbCount('products', $where, $params);
    $pagination = paginate($total, $page, ITEMS_PER_PAGE);

    $products = dbQuery("
        SELECT * FROM products
        WHERE $where
        ORDER BY name ASC
        LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
    ", $params);
} else {
    $products = [];
    $total = 0;
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Arama Sonuçları</h1>

    <?php if (!empty($query)): ?>
        <p class="lead">"<?php echo htmlspecialchars($query); ?>" için <?php echo $total; ?> ürün bulundu.</p>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                Aramanızla eşleşen ürün bulunamadı. Lütfen farklı kelimeler deneyin.
            </div>
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
                    <?php echo displayPagination($pagination, 'search.php?q=' . urlencode($query)); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning">Lütfen arama yapmak için bir kelime girin.</div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
