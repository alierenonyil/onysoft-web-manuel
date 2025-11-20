<?php
/**
 * Banner Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Banner Yönetimi';

// Banner Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        $id = (int)$_GET['delete'];
        dbDelete('banners', 'id = ?', [$id]);
        setFlash('success', 'Banner silindi.');
    }
    redirect('/admin/banners/');
}

// Banner Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/banners/');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => sanitize($_POST['name']),
        'position' => sanitize($_POST['position']),
        'width' => (int)$_POST['width'] ?: null,
        'height' => (int)$_POST['height'] ?: null
    ];

    if ($id > 0) {
        dbUpdate('banners', $data, 'id = ?', [$id]);
        setFlash('success', 'Banner güncellendi.');
    } else {
        dbInsert('banners', $data);
        setFlash('success', 'Banner oluşturuldu.');
    }

    redirect('/admin/banners/');
}

$banners = dbQuery("SELECT b.*, (SELECT COUNT(*) FROM banner_items WHERE banner_id = b.id) as item_count FROM banners b ORDER BY b.created_at DESC");

$positions = [
    'home_top' => 'Ana Sayfa Üst',
    'home_middle' => 'Ana Sayfa Orta',
    'home_bottom' => 'Ana Sayfa Alt',
    'sidebar' => 'Yan Menü',
    'category' => 'Kategori Sayfası',
    'product' => 'Ürün Sayfası',
    'footer' => 'Alt Bilgi'
];

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-image"></i> Banner Yönetimi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bannerModal">
        <i class="fas fa-plus"></i> Yeni Banner
    </button>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Banner Adı</th>
                        <th>Pozisyon</th>
                        <th>Boyut</th>
                        <th>Görsel Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td><?= $banner['id'] ?></td>
                            <td><strong><?= htmlspecialchars($banner['name']) ?></strong></td>
                            <td><span class="badge bg-info"><?= $positions[$banner['position']] ?? $banner['position'] ?></span></td>
                            <td><?= $banner['width'] && $banner['height'] ? $banner['width'].'x'.$banner['height'].'px' : '-' ?></td>
                            <td><span class="badge bg-primary"><?= $banner['item_count'] ?></span></td>
                            <td>
                                <a href="/admin/banners/items.php?id=<?= $banner['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-images"></i></a>
                                <button class="btn btn-sm btn-warning" onclick="editBanner(<?= htmlspecialchars(json_encode($banner)) ?>)"><i class="fas fa-edit"></i></button>
                                <a href="?delete=<?= $banner['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="bannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="banner_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="bannerModalTitle">Yeni Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Banner Adı *</label>
                        <input type="text" name="name" id="banner_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pozisyon</label>
                        <select name="position" id="banner_position" class="form-select">
                            <?php foreach ($positions as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Genişlik (px)</label>
                                <input type="number" name="width" id="banner_width" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Yükseklik (px)</label>
                                <input type="number" name="height" id="banner_height" class="form-control">
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
function editBanner(banner) {
    document.getElementById('bannerModalTitle').textContent = 'Banner Düzenle';
    document.getElementById('banner_id').value = banner.id;
    document.getElementById('banner_name').value = banner.name;
    document.getElementById('banner_position').value = banner.position;
    document.getElementById('banner_width').value = banner.width || '';
    document.getElementById('banner_height').value = banner.height || '';
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
