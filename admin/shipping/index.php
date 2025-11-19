<?php
/**
 * Kargo Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Kargo Yönetimi';

// Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        dbDelete('shipping_methods', 'id = ?', [(int)$_GET['delete']]);
        setFlash('success', 'Kargo yöntemi silindi.');
    }
    redirect('/admin/shipping/');
}

// Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/shipping/');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => sanitize($_POST['name']),
        'description' => sanitize($_POST['description']),
        'cost' => (float)$_POST['cost'],
        'free_shipping_threshold' => !empty($_POST['free_shipping_threshold']) ? (float)$_POST['free_shipping_threshold'] : null,
        'min_weight' => !empty($_POST['min_weight']) ? (float)$_POST['min_weight'] : null,
        'max_weight' => !empty($_POST['max_weight']) ? (float)$_POST['max_weight'] : null,
        'estimated_days' => sanitize($_POST['estimated_days']),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    if ($id > 0) {
        dbUpdate('shipping_methods', $data, 'id = ?', [$id]);
        setFlash('success', 'Kargo yöntemi güncellendi.');
    } else {
        dbInsert('shipping_methods', $data);
        setFlash('success', 'Kargo yöntemi eklendi.');
    }
    redirect('/admin/shipping/');
}

$methods = dbQuery("SELECT * FROM shipping_methods ORDER BY sort_order");

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-truck"></i> Kargo Yönetimi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shippingModal">
        <i class="fas fa-plus"></i> Yeni Kargo Yöntemi
    </button>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Kargo Yöntemi</th>
                        <th>Ücret</th>
                        <th>Ücretsiz Kargo</th>
                        <th>Tahmini Süre</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($methods as $method): ?>
                        <tr>
                            <td><?= $method['sort_order'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($method['name']) ?></strong>
                                <?php if ($method['description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($method['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= formatPrice($method['cost']) ?></td>
                            <td>
                                <?php if ($method['free_shipping_threshold']): ?>
                                    <?= formatPrice($method['free_shipping_threshold']) ?> üzeri
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($method['estimated_days']) ?></td>
                            <td>
                                <span class="badge bg-<?= $method['status'] ? 'success' : 'danger' ?>">
                                    <?= $method['status'] ? 'Aktif' : 'Pasif' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editMethod(<?= htmlspecialchars(json_encode($method)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $method['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">
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
<div class="modal fade" id="shippingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="method_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Kargo Yöntemi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Yöntem Adı *</label>
                                <input type="text" name="name" id="method_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kargo Ücreti *</label>
                                <input type="number" name="cost" id="method_cost" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" id="method_desc" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ücretsiz Kargo Limiti</label>
                                <input type="number" name="free_shipping_threshold" id="method_free" class="form-control" step="0.01">
                                <small class="text-muted">Bu tutarın üzeri ücretsiz</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Min Ağırlık (kg)</label>
                                <input type="number" name="min_weight" id="method_min" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Max Ağırlık (kg)</label>
                                <input type="number" name="max_weight" id="method_max" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tahmini Teslimat Süresi</label>
                                <input type="text" name="estimated_days" id="method_days" class="form-control" placeholder="1-3 iş günü">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Sıra</label>
                                <input type="number" name="sort_order" id="method_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="status" id="method_status" class="form-check-input" checked>
                                <label class="form-check-label" for="method_status">Aktif</label>
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
function editMethod(method) {
    document.getElementById('method_id').value = method.id;
    document.getElementById('method_name').value = method.name;
    document.getElementById('method_desc').value = method.description || '';
    document.getElementById('method_cost').value = method.cost;
    document.getElementById('method_free').value = method.free_shipping_threshold || '';
    document.getElementById('method_min').value = method.min_weight || '';
    document.getElementById('method_max').value = method.max_weight || '';
    document.getElementById('method_days').value = method.estimated_days || '';
    document.getElementById('method_order').value = method.sort_order;
    document.getElementById('method_status').checked = method.status == 1;
    new bootstrap.Modal(document.getElementById('shippingModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
