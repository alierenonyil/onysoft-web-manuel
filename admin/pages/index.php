<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Sayfa Yönetimi';

if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $id = (int)post('id');
        $data = [
            'title' => cleanText(post('title')),
            'content' => post('content'),
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int)post('sort_order', 0)
        ];

        if ($id > 0) {
            dbUpdate('pages', $data, 'id = ?', [$id]);
            setFlash('page', 'Sayfa güncellendi', 'success');
        } else {
            $data['slug'] = generateSlug($data['title']);
            if (dbExists('pages', 'slug = ?', [$data['slug']])) {
                $data['slug'] .= '-' . time();
            }
            dbInsert('pages', $data);
            setFlash('page', 'Sayfa oluşturuldu', 'success');
        }
        redirect(siteUrl('admin/pages/index.php'));
    }
}

if (get('delete')) {
    $id = (int)get('delete');
    dbDelete('pages', 'id = ?', [$id]);
    setFlash('page', 'Sayfa silindi', 'success');
    redirect(siteUrl('admin/pages/index.php'));
}

$pages = dbQuery("SELECT * FROM pages ORDER BY sort_order ASC");
$editPage = get('edit') ? dbQueryOne("SELECT * FROM pages WHERE id = ?", [get('edit')]) : null;

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between mb-4">
    <h2><i class="fas fa-file-alt"></i> Sayfa Yönetimi</h2>
    <?php if (!get('edit') && !get('create')): ?>
        <a href="?create=1" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Sayfa</a>
    <?php endif; ?>
</div>

<?php displayFlash('page'); ?>

<?php if (get('edit') || get('create')): ?>
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo $editPage ? 'Sayfa Düzenle' : 'Yeni Sayfa'; ?></h5></div>
        <div class="card-body">
            <form method="POST">
                <?php echo csrfField(); ?>
                <?php if ($editPage): ?>
                    <input type="hidden" name="id" value="<?php echo $editPage['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-9">
                        <div class="mb-3">
                            <label>Sayfa Başlığı *</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label>İçerik</label>
                            <textarea name="content" class="form-control" rows="15"><?php echo htmlspecialchars($editPage['content'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Sıra</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?php echo $editPage['sort_order'] ?? 0; ?>">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="status" class="form-check-input" <?php echo (!$editPage || $editPage['status']) ? 'checked' : ''; ?>>
                                        <label>Aktif</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Kaydet</button>
                                <a href="index.php" class="btn btn-secondary w-100 mt-2">İptal</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($page['slug']); ?></td>
                            <td><?php echo $page['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'; ?></td>
                            <td><?php echo formatDate($page['created_at'], 'd.m.Y'); ?></td>
                            <td>
                                <a href="?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?php echo $page['id']; ?>" class="btn btn-sm btn-danger delete-confirm"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
