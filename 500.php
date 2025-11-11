<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = '500 - Sunucu Hatası';

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="mb-4">
                <i class="fas fa-server text-danger" style="font-size: 6rem;"></i>
            </div>
            <h1 class="display-1 fw-bold">500</h1>
            <h2 class="mb-4">Sunucu Hatası</h2>
            <p class="lead mb-4">Üzgünüz, bir şeyler ters gitti. Lütfen daha sonra tekrar deneyin.</p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="<?php echo siteUrl(); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Ana Sayfaya Dön
                </a>
                <a href="javascript:location.reload()" class="btn btn-secondary btn-lg">
                    <i class="fas fa-redo"></i> Sayfayı Yenile
                </a>
            </div>

            <div class="alert alert-info mt-4">
                Sorun devam ederse lütfen bizimle iletişime geçin: <?php echo getSetting('site_email'); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
