<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Sipariş Detayı';

$orderId = (int)get('id');
$order = dbQueryOne("SELECT o.*, c.first_name, c.last_name, c.email FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?", [$orderId]);
if (!$order) {
    setFlash('order', 'Sipariş bulunamadı', 'error');
    redirect(siteUrl('admin/orders/index.php'));
}

$items = dbQuery("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);

if (isPost() && post('action') == 'update_status') {
    if (verifyCsrfToken(post('csrf_token'))) {
        $newStatus = post('status');
        dbUpdate('orders', ['status' => $newStatus], 'id = ?', [$orderId]);
        setFlash('order', 'Sipariş durumu güncellendi', 'success');
        redirect(siteUrl('admin/orders/view.php?id='.$orderId));
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between mb-4">
    <h2><i class="fas fa-shopping-cart"></i> Sipariş #<?php echo htmlspecialchars($order['order_number']); ?></h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Geri</a>
</div>

<?php displayFlash('order'); ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><h5>Sipariş Ürünleri</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Ürün</th><th>Fiyat</th><th>Adet</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?><?php if($item['variant_name']) echo '<br><small>'.htmlspecialchars($item['variant_name']).'</small>'; ?></td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><th colspan="3">Ara Toplam</th><td><?php echo formatPrice($order['subtotal']); ?></td></tr>
                        <?php if ($order['tax'] > 0): ?><tr><th colspan="3">KDV</th><td><?php echo formatPrice($order['tax']); ?></td></tr><?php endif; ?>
                        <?php if ($order['shipping_cost'] > 0): ?><tr><th colspan="3">Kargo</th><td><?php echo formatPrice($order['shipping_cost']); ?></td></tr><?php endif; ?>
                        <?php if ($order['discount'] > 0): ?><tr><th colspan="3">İndirim</th><td>-<?php echo formatPrice($order['discount']); ?></td></tr><?php endif; ?>
                        <tr class="table-primary"><th colspan="3">TOPLAM</th><th><?php echo formatPrice($order['total']); ?></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h6>Fatura Adresi</h6></div>
                    <div class="card-body">
                        <?php
                        $billing = json_decode($order['billing_address'], true);
                        if ($billing) {
                            echo htmlspecialchars($billing['first_name'] . ' ' . $billing['last_name']) . '<br>';
                            echo htmlspecialchars($billing['address_line1']) . '<br>';
                            if (!empty($billing['address_line2'])) echo htmlspecialchars($billing['address_line2']) . '<br>';
                            echo htmlspecialchars($billing['city'] . ', ' . $billing['postal_code']) . '<br>';
                            echo htmlspecialchars($billing['phone']);
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h6>Teslimat Adresi</h6></div>
                    <div class="card-body">
                        <?php
                        $shipping = json_decode($order['shipping_address'], true);
                        if ($shipping) {
                            echo htmlspecialchars($shipping['first_name'] . ' ' . $shipping['last_name']) . '<br>';
                            echo htmlspecialchars($shipping['address_line1']) . '<br>';
                            if (!empty($shipping['address_line2'])) echo htmlspecialchars($shipping['address_line2']) . '<br>';
                            echo htmlspecialchars($shipping['city'] . ', ' . $shipping['postal_code']) . '<br>';
                            echo htmlspecialchars($shipping['phone']);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5>Sipariş Bilgileri</h5></div>
            <div class="card-body">
                <p><strong>Sipariş No:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Tarih:</strong> <?php echo formatDate($order['created_at']); ?></p>
                <p><strong>Müşteri:</strong> <?php echo htmlspecialchars(($order['first_name']??'Misafir').' '.($order['last_name']??'')); ?></p>
                <?php if ($order['email']): ?><p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p><?php endif; ?>
                <p><strong>Ödeme Yöntemi:</strong> <?php echo htmlspecialchars($order['payment_method']??'-'); ?></p>
                <p><strong>Ödeme Durumu:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5>Sipariş Durumu</h5></div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_status">
                    <select name="status" class="form-select mb-3">
                        <option value="pending" <?php echo $order['status']=='pending'?'selected':''; ?>>Bekliyor</option>
                        <option value="processing" <?php echo $order['status']=='processing'?'selected':''; ?>>İşleniyor</option>
                        <option value="shipped" <?php echo $order['status']=='shipped'?'selected':''; ?>>Kargoya Verildi</option>
                        <option value="delivered" <?php echo $order['status']=='delivered'?'selected':''; ?>>Teslim Edildi</option>
                        <option value="cancelled" <?php echo $order['status']=='cancelled'?'selected':''; ?>>İptal</option>
                        <option value="refunded" <?php echo $order['status']=='refunded'?'selected':''; ?>>İade</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
