<?php
define('ADMIN_PAGE', true);
require_once '../includes/config.php';

$pageTitle = 'Dashboard';

// Get statistics
$stats = [
    'total_products' => dbCount('products'),
    'total_orders' => dbCount('orders'),
    'total_customers' => dbCount('customers'),
    'pending_orders' => dbCount('orders', "status = 'pending'"),
    'total_revenue' => dbGetValue("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'") ?? 0,
    'today_orders' => dbCount('orders', "DATE(created_at) = CURDATE()"),
];

// Recent orders
$recentOrders = dbQuery("
    SELECT o.*, c.first_name, c.last_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Low stock products
$lowStockProducts = dbQuery("
    SELECT * FROM products
    WHERE stock <= low_stock_threshold AND stock > 0
    ORDER BY stock ASC
    LIMIT 10
");

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Toplam Ürün</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_products']); ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Toplam Sipariş</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Toplam Müşteri</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Toplam Gelir</h6>
                        <h2 class="mb-0"><?php echo formatPrice($stats['total_revenue']); ?></h2>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-warning">
            <div class="card-body">
                <h5 class="card-title text-warning"><i class="fas fa-clock"></i> Bekleyen Siparişler</h5>
                <h3 class="mb-0"><?php echo number_format($stats['pending_orders']); ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-info">
            <div class="card-body">
                <h5 class="card-title text-info"><i class="fas fa-calendar-day"></i> Bugünkü Siparişler</h5>
                <h3 class="mb-0"><?php echo number_format($stats['today_orders']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Orders -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Son Siparişler</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Henüz sipariş bulunmuyor</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td>
                                            <?php
                                            if ($order['first_name']) {
                                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                            } else {
                                                echo '<em>Misafir</em>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo formatPrice($order['total']); ?></td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'pending' => '<span class="badge bg-warning">Bekliyor</span>',
                                                'processing' => '<span class="badge bg-info">İşleniyor</span>',
                                                'shipped' => '<span class="badge bg-primary">Kargoya Verildi</span>',
                                                'delivered' => '<span class="badge bg-success">Teslim Edildi</span>',
                                                'cancelled' => '<span class="badge bg-danger">İptal</span>',
                                                'refunded' => '<span class="badge bg-secondary">İade</span>',
                                            ];
                                            echo $statusLabels[$order['status']] ?? $order['status'];
                                            ?>
                                        </td>
                                        <td><?php echo formatDate($order['created_at']); ?></td>
                                        <td>
                                            <a href="<?php echo siteUrl('admin/orders/view.php?id=' . $order['id']); ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning"></i> Düşük Stok</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="list-group-item text-center text-muted">
                            Düşük stoklu ürün yok
                        </div>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small class="text-danger">Stok: <?php echo $product['stock']; ?></small>
                                    </div>
                                    <a href="<?php echo siteUrl('admin/products/edit.php?id=' . $product['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
