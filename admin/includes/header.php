<?php
if (!defined('ADMIN_PAGE')) {
    die('Direct access not allowed');
}

requireAdmin();
$admin = getAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/admin.css'); ?>" rel="stylesheet">
    <?php echo csrfMeta(); ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-store"></i> <?php echo SITE_NAME; ?></h4>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/index.php'); ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'product') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/products/index.php'); ?>">
                    <i class="fas fa-box"></i> Ürünler
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'categor') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/categories/index.php'); ?>">
                    <i class="fas fa-folder"></i> Kategoriler
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'order') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/orders/index.php'); ?>">
                    <i class="fas fa-shopping-cart"></i> Siparişler
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'customer') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/customers/index.php'); ?>">
                    <i class="fas fa-users"></i> Müşteriler
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'slider') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/sliders/index.php'); ?>">
                    <i class="fas fa-images"></i> Slider
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'page') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/pages/index.php'); ?>">
                    <i class="fas fa-file-alt"></i> Sayfalar
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'email') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/emails/index.php'); ?>">
                    <i class="fas fa-envelope"></i> Toplu Mail
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/reports/index.php'); ?>">
                    <i class="fas fa-chart-line"></i> Raporlar
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tools') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/tools/backup.php'); ?>">
                    <i class="fas fa-tools"></i> Araçlar
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo strpos($currentPage, 'setting') !== false || strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : ''; ?>" href="#settingsMenu" data-bs-toggle="collapse">
                    <i class="fas fa-cog"></i> Ayarlar <i class="fas fa-chevron-down float-end mt-1" style="font-size: 0.8rem;"></i>
                </a>
                <div class="collapse <?php echo strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'show' : ''; ?>" id="settingsMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/settings/index.php'); ?>">
                                <i class="fas fa-sliders-h"></i> Genel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'mail.php' ? 'active' : ''; ?>" href="<?php echo siteUrl('admin/settings/mail.php'); ?>">
                                <i class="fas fa-envelope-open-text"></i> Mail
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="<?php echo siteUrl(); ?>" target="_blank" class="btn btn-sm btn-outline-light w-100 mb-2">
                <i class="fas fa-external-link-alt"></i> Siteyi Görüntüle
            </a>
            <a href="<?php echo siteUrl('admin/logout.php'); ?>" class="btn btn-sm btn-danger w-100">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
            <div class="container-fluid">
                <button class="btn btn-link" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-decoration-none text-dark" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-lg"></i>
                            <span class="ms-2"><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo siteUrl('admin/settings/profile.php'); ?>"><i class="fas fa-user-edit"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo siteUrl('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Çıkış</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="content-wrapper">
