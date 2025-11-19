<?php
/**
 * Kupon/İndirim Kodu Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Kupon Yönetimi';

// Kupon Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        dbDelete('coupons', 'id = ?', [(int)$_GET['delete']]);
        setFlash('success', 'Kupon silindi.');
    }
    redirect('/admin/coupons/');
}

// Kupon Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/coupons/');
    }

    $id = (int)($_POST['id'] ?? 0);

    $data = [
        'name' => sanitize($_POST['name']),
        'code' => strtoupper(sanitize($_POST['code'])),
        'type' => sanitize($_POST['type']),
        'discount' => (float)$_POST['discount'],
        'min_order_amount' => (float)$_POST['min_order_amount'],
        'max_discount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
        'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
        'usage_per_customer' => (int)$_POST['usage_per_customer'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'applies_to' => sanitize($_POST['applies_to']),
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    // Kod kontrolü
    $exists = dbQueryOne("SELECT id FROM coupons WHERE code = ? AND id != ?", [$data['code'], $id]);
    if ($exists) {
        setFlash('error', 'Bu kupon kodu zaten kullanılıyor.');
        redirect('/admin/coupons/');
    }

    if ($id > 0) {
        dbUpdate('coupons', $data, 'id = ?', [$id]);
        setFlash('success', 'Kupon güncellendi.');
    } else {
        dbInsert('coupons', $data);
        setFlash('success', 'Kupon oluşturuldu.');
    }
    redirect('/admin/coupons/');
}

$coupons = dbQuery("SELECT * FROM coupons ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-ticket-alt"></i> Kupon Yönetimi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
        <i class="fas fa-plus"></i> Yeni Kupon
    </button>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kupon Kodu</th>
                        <th>Kampanya Adı</th>
                        <th>İndirim</th>
                        <th>Kullanım</th>
                        <th>Geçerlilik</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <?php
                        $now = time();
                        $start = strtotime($coupon['start_date']);
                        $end = strtotime($coupon['end_date']);
                        $isActive = $coupon['status'] && $now >= $start && $now <= $end;
                        ?>
                        <tr>
                            <td><code class="fs-6"><?= htmlspecialchars($coupon['code']) ?></code></td>
                            <td><?= htmlspecialchars($coupon['name']) ?></td>
                            <td>
                                <?php if ($coupon['type'] === 'percentage'): ?>
                                    <span class="badge bg-success">%<?= $coupon['discount'] ?></span>
                                <?php elseif ($coupon['type'] === 'fixed'): ?>
                                    <span class="badge bg-primary"><?= formatPrice($coupon['discount']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info">Ücretsiz Kargo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?: '∞' ?>
                            </td>
                            <td>
                                <small>
                                    <?= date('d.m.Y', $start) ?><br>
                                    <?= date('d.m.Y', $end) ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php elseif ($now < $start): ?>
                                    <span class="badge bg-warning">Beklemede</span>
                                <?php elseif ($now > $end): ?>
                                    <span class="badge bg-secondary">Süresi Doldu</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editCoupon(<?= htmlspecialchars(json_encode($coupon)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $coupon['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="coupon_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Kupon Ekle/Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kampanya Adı *</label>
                                <input type="text" name="name" id="coupon_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kupon Kodu *</label>
                                <input type="text" name="code" id="coupon_code" class="form-control" required style="text-transform: uppercase">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">İndirim Tipi</label>
                                <select name="type" id="coupon_type" class="form-select">
                                    <option value="percentage">Yüzde (%)</option>
                                    <option value="fixed">Sabit Tutar</option>
                                    <option value="free_shipping">Ücretsiz Kargo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">İndirim Miktarı *</label>
                                <input type="number" name="discount" id="coupon_discount" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Max İndirim</label>
                                <input type="number" name="max_discount" id="coupon_max" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Min Sipariş Tutarı</label>
                                <input type="number" name="min_order_amount" id="coupon_min" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Toplam Kullanım Limiti</label>
                                <input type="number" name="usage_limit" id="coupon_limit" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Kişi Başı Kullanım</label>
                                <input type="number" name="usage_per_customer" id="coupon_per_customer" class="form-control" value="1">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Başlangıç Tarihi *</label>
                                <input type="datetime-local" name="start_date" id="coupon_start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bitiş Tarihi *</label>
                                <input type="datetime-local" name="end_date" id="coupon_end" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Uygulanacak</label>
                                <select name="applies_to" id="coupon_applies" class="form-select">
                                    <option value="all">Tüm Ürünler</option>
                                    <option value="categories">Belirli Kategoriler</option>
                                    <option value="products">Belirli Ürünler</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="status" id="coupon_status" class="form-check-input" checked>
                                <label class="form-check-label" for="coupon_status">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCoupon(coupon) {
    document.getElementById('coupon_id').value = coupon.id;
    document.getElementById('coupon_name').value = coupon.name;
    document.getElementById('coupon_code').value = coupon.code;
    document.getElementById('coupon_type').value = coupon.type;
    document.getElementById('coupon_discount').value = coupon.discount;
    document.getElementById('coupon_max').value = coupon.max_discount || '';
    document.getElementById('coupon_min').value = coupon.min_order_amount;
    document.getElementById('coupon_limit').value = coupon.usage_limit || '';
    document.getElementById('coupon_per_customer').value = coupon.usage_per_customer;
    document.getElementById('coupon_start').value = coupon.start_date.replace(' ', 'T');
    document.getElementById('coupon_end').value = coupon.end_date.replace(' ', 'T');
    document.getElementById('coupon_applies').value = coupon.applies_to;
    document.getElementById('coupon_status').checked = coupon.status == 1;
    new bootstrap.Modal(document.getElementById('couponModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
