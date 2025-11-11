<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Slider Yönetimi';

if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $id = (int)post('id');
        $data = [
            'title' => cleanText(post('title')),
            'subtitle' => cleanText(post('subtitle')),
            'link_url' => cleanText(post('link_url')),
            'link_text' => cleanText(post('link_text')),
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int)post('sort_order', 0)
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/sliders');
            if ($upload['success']) {
                if ($id > 0) {
                    $old = dbQueryOne("SELECT image FROM sliders WHERE id = ?", [$id]);
                    if ($old && $old['image']) deleteFile(UPLOAD_PATH . '/sliders/' . $old['image']);
                }
                $data['image'] = $upload['filename'];
            }
        }

        if ($id > 0) {
            dbUpdate('sliders', $data, 'id = ?', [$id]);
            setFlash('slider', 'Slider güncellendi', 'success');
        } else {
            if (empty($data['image'])) {
                setFlash('slider', 'Görsel zorunludur', 'error');
                redirect(siteUrl('admin/sliders/index.php'));
            }
            dbInsert('sliders', $data);
            setFlash('slider', 'Slider oluşturuldu', 'success');
        }
        redirect(siteUrl('admin/sliders/index.php'));
    }
}

if (get('delete')) {
    $id = (int)get('delete');
    $slider = dbQueryOne("SELECT image FROM sliders WHERE id = ?", [$id]);
    if ($slider && $slider['image']) deleteFile(UPLOAD_PATH . '/sliders/' . $slider['image']);
    dbDelete('sliders', 'id = ?', [$id]);
    setFlash('slider', 'Slider silindi', 'success');
    redirect(siteUrl('admin/sliders/index.php'));
}

$sliders = dbQuery("SELECT * FROM sliders ORDER BY sort_order ASC");
$editSlider = get('edit') ? dbQueryOne("SELECT * FROM sliders WHERE id = ?", [get('edit')]) : null;

require_once '../includes/header.php';
?>

<h2><i class="fas fa-images"></i> Slider Yönetimi</h2>
<?php displayFlash('slider'); ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5><?php echo $editSlider ? 'Slider Düzenle' : 'Yeni Slider'; ?></h5></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <?php if ($editSlider): ?>
                        <input type="hidden" name="id" value="<?php echo $editSlider['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Başlık *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editSlider['title'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Alt Başlık</label>
                        <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($editSlider['subtitle'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label>Link URL</label>
                        <input type="url" name="link_url" class="form-control" value="<?php echo htmlspecialchars($editSlider['link_url'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label>Link Metni</label>
                        <input type="text" name="link_text" class="form-control" value="<?php echo htmlspecialchars($editSlider['link_text'] ?? ''); ?>">
                    </div>

                    <?php if ($editSlider && $editSlider['image']): ?>
                        <div class="mb-3">
                            <img src="<?php echo siteUrl('uploads/sliders/' . $editSlider['image']); ?>" class="img-thumbnail mb-2" style="max-width:100%">
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Görsel <?php echo $editSlider ? '' : '*'; ?></label>
                        <input type="file" name="image" class="form-control" accept="image/*" <?php echo $editSlider ? '' : 'required'; ?>>
                    </div>

                    <div class="mb-3">
                        <label>Sıra</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $editSlider['sort_order'] ?? 0; ?>">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="status" class="form-check-input" <?php echo (!$editSlider || $editSlider['status']) ? 'checked' : ''; ?>>
                            <label>Aktif</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Kaydet</button>
                    <?php if ($editSlider): ?>
                        <a href="index.php" class="btn btn-secondary w-100 mt-2">İptal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th width="120">Görsel</th><th>Başlık</th><th>Sıra</th><th>Durum</th><th>İşlem</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sliders as $slider): ?>
                            <tr>
                                <td><img src="<?php echo siteUrl('uploads/sliders/' . $slider['image']); ?>" class="img-thumbnail" style="width:100px"></td>
                                <td><strong><?php echo htmlspecialchars($slider['title']); ?></strong><br><small><?php echo htmlspecialchars($slider['subtitle'] ?? ''); ?></small></td>
                                <td><?php echo $slider['sort_order']; ?></td>
                                <td><?php echo $slider['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $slider['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $slider['id']; ?>" class="btn btn-sm btn-danger delete-confirm"><i class="fas fa-trash"></i></a>
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
