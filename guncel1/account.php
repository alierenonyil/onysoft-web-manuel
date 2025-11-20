<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

requireCustomer();

$customer = getCustomer();
$pageTitle = 'Hesabım';

// Get customer orders
$orders = dbQuery("
    SELECT * FROM orders
    WHERE customer_id = ?
    ORDER BY created_at DESC
    LIMIT 10
", [$customer['id']]);

// Get customer addresses
$addresses = dbQuery("
    SELECT * FROM customer_addresses
    WHERE customer_id = ?
    ORDER BY is_default DESC
", [$customer['id']]);

// Handle profile update
if (isPost() && post('action') == 'update_profile') {
    if (verifyCsrfToken(post('csrf_token'))) {
        $updateData = [
            'first_name' => cleanText(post('first_name')),
            'last_name' => cleanText(post('last_name')),
            'phone' => cleanText(post('phone'))
        ];

        if (dbUpdate('customers', $updateData, 'id = ?', [$customer['id']])) {
            unset($_SESSION['customer_data']); // Clear cache
            setFlash('account', 'Profil güncellendi', 'success');
            redirect(siteUrl('account.php'));
        }
    }
}

// Handle password change
if (isPost() && post('action') == 'change_password') {
    if (verifyCsrfToken(post('csrf_token'))) {
        $currentPassword = post('current_password');
        $newPassword = post('new_password');
        $confirmPassword = post('confirm_password');

        if (!verifyPassword($currentPassword, $customer['password'])) {
            setFlash('account', 'Mevcut şifre hatalı', 'error');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('account', 'Yeni şifreler eşleşmiyor', 'error');
        } elseif (strlen($newPassword) < 6) {
            setFlash('account', 'Şifre en az 6 karakter olmalı', 'error');
        } else {
            $hashedPassword = hashPassword($newPassword);
            if (dbUpdate('customers', ['password' => $hashedPassword], 'id = ?', [$customer['id']])) {
                setFlash('account', 'Şifre başarıyla değiştirildi', 'success');
                redirect(siteUrl('account.php'));
            }
        }
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Hesabım</h1>

    <?php displayFlash('account'); ?>

    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                        <h5 class="mt-2"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($customer['email']); ?></p>
                    </div>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                                <i class="fas fa-user"></i> Profil Bilgilerim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#orders" data-bs-toggle="tab">
                                <i class="fas fa-shopping-bag"></i> Siparişlerim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#addresses" data-bs-toggle="tab">
                                <i class="fas fa-map-marker-alt"></i> Adreslerim
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#password" data-bs-toggle="tab">
                                <i class="fas fa-key"></i> Şifre Değiştir
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo siteUrl('logout.php'); ?>">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Profil Bilgilerim</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ad *</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Soyad *</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled>
                                    <small class="text-muted">Email adresi değiştirilemez</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Güncelle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Siparişlerim</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($orders)): ?>
                                <div class="p-4 text-center text-muted">
                                    Henüz siparişiniz bulunmuyor
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sipariş No</th>
                                                <th>Tarih</th>
                                                <th>Tutar</th>
                                                <th>Durum</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                                    <td><?php echo formatDate($order['created_at']); ?></td>
                                                    <td><?php echo formatPrice($order['total']); ?></td>
                                                    <td>
                                                        <?php
                                                        $badges = ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                                                        $labels = ['pending'=>'Bekliyor','processing'=>'İşleniyor','shipped'=>'Kargoda','delivered'=>'Teslim','cancelled'=>'İptal'];
                                                        echo '<span class="badge bg-'.($badges[$order['status']]??'secondary').'">'.($labels[$order['status']]??$order['status']).'</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo siteUrl('order-detail.php?order=' . $order['order_number']); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Detay
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Adreslerim</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fas fa-plus"></i> Yeni Adres
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($addresses)): ?>
                                <div class="text-center text-muted">
                                    Henüz kayıtlı adresiniz bulunmuyor
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <?php if ($address['is_default']): ?>
                                                        <span class="badge bg-success mb-2">Varsayılan</span>
                                                    <?php endif; ?>
                                                    <h6><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></h6>
                                                    <p class="mb-0 small">
                                                        <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                                        <?php echo htmlspecialchars($address['city'] . ', ' . $address['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($address['phone']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Şifre Değiştir</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label class="form-label">Mevcut Şifre *</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Yeni Şifre *</label>
                                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                                    <small class="text-muted">En az 6 karakter olmalıdır</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Yeni Şifre Tekrar *</label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Şifreyi Değiştir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
