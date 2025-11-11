<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = '404 - Sayfa Bulunamadı';

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 6rem;"></i>
            </div>
            <h1 class="display-1 fw-bold">404</h1>
            <h2 class="mb-4">Sayfa Bulunamadı</h2>
            <p class="lead mb-4">Aradığınız sayfa mevcut değil veya taşınmış olabilir.</p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="<?php echo siteUrl(); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Ana Sayfaya Dön
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>

            <div class="mt-5">
                <h5>Popüler Kategoriler</h5>
                <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                    <?php
                    $categories = dbQuery("SELECT * FROM categories WHERE status = 1 AND parent_id = 0 LIMIT 5");
                    foreach ($categories as $cat):
                    ?>
                        <a href="<?php echo siteUrl('category.php?slug=' . $cat['slug']); ?>" class="btn btn-outline-primary">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
