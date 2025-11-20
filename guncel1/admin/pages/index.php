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
            'meta_description' => cleanText(post('meta_description')),
            'meta_keywords' => cleanText(post('meta_keywords')),
            'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0,
            'menu_order' => (int)post('menu_order', 0),
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

$pages = dbQuery("SELECT * FROM pages ORDER BY menu_order ASC, sort_order ASC, title ASC");
$editPage = get('edit') ? dbQueryOne("SELECT * FROM pages WHERE id = ?", [get('edit')]) : null;

require_once '../includes/header.php';
?>

<style>
.page-card {
    transition: all 0.3s;
    border-left: 4px solid #667eea;
}
.page-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-file-alt"></i> Sayfa Yönetimi</h2>
        <p class="text-muted mb-0">Sitenizin özel sayfalarını yönetin</p>
    </div>
    <?php if (!get('edit') && !get('create')): ?>
        <a href="?create=1" class="btn btn-primary btn-lg">
            <i class="fas fa-plus"></i> Yeni Sayfa Oluştur
        </a>
    <?php endif; ?>
</div>

<?php displayFlash('page'); ?>

<?php if (get('edit') || get('create')): ?>
    <form method="POST">
        <?php echo csrfField(); ?>
        <?php if ($editPage): ?>
            <input type="hidden" name="id" value="<?php echo $editPage['id']; ?>">
        <?php endif; ?>

        <div class="row g-4">
            <!-- Main Content Column -->
            <div class="col-lg-9">
                <!-- Basic Info -->
                <div class="card page-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> <?php echo $editPage ? 'Sayfa Düzenle' : 'Yeni Sayfa'; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sayfa Başlığı <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="pageTitle" class="form-control form-control-lg"
                                value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>"
                                placeholder="Örn: Hakkımızda, İletişim, Gizlilik Politikası" required>
                            <?php if ($editPage): ?>
                                <small class="text-muted">
                                    <i class="fas fa-link"></i> URL: <?php echo siteUrl('page/' . $editPage['slug']); ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sayfa İçeriği</label>
                            <textarea name="content" id="pageContent" class="form-control" rows="20"><?php echo htmlspecialchars($editPage['content'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="card page-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-search"></i> SEO Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta Açıklama</label>
                            <textarea name="meta_description" class="form-control" rows="3"
                                placeholder="Sayfanızın kısa açıklaması (Google'da görünür, 150-160 karakter önerilir)"
                                maxlength="160"><?php echo htmlspecialchars($editPage['meta_description'] ?? ''); ?></textarea>
                            <small class="text-muted">
                                <span id="metaDescLength">0</span>/160 karakter
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Anahtar Kelimeler</label>
                            <input type="text" name="meta_keywords" class="form-control"
                                value="<?php echo htmlspecialchars($editPage['meta_keywords'] ?? ''); ?>"
                                placeholder="kelime1, kelime2, kelime3">
                            <small class="text-muted">Virgülle ayırın</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-3">
                <!-- Publish Settings -->
                <div class="card page-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Yayın Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="status" class="form-check-input" id="pageStatus"
                                    <?php echo (!$editPage || $editPage['status']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pageStatus">
                                    <strong>Yayında</strong>
                                    <small class="d-block text-muted">Pasifse ziyaretçiler göremez</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_in_menu" class="form-check-input" id="showInMenu"
                                    <?php echo (!$editPage || $editPage['show_in_menu']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showInMenu">
                                    <strong>Menüde Göster</strong>
                                    <small class="d-block text-muted">Footer menüsünde görünsün mü?</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Menü Sırası</label>
                            <input type="number" name="menu_order" class="form-control"
                                value="<?php echo $editPage['menu_order'] ?? 0; ?>" min="0">
                            <small class="text-muted">Menüdeki sıra (0=ilk)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Genel Sıra</label>
                            <input type="number" name="sort_order" class="form-control"
                                value="<?php echo $editPage['sort_order'] ?? 0; ?>" min="0">
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Kaydet
                        </button>

                        <a href="index.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> İptal
                        </a>

                        <?php if ($editPage): ?>
                            <hr>
                            <a href="?delete=<?php echo $editPage['id']; ?>" class="btn btn-danger w-100 delete-confirm">
                                <i class="fas fa-trash"></i> Sil
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-lightbulb"></i> İpuçları</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <ul class="ps-3 mb-0">
                                <li>SEO için meta açıklama ekleyin</li>
                                <li>Başlıkta anahtar kelime kullanın</li>
                                <li>İçeriğe resim ekleyebilirsiniz</li>
                                <li>Menü sırası düşük olanlar başta görünür</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: '#pageContent',
        height: 600,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link | code | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
        language: 'tr_TR'
    });

    // Meta description character counter
    const metaDescInput = document.querySelector('[name="meta_description"]');
    const metaDescCounter = document.getElementById('metaDescLength');

    if (metaDescInput) {
        metaDescInput.addEventListener('input', function() {
            metaDescCounter.textContent = this.value.length;

            if (this.value.length > 160) {
                metaDescCounter.classList.add('text-danger');
            } else {
                metaDescCounter.classList.remove('text-danger');
            }
        });

        // Trigger on load
        metaDescInput.dispatchEvent(new Event('input'));
    }
    </script>

<?php else: ?>
    <!-- Pages List -->
    <?php if (empty($pages)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-alt text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">Henüz Sayfa Yok</h4>
                <p class="text-muted">Hadi ilk sayfanızı oluşturun!</p>
                <a href="?create=1" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Sayfa Oluştur
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pages as $page): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card page-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt text-primary"></i>
                                    <?php echo htmlspecialchars($page['title']); ?>
                                </h5>
                                <?php if ($page['status']): ?>
                                    <span class="badge bg-success">Yayında</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pasif</span>
                                <?php endif; ?>
                            </div>

                            <p class="card-text text-muted small">
                                <i class="fas fa-link"></i> /page/<?php echo htmlspecialchars($page['slug']); ?>
                            </p>

                            <?php if ($page['meta_description']): ?>
                                <p class="card-text small">
                                    <?php echo truncate($page['meta_description'], 100); ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex gap-2 align-items-center text-muted small mb-3">
                                <?php if ($page['show_in_menu']): ?>
                                    <span class="badge bg-info text-white">
                                        <i class="fas fa-bars"></i> Menüde
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <i class="fas fa-sort"></i> Sıra: <?php echo $page['menu_order']; ?>
                                </span>
                            </div>

                            <div class="text-muted small mb-3">
                                <i class="fas fa-calendar"></i> <?php echo formatDate($page['created_at'], 'd.m.Y'); ?>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary flex-fill">
                                    <i class="fas fa-edit"></i> Düzenle
                                </a>
                                <a href="<?php echo siteUrl('page/' . $page['slug']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="?delete=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
