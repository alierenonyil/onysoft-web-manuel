<?php
/**
 * Sayfa Düzeni Yönetimi - OpenCart Benzeri Layout Sistemi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Sayfa Düzenleri';

// Layout Silme
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (verifyCsrfToken($_GET['token'])) {
        $id = (int)$_GET['delete'];
        $layout = dbQueryOne("SELECT * FROM layouts WHERE id = ?", [$id]);

        if ($layout && !$layout['is_default']) {
            dbDelete('layouts', 'id = ?', [$id]);
            setFlash('success', 'Düzen başarıyla silindi.');
        } else {
            setFlash('error', 'Varsayılan düzen silinemez.');
        }
    }
    redirect('/admin/layouts/');
}

// Layout Ekleme/Düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/layouts/');
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name']);
    $route = sanitize($_POST['route']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    $data = [
        'name' => $name,
        'route' => $route,
        'is_default' => $is_default,
        'status' => $status
    ];

    if ($id > 0) {
        dbUpdate('layouts', $data, 'id = ?', [$id]);
        setFlash('success', 'Düzen güncellendi.');
    } else {
        dbInsert('layouts', $data);
        setFlash('success', 'Düzen oluşturuldu.');
    }

    redirect('/admin/layouts/');
}

// Layoutları Getir
$layouts = dbQuery("SELECT * FROM layouts ORDER BY name");

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-th-large"></i> Sayfa Düzenleri
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#layoutModal">
        <i class="fas fa-plus"></i> Yeni Düzen
    </button>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Düzen Adı</th>
                        <th>Sayfa Yolu</th>
                        <th width="100">Varsayılan</th>
                        <th width="100">Durum</th>
                        <th width="200">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($layouts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Henüz düzen oluşturulmamış.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($layouts as $layout): ?>
                            <tr>
                                <td><?= $layout['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($layout['name']) ?></strong>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($layout['route'] ?: 'Genel') ?></code>
                                </td>
                                <td>
                                    <?php if ($layout['is_default']): ?>
                                        <span class="badge bg-success">Evet</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hayır</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($layout['status']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/admin/layouts/modules.php?id=<?= $layout['id'] ?>" class="btn btn-sm btn-info" title="Modüller">
                                        <i class="fas fa-puzzle-piece"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning"
                                            onclick="editLayout(<?= htmlspecialchars(json_encode($layout)) ?>)" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$layout['is_default']): ?>
                                        <a href="?delete=<?= $layout['id'] ?>&token=<?= generateCsrfToken() ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Bu düzeni silmek istediğinize emin misiniz?')" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Layout Modal -->
<div class="modal fade" id="layoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="layoutForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="layout_id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Düzen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Düzen Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="layout_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sayfa Yolu</label>
                        <select name="route" id="layout_route" class="form-select">
                            <option value="">Genel</option>
                            <option value="home">Ana Sayfa</option>
                            <option value="category">Kategori Sayfası</option>
                            <option value="product">Ürün Sayfası</option>
                            <option value="page">İçerik Sayfası</option>
                            <option value="cart">Sepet</option>
                            <option value="checkout">Ödeme</option>
                            <option value="account">Hesabım</option>
                            <option value="contact">İletişim</option>
                        </select>
                        <small class="text-muted">Bu düzenin hangi sayfa türünde kullanılacağını seçin.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" id="layout_default" class="form-check-input">
                                <label class="form-check-label" for="layout_default">Varsayılan</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="status" id="layout_status" class="form-check-input" checked>
                                <label class="form-check-label" for="layout_status">Aktif</label>
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
function editLayout(layout) {
    document.getElementById('modalTitle').textContent = 'Düzeni Düzenle';
    document.getElementById('layout_id').value = layout.id;
    document.getElementById('layout_name').value = layout.name;
    document.getElementById('layout_route').value = layout.route || '';
    document.getElementById('layout_default').checked = layout.is_default == 1;
    document.getElementById('layout_status').checked = layout.status == 1;

    new bootstrap.Modal(document.getElementById('layoutModal')).show();
}

// Modal kapanınca formu sıfırla
document.getElementById('layoutModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Yeni Düzen';
    document.getElementById('layoutForm').reset();
    document.getElementById('layout_id').value = '0';
    document.getElementById('layout_status').checked = true;
});
</script>

<?php include '../includes/footer.php'; ?>
