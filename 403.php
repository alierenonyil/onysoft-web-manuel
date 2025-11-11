<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = '403 - Erişim Engellendi';

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="mb-4">
                <i class="fas fa-ban text-danger" style="font-size: 6rem;"></i>
            </div>
            <h1 class="display-1 fw-bold">403</h1>
            <h2 class="mb-4">Erişim Engellendi</h2>
            <p class="lead mb-4">Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="<?php echo siteUrl(); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Ana Sayfaya Dön
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
