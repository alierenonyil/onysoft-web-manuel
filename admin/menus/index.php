<?php
/**
 * Menü Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Menü Yönetimi';

// Menü Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        dbDelete('menus', 'id = ?', [(int)$_GET['delete']]);
        setFlash('success', 'Menü silindi.');
    }
    redirect('/admin/menus/');
}

// Menü Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/menus/');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => sanitize($_POST['name']),
        'position' => sanitize($_POST['position']),
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    if ($id > 0) {
        dbUpdate('menus', $data, 'id = ?', [$id]);
        setFlash('success', 'Menü güncellendi.');
    } else {
        dbInsert('menus', $data);
        setFlash('success', 'Menü oluşturuldu.');
    }
    redirect('/admin/menus/');
}

$menus = dbQuery("SELECT m.*, (SELECT COUNT(*) FROM menu_items WHERE menu_id = m.id) as item_count FROM menus m ORDER BY m.name");

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-bars"></i> Menü Yönetimi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal">
        <i class="fas fa-plus"></i> Yeni Menü
    </button>
</div>

<?php displayFlash(); ?>

<div class="row">
    <?php foreach ($menus as $menu): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($menu['name']) ?></h5>
                        <span class="badge bg-<?= $menu['status'] ? 'success' : 'danger' ?>"><?= $menu['status'] ? 'Aktif' : 'Pasif' ?></span>
                    </div>
                    <p class="text-muted mb-2">Pozisyon: <strong><?= ucfirst($menu['position']) ?></strong></p>
                    <p class="mb-0"><span class="badge bg-info"><?= $menu['item_count'] ?> öğe</span></p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="/admin/menus/items.php?id=<?= $menu['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-list"></i> Öğeler</a>
                    <button class="btn btn-sm btn-warning" onclick="editMenu(<?= htmlspecialchars(json_encode($menu)) ?>)"><i class="fas fa-edit"></i></button>
                    <a href="?delete=<?= $menu['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="menu_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Menü Ekle/Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Menü Adı *</label>
                        <input type="text" name="name" id="menu_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pozisyon</label>
                        <select name="position" id="menu_position" class="form-select">
                            <option value="header">Header</option>
                            <option value="footer">Footer</option>
                            <option value="mobile">Mobil</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="status" id="menu_status" class="form-check-input" checked>
                        <label class="form-check-label" for="menu_status">Aktif</label>
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
function editMenu(menu) {
    document.getElementById('menu_id').value = menu.id;
    document.getElementById('menu_name').value = menu.name;
    document.getElementById('menu_position').value = menu.position;
    document.getElementById('menu_status').checked = menu.status == 1;
    new bootstrap.Modal(document.getElementById('menuModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
