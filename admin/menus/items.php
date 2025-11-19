<?php
/**
 * Menü Öğeleri Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$menu_id = (int)($_GET['id'] ?? 0);
$menu = dbQueryOne("SELECT * FROM menus WHERE id = ?", [$menu_id]);

if (!$menu) {
    setFlash('error', 'Menü bulunamadı.');
    redirect('/admin/menus/');
}

$page_title = $menu['name'] . ' - Menü Öğeleri';

// Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        dbDelete('menu_items', 'id = ?', [(int)$_GET['delete']]);
        setFlash('success', 'Menü öğesi silindi.');
    }
    redirect('/admin/menus/items.php?id=' . $menu_id);
}

// Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/menus/items.php?id=' . $menu_id);
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'menu_id' => $menu_id,
        'parent_id' => (int)$_POST['parent_id'],
        'title' => sanitize($_POST['title']),
        'link_type' => sanitize($_POST['link_type']),
        'link_id' => !empty($_POST['link_id']) ? (int)$_POST['link_id'] : null,
        'url' => sanitize($_POST['url']),
        'target' => sanitize($_POST['target']),
        'icon' => sanitize($_POST['icon']),
        'css_class' => sanitize($_POST['css_class']),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    if ($id > 0) {
        dbUpdate('menu_items', $data, 'id = ?', [$id]);
        setFlash('success', 'Menü öğesi güncellendi.');
    } else {
        dbInsert('menu_items', $data);
        setFlash('success', 'Menü öğesi eklendi.');
    }
    redirect('/admin/menus/items.php?id=' . $menu_id);
}

// Öğeleri getir
$items = dbQuery("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY parent_id, sort_order", [$menu_id]);

// Kategoriler ve sayfalar
$categories = dbQuery("SELECT id, name FROM categories ORDER BY name");
$pages = dbQuery("SELECT id, title FROM pages ORDER BY title");

include '../includes/header.php';

// Hiyerarşik menü gösterimi için yardımcı fonksiyon
function displayMenuItems($items, $parent_id = 0, $level = 0) {
    $html = '';
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
            $html .= '<tr>
                <td>' . $indent . ($level > 0 ? '└─ ' : '') . htmlspecialchars($item['title']) . '</td>
                <td>' . $item['link_type'] . '</td>
                <td>' . ($item['url'] ?: ($item['link_type'] !== 'custom' ? '#' . $item['link_id'] : '-')) . '</td>
                <td>' . $item['sort_order'] . '</td>
                <td><span class="badge bg-' . ($item['status'] ? 'success' : 'danger') . '">' . ($item['status'] ? 'Aktif' : 'Pasif') . '</span></td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editItem(' . htmlspecialchars(json_encode($item)) . ')"><i class="fas fa-edit"></i></button>
                    <a href="?id=' . $item['menu_id'] . '&delete=' . $item['id'] . '&token=' . generateCsrfToken() . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Silmek istediğinize emin misiniz?\')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>';
            $html .= displayMenuItems($items, $item['id'], $level + 1);
        }
    }
    return $html;
}
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-list"></i> <?= htmlspecialchars($menu['name']) ?> - Öğeler</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
            <i class="fas fa-plus"></i> Yeni Öğe
        </button>
        <a href="/admin/menus/" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Geri</a>
    </div>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Tip</th>
                        <th>URL</th>
                        <th>Sıra</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?= displayMenuItems($items) ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="item_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Menü Öğesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Başlık *</label>
                                <input type="text" name="title" id="item_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Üst Menü</label>
                                <select name="parent_id" id="item_parent" class="form-select">
                                    <option value="0">Ana Menü</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Link Tipi</label>
                                <select name="link_type" id="item_type" class="form-select" onchange="toggleLinkFields(this.value)">
                                    <option value="custom">Özel URL</option>
                                    <option value="category">Kategori</option>
                                    <option value="product">Ürün</option>
                                    <option value="page">Sayfa</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3" id="urlField">
                                <label class="form-label">URL</label>
                                <input type="text" name="url" id="item_url" class="form-control" placeholder="https://...">
                            </div>
                            <div class="mb-3" id="categoryField" style="display: none;">
                                <label class="form-label">Kategori</label>
                                <select name="link_id" id="item_category" class="form-select">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3" id="pageField" style="display: none;">
                                <label class="form-label">Sayfa</label>
                                <select name="link_id" id="item_page" class="form-select">
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?= $page['id'] ?>"><?= htmlspecialchars($page['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">İkon (Font Awesome)</label>
                                <input type="text" name="icon" id="item_icon" class="form-control" placeholder="fas fa-home">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Target</label>
                                <select name="target" id="item_target" class="form-select">
                                    <option value="_self">Aynı Pencere</option>
                                    <option value="_blank">Yeni Pencere</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">CSS Sınıfı</label>
                                <input type="text" name="css_class" id="item_class" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sıra</label>
                                <input type="number" name="sort_order" id="item_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="status" id="item_status" class="form-check-input" checked>
                                <label class="form-check-label" for="item_status">Aktif</label>
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
function toggleLinkFields(type) {
    document.getElementById('urlField').style.display = type === 'custom' ? 'block' : 'none';
    document.getElementById('categoryField').style.display = type === 'category' ? 'block' : 'none';
    document.getElementById('pageField').style.display = type === 'page' ? 'block' : 'none';
}

function editItem(item) {
    document.getElementById('item_id').value = item.id;
    document.getElementById('item_title').value = item.title;
    document.getElementById('item_parent').value = item.parent_id;
    document.getElementById('item_type').value = item.link_type;
    document.getElementById('item_url').value = item.url || '';
    document.getElementById('item_icon').value = item.icon || '';
    document.getElementById('item_target').value = item.target;
    document.getElementById('item_class').value = item.css_class || '';
    document.getElementById('item_order').value = item.sort_order;
    document.getElementById('item_status').checked = item.status == 1;

    if (item.link_id) {
        if (item.link_type === 'category') document.getElementById('item_category').value = item.link_id;
        if (item.link_type === 'page') document.getElementById('item_page').value = item.link_id;
    }

    toggleLinkFields(item.link_type);
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
