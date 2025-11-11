<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Kategori Yönetimi';

// Handle create/update
if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $id = (int)post('id');
        $data = [
            'parent_id' => (int)post('parent_id', 0),
            'name' => cleanText(post('name')),
            'description' => cleanText(post('description')),
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int)post('sort_order', 0)
        ];

        if ($id > 0) {
            // Update
            dbUpdate('categories', $data, 'id = ?', [$id]);
            setFlash('category', 'Kategori güncellendi', 'success');
        } else {
            // Create
            $data['slug'] = generateSlug($data['name']);
            if (dbExists('categories', 'slug = ?', [$data['slug']])) {
                $data['slug'] .= '-' . time();
            }
            dbInsert('categories', $data);
            setFlash('category', 'Kategori oluşturuldu', 'success');
        }
        redirect(siteUrl('admin/categories/index.php'));
    }
}

// Handle delete
if (get('delete')) {
    $id = (int)get('delete');
    dbDelete('categories', 'id = ?', [$id]);
    setFlash('category', 'Kategori silindi', 'success');
    redirect(siteUrl('admin/categories/index.php'));
}

$categories = dbQuery("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.sort_order ASC, c.name ASC");
$editCategory = get('edit') ? dbQueryOne("SELECT * FROM categories WHERE id = ?", [get('edit')]) : null;

require_once '../includes/header.php';
?>

<h2><i class="fas fa-folder"></i> Kategori Yönetimi</h2>
<?php displayFlash('category'); ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5><?php echo $editCategory ? 'Kategori Düzenle' : 'Yeni Kategori'; ?></h5></div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Kategori Adı *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Üst Kategori</label>
                        <select name="parent_id" class="form-select">
                            <option value="0">Ana Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if (!$editCategory || $cat['id'] != $editCategory['id']): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($editCategory && $editCategory['parent_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Sıra</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $editCategory['sort_order'] ?? 0; ?>">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="status" class="form-check-input" <?php echo (!$editCategory || $editCategory['status']) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> <?php echo $editCategory ? 'Güncelle' : 'Oluştur'; ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="index.php" class="btn btn-secondary w-100 mt-2">İptal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Kategori</th><th>Üst Kategori</th><th>Sıra</th><th>Durum</th><th>İşlem</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['parent_name'] ?? '-'); ?></td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td><?php echo $category['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger delete-confirm"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
