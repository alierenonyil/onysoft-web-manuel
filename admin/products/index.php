<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$pageTitle = 'Ürün Yönetimi';

// Get filters
$search = get('search', '');
$category = get('category', '');
$status = get('status', '');
$page = max(1, (int)get('page', 1));

// Build query
$where = ['1=1'];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR sku LIKE ?)";
    $searchTerm = '%' . dbEscapeLike($search) . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category !== '') {
    $where[] = "category_id = ?";
    $params[] = $category;
}

if ($status !== '') {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count total
$totalProducts = dbCount('products', $whereClause, $params);

// Pagination
$pagination = paginate($totalProducts, $page, ADMIN_ITEMS_PER_PAGE);

// Get products
$products = dbQuery("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $whereClause
    ORDER BY p.created_at DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
", $params);

// Get categories for filter
$categories = dbQuery("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-box"></i> Ürün Yönetimi</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Yeni Ürün Ekle
    </a>
</div>

<?php displayFlash('product'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Ürün adı veya SKU ara..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Pasif</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary me-2">
                    <i class="fas fa-search"></i> Filtrele
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i> Temizle
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">Resim</th>
                        <th>Ürün Adı</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th>Fiyat</th>
                        <th>Stok</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th width="120">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Henüz ürün bulunmuyor
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['main_image']): ?>
                                        <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-thumbnail"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="badge bg-warning ms-1">Öne Çıkan</span>
                                    <?php endif; ?>
                                    <?php if ($product['is_new']): ?>
                                        <span class="badge bg-info ms-1">Yeni</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($product['sale_price']): ?>
                                        <del class="text-muted small"><?php echo formatPrice($product['price']); ?></del><br>
                                        <strong class="text-danger"><?php echo formatPrice($product['sale_price']); ?></strong>
                                    <?php else: ?>
                                        <?php echo formatPrice($product['price']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $stockClass = 'text-success';
                                    if ($product['stock'] == 0) {
                                        $stockClass = 'text-danger';
                                    } elseif ($product['stock'] <= $product['low_stock_threshold']) {
                                        $stockClass = 'text-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $stockClass; ?>">
                                        <strong><?php echo $product['stock']; ?></strong>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['status']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($product['created_at'], 'd.m.Y'); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $product['id']; ?>" class="btn btn-danger delete-confirm" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php echo displayPagination($pagination, 'index.php'); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
