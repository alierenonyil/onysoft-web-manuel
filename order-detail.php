<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$orderNumber = get('order');

if (!$orderNumber) {
    redirect(siteUrl('account.php'));
}

$order = dbQueryOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber]);

if (!$order) {
    setFlash('account', 'Sipariş bulunamadı', 'error');
    redirect(siteUrl('account.php'));
}

// Check if customer owns this order
if (isCustomer() && $order['customer_id'] != $_SESSION['customer_id']) {
    setFlash('account', 'Bu siparişi görüntüleme yetkiniz yok', 'error');
    redirect(siteUrl('account.php'));
}

$orderItems = dbQuery("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);

$pageTitle = 'Sipariş Detayı - ' . $order['order_number'];

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sipariş Detayı</h1>
        <a href="<?php echo siteUrl('account.php'); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Sipariş Ürünleri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Fiyat</th>
                                    <th>Adet</th>
                                    <th>Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <?php if ($item['variant_name']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($item['variant_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['total']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Ara Toplam</th>
                                    <td><?php echo formatPrice($order['subtotal']); ?></td>
                                </tr>
                                <?php if ($order['tax'] > 0): ?>
                                    <tr>
                                        <th colspan="3">KDV</th>
                                        <td><?php echo formatPrice($order['tax']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    <tr>
                                        <th colspan="3">Kargo</th>
                                        <td><?php echo formatPrice($order['shipping_cost']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['discount'] > 0): ?>
                                    <tr>
                                        <th colspan="3">İndirim</th>
                                        <td>-<?php echo formatPrice($order['discount']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-primary">
                                    <th colspan="3">TOPLAM</th>
                                    <th><?php echo formatPrice($order['total']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Addresses -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Fatura Adresi</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $billing = json_decode($order['billing_address'], true);
                            if ($billing):
                            ?>
                                <?php echo htmlspecialchars($billing['first_name'] . ' ' . $billing['last_name']); ?><br>
                                <?php echo htmlspecialchars($billing['address_line1']); ?><br>
                                <?php echo htmlspecialchars($billing['city'] . ', ' . $billing['postal_code']); ?><br>
                                <?php echo htmlspecialchars($billing['phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Teslimat Adresi</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $shipping = json_decode($order['shipping_address'], true);
                            if ($shipping):
                            ?>
                                <?php echo htmlspecialchars($shipping['first_name'] . ' ' . $shipping['last_name']); ?><br>
                                <?php echo htmlspecialchars($shipping['address_line1']); ?><br>
                                <?php echo htmlspecialchars($shipping['city'] . ', ' . $shipping['postal_code']); ?><br>
                                <?php echo htmlspecialchars($shipping['phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Order Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Sipariş Bilgileri</h5>
                </div>
                <div class="card-body">
                    <p><strong>Sipariş No:</strong><br><?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Tarih:</strong><br><?php echo formatDate($order['created_at']); ?></p>
                    <p><strong>Ödeme Yöntemi:</strong><br><?php echo htmlspecialchars($order['payment_method'] == 'bank_transfer' ? 'Havale/EFT' : $order['payment_method']); ?></p>
                    <p><strong>Ödeme Durumu:</strong><br>
                        <?php
                        $paymentBadges = ['unpaid'=>'warning','paid'=>'success','refunded'=>'info','failed'=>'danger'];
                        $paymentLabels = ['unpaid'=>'Ödenmedi','paid'=>'Ödendi','refunded'=>'İade','failed'=>'Başarısız'];
                        echo '<span class="badge bg-'.($paymentBadges[$order['payment_status']]??'secondary').'">'.($paymentLabels[$order['payment_status']]??$order['payment_status']).'</span>';
                        ?>
                    </p>
                    <p><strong>Sipariş Durumu:</strong><br>
                        <?php
                        $badges = ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                        $labels = ['pending'=>'Bekliyor','processing'=>'İşleniyor','shipped'=>'Kargoda','delivered'=>'Teslim','cancelled'=>'İptal'];
                        echo '<span class="badge bg-'.($badges[$order['status']]??'secondary').'">'.($labels[$order['status']]??$order['status']).'</span>';
                        ?>
                    </p>
                    <?php if ($order['tracking_number']): ?>
                        <p><strong>Kargo Takip No:</strong><br><?php echo htmlspecialchars($order['tracking_number']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($order['customer_note']): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Sipariş Notu</h6>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($order['customer_note'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
