<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Raporlar ve İstatistikler';

// Date range filter
$startDate = get('start_date', date('Y-m-01'));
$endDate = get('end_date', date('Y-m-d'));

// Sales Statistics
$salesStats = dbQueryOne("
    SELECT
        COUNT(*) as total_orders,
        SUM(total) as total_revenue,
        AVG(total) as avg_order_value,
        SUM(CASE WHEN status = 'completed' OR status = 'delivered' THEN total ELSE 0 END) as completed_revenue,
        SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END) as cancelled_revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

// Top Products
$topProducts = dbQuery("
    SELECT
        p.name,
        p.main_image,
        p.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total) as revenue
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 10
", [$startDate, $endDate]);

// Order Status Distribution
$orderStatuses = dbQuery("
    SELECT
        status,
        COUNT(*) as count,
        SUM(total) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
", [$startDate, $endDate]);

// Customer Statistics
$customerStats = dbQueryOne("
    SELECT
        COUNT(DISTINCT customer_id) as total_customers,
        COUNT(*) as total_orders,
        AVG(order_count) as avg_orders_per_customer
    FROM (
        SELECT customer_id, COUNT(*) as order_count
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY customer_id
    ) as customer_orders
", [$startDate, $endDate]);

// Daily Sales Chart Data
$dailySales = dbQuery("
    SELECT
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
", [$startDate, $endDate]);

// Category Performance
$categoryPerformance = dbQuery("
    SELECT
        c.name,
        COUNT(oi.id) as products_sold,
        SUM(oi.total) as revenue
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 10
", [$startDate, $endDate]);

// Low Stock Products
$lowStockProducts = dbQuery("
    SELECT id, name, sku, stock, low_stock_threshold
    FROM products
    WHERE stock <= low_stock_threshold AND status = 1
    ORDER BY stock ASC
    LIMIT 10
");

require_once '../includes/header.php';
?>

<style>
.stat-card {
    transition: all 0.3s;
    border-left: 4px solid;
}
.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.stat-card.primary { border-left-color: #667eea; }
.stat-card.success { border-left-color: #28a745; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.danger { border-left-color: #dc3545; }
.stat-card.info { border-left-color: #17a2b8; }

.chart-container {
    position: relative;
    height: 300px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-chart-line"></i> Raporlar ve İstatistikler</h2>
        <p class="text-muted mb-0">İşletmenizin performansını analiz edin</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="start_date" class="form-control"
                    value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="end_date" class="form-control"
                    value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrele
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Sıfırla
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-shopping-cart fa-3x text-primary me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">Toplam Sipariş</h6>
                        <h3 class="mb-0"><?php echo number_format($salesStats['total_orders'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lira-sign fa-3x text-success me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">Toplam Gelir</h6>
                        <h3 class="mb-0"><?php echo formatPrice($salesStats['total_revenue'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-chart-bar fa-3x text-warning me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">Ort. Sipariş</h6>
                        <h3 class="mb-0"><?php echo formatPrice($salesStats['avg_order_value'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-users fa-3x text-info me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">Toplam Müşteri</h6>
                        <h3 class="mb-0"><?php echo number_format($customerStats['total_customers'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Products -->
    <div class="col-lg-6">
        <div class="card stat-card primary h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-trophy text-warning"></i> En Çok Satan Ürünler</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Henüz satış yok</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ürün</th>
                                    <th>Satış</th>
                                    <th>Gelir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($product['main_image']): ?>
                                                    <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>"
                                                        alt="" style="width: 40px; height: 40px; object-fit: cover;" class="rounded me-2">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo number_format($product['total_sold']); ?></span></td>
                                        <td><strong class="text-success"><?php echo formatPrice($product['revenue']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="col-lg-6">
        <div class="card stat-card success h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Sipariş Durumları</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orderStatuses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Henüz sipariş yok</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orderStatuses as $status): ?>
                        <?php
                        $total = array_sum(array_column($orderStatuses, 'count'));
                        $percentage = ($status['count'] / $total) * 100;
                        $statusLabels = [
                            'pending' => ['Beklemede', 'warning'],
                            'processing' => ['İşleniyor', 'info'],
                            'shipped' => ['Kargoda', 'primary'],
                            'delivered' => ['Teslim Edildi', 'success'],
                            'cancelled' => ['İptal', 'danger'],
                            'refunded' => ['İade', 'secondary']
                        ];
                        $label = $statusLabels[$status['status']] ?? [$status['status'], 'secondary'];
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $label[0]; ?>: <strong><?php echo $status['count']; ?></strong></span>
                                <span><?php echo formatPrice($status['revenue']); ?></span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-<?php echo $label[1]; ?>" role="progressbar"
                                    style="width: <?php echo $percentage; ?>%">
                                    <?php echo number_format($percentage, 1); ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Category Performance -->
    <div class="col-lg-6">
        <div class="card stat-card warning h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-folder-open"></i> Kategori Performansı</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categoryPerformance)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Veri yok</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th>Satış</th>
                                    <th>Gelir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryPerformance as $cat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td><span class="badge bg-info"><?php echo number_format($cat['products_sold']); ?></span></td>
                                        <td><strong class="text-success"><?php echo formatPrice($cat['revenue']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="col-lg-6">
        <div class="card stat-card danger h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Düşük Stoklar</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Tüm stoklar yeterli</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ürün</th>
                                    <th>SKU</th>
                                    <th>Stok</th>
                                    <th>Eşik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo siteUrl('admin/products/edit.php?id=' . $product['id']); ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                        </td>
                                        <td><?php echo $product['low_stock_threshold']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Daily Sales Chart -->
<?php if (!empty($dailySales)): ?>
<div class="card stat-card info mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-chart-area"></i> Günlük Satış Grafiği</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('salesChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($dailySales, 'date')); ?>,
        datasets: [{
            label: 'Gelir (₺)',
            data: <?php echo json_encode(array_column($dailySales, 'revenue')); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Sipariş Sayısı',
            data: <?php echo json_encode(array_column($dailySales, 'orders')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            },
        }
    }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
