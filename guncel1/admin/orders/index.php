<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Sipariş Yönetimi';

$page = max(1, (int)get('page', 1));
$status = get('status', '');

$where = '1=1';
$params = [];
if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$total = dbCount('orders', $where, $params);
$pagination = paginate($total, $page, ADMIN_ITEMS_PER_PAGE);

$orders = dbQuery("
    SELECT o.*, c.first_name, c.last_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE $where
    ORDER BY o.created_at DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
", $params);

require_once '../includes/header.php';
?>

<h2><i class="fas fa-shopping-cart"></i> Sipariş Yönetimi</h2>
<?php displayFlash('order'); ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" <?php echo $status=='pending'?'selected':''; ?>>Bekliyor</option>
                    <option value="processing" <?php echo $status=='processing'?'selected':''; ?>>İşleniyor</option>
                    <option value="shipped" <?php echo $status=='shipped'?'selected':''; ?>>Kargoya Verildi</option>
                    <option value="delivered" <?php echo $status=='delivered'?'selected':''; ?>>Teslim Edildi</option>
                    <option value="cancelled" <?php echo $status=='cancelled'?'selected':''; ?>>İptal</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filtrele</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Sipariş No</th><th>Müşteri</th><th>Tutar</th><th>Ödeme</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-4">Sipariş bulunamadı</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars(($order['first_name'] ?? 'Misafir') . ' ' . ($order['last_name'] ?? '')); ?></td>
                            <td><?php echo formatPrice($order['total']); ?></td>
                            <td>
                                <?php
                                $paymentBadges = ['unpaid'=>'warning','paid'=>'success','refunded'=>'info','failed'=>'danger'];
                                $paymentLabels = ['unpaid'=>'Ödenmedi','paid'=>'Ödendi','refunded'=>'İade','failed'=>'Başarısız'];
                                echo '<span class="badge bg-'.($paymentBadges[$order['payment_status']]??'secondary').'">'.($paymentLabels[$order['payment_status']]??$order['payment_status']).'</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $badges = ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                                $labels = ['pending'=>'Bekliyor','processing'=>'İşleniyor','shipped'=>'Kargoda','delivered'=>'Teslim','cancelled'=>'İptal'];
                                echo '<span class="badge bg-'.($badges[$order['status']]??'secondary').'">'.($labels[$order['status']]??$order['status']).'</span>';
                                ?>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer"><?php echo displayPagination($pagination, 'index.php'); ?></div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
