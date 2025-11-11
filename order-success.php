<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$orderNumber = get('order');

if (!$orderNumber) {
    redirect(siteUrl());
}

$order = dbQueryOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber]);

if (!$order) {
    redirect(siteUrl());
}

$pageTitle = 'Sipariş Başarılı';

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <?php displayFlash('order_success'); ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="text-success mb-4">Siparişiniz Alındı!</h2>

                    <div class="alert alert-info">
                        <h4>Sipariş Numaranız: <?php echo htmlspecialchars($order['order_number']); ?></h4>
                        <p class="mb-0">Toplam: <?php echo formatPrice($order['total']); ?></p>
                    </div>

                    <p class="lead">Siparişiniz başarıyla oluşturuldu. En kısa sürede işleme alınacaktır.</p>

                    <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-university"></i> Havale/EFT Bilgileri</h5>
                            <p>Siparişinizi tamamlamak için aşağıdaki hesap bilgilerine ödeme yapınız:</p>
                            <p class="mb-0">
                                <strong>Banka:</strong> Örnek Banka<br>
                                <strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000 00<br>
                                <strong>Alıcı:</strong> StarAvcısı E-Ticaret<br>
                                <strong>Açıklama:</strong> <?php echo htmlspecialchars($order['order_number']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <p>Sipariş durumunuzu email adresinize gönderilen bilgilendirme emailinden takip edebilirsiniz.</p>

                    <div class="d-grid gap-2 mt-4">
                        <a href="<?php echo siteUrl(); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Ana Sayfaya Dön
                        </a>
                        <?php if (isCustomer()): ?>
                            <a href="<?php echo siteUrl('account.php'); ?>" class="btn btn-secondary">
                                <i class="fas fa-user"></i> Siparişlerimi Görüntüle
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
