<?php
if (!defined('FRONTEND_PAGE')) {
    die('Direct access not allowed');
}

// Get cart count
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Get categories for menu
$menuCategories = dbQuery("SELECT * FROM categories WHERE parent_id = 0 AND status = 1 ORDER BY sort_order ASC LIMIT 10");

// Site settings
$siteName = getSetting('site_name', SITE_NAME);
$siteLogo = getSetting('site_logo');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? $siteName; ?></title>
    <meta name="description" content="<?php echo $pageDescription ?? getSetting('site_description'); ?>">
    <meta name="keywords" content="<?php echo $pageKeywords ?? ''; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
    <?php echo csrfMeta(); ?>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <small>
                        <i class="fas fa-phone"></i> <?php echo getSetting('site_phone'); ?>
                        <span class="mx-2">|</span>
                        <i class="fas fa-envelope"></i> <?php echo getSetting('site_email'); ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (isCustomer()): ?>
                        <small>
                            <i class="fas fa-user"></i>
                            <a href="<?php echo siteUrl('account.php'); ?>" class="text-white text-decoration-none">Hesabım</a>
                            <span class="mx-2">|</span>
                            <a href="<?php echo siteUrl('logout.php'); ?>" class="text-white text-decoration-none">Çıkış</a>
                        </small>
                    <?php else: ?>
                        <small>
                            <a href="<?php echo siteUrl('login.php'); ?>" class="text-white text-decoration-none"><i class="fas fa-sign-in-alt"></i> Giriş</a>
                            <span class="mx-2">|</span>
                            <a href="<?php echo siteUrl('register.php'); ?>" class="text-white text-decoration-none"><i class="fas fa-user-plus"></i> Kayıt</a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container">
            <div class="row align-items-center py-3">
                <div class="col-md-3">
                    <a href="<?php echo siteUrl(); ?>" class="navbar-brand">
                        <?php if ($siteLogo): ?>
                            <img src="<?php echo siteUrl('uploads/' . $siteLogo); ?>" alt="<?php echo $siteName; ?>" height="50">
                        <?php else: ?>
                            <h3 class="mb-0"><?php echo $siteName; ?></h3>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="col-md-6">
                    <form action="<?php echo siteUrl('search.php'); ?>" method="GET">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Ürün ara..." required>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-md-3 text-end">
                    <a href="<?php echo siteUrl('cart.php'); ?>" class="btn btn-outline-primary position-relative">
                        <i class="fas fa-shopping-cart"></i> Sepet
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo siteUrl(); ?>"><i class="fas fa-home"></i> Ana Sayfa</a>
                    </li>
                    <?php foreach ($menuCategories as $category): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo siteUrl('category.php?slug=' . $category['slug']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
