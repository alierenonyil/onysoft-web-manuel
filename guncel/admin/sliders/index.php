<?php
/**
 * Gelişmiş Slider Yönetimi - OpenCart Benzeri
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Slider Yönetimi';

// Slider Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        $id = (int)$_GET['delete'];
        // Slider item'larını sil
        $items = dbQuery("SELECT image, image_mobile FROM slider_items WHERE slider_id = ?", [$id]);
        foreach ($items as $item) {
            if ($item['image'] && file_exists(UPLOAD_PATH . '/sliders/' . $item['image'])) {
                unlink(UPLOAD_PATH . '/sliders/' . $item['image']);
            }
            if ($item['image_mobile'] && file_exists(UPLOAD_PATH . '/sliders/' . $item['image_mobile'])) {
                unlink(UPLOAD_PATH . '/sliders/' . $item['image_mobile']);
            }
        }
        dbDelete('sliders', 'id = ?', [$id]);
        setFlash('success', 'Slider silindi.');
    }
    redirect('/admin/sliders/');
}

// Slider Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/sliders/');
    }

    $id = (int)($_POST['id'] ?? 0);

    $data = [
        'name' => sanitize($_POST['name']),
        'type' => sanitize($_POST['type']),
        'width' => (int)$_POST['width'],
        'height' => (int)$_POST['height'],
        'autoplay' => isset($_POST['autoplay']) ? 1 : 0,
        'autoplay_speed' => (int)$_POST['autoplay_speed'],
        'animation' => sanitize($_POST['animation']),
        'show_arrows' => isset($_POST['show_arrows']) ? 1 : 0,
        'show_dots' => isset($_POST['show_dots']) ? 1 : 0,
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    if ($id > 0) {
        dbUpdate('sliders', $data, 'id = ?', [$id]);
        setFlash('success', 'Slider güncellendi.');
    } else {
        dbInsert('sliders', $data);
        setFlash('success', 'Slider oluşturuldu.');
    }

    redirect('/admin/sliders/');
}

// Sliderları Getir
$sliders = dbQuery("SELECT s.*, (SELECT COUNT(*) FROM slider_items WHERE slider_id = s.id) as item_count FROM sliders s ORDER BY s.created_at DESC");

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-images"></i> Slider Yönetimi
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sliderModal">
        <i class="fas fa-plus"></i> Yeni Slider
    </button>
</div>

<?php displayFlash(); ?>

<div class="row">
    <?php if (empty($sliders)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Henüz slider oluşturulmamış.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sliderModal">
                        <i class="fas fa-plus"></i> İlk Slider'ı Oluştur
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($sliders as $slider): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($slider['name']) ?></h5>
                                <span class="badge bg-<?= $slider['status'] ? 'success' : 'danger' ?>">
                                    <?= $slider['status'] ? 'Aktif' : 'Pasif' ?>
                                </span>
                            </div>
                            <span class="badge bg-primary"><?= $slider['item_count'] ?> Görsel</span>
                        </div>

                        <div class="small text-muted mb-3">
                            <div><strong>Tip:</strong> <?= ucfirst($slider['type']) ?></div>
                            <div><strong>Boyut:</strong> <?= $slider['width'] ?>x<?= $slider['height'] ?>px</div>
                            <div><strong>Animasyon:</strong> <?= ucfirst($slider['animation']) ?></div>
                            <div>
                                <strong>Özellikler:</strong>
                                <?php if ($slider['autoplay']): ?><span class="badge bg-info">Otomatik</span><?php endif; ?>
                                <?php if ($slider['show_arrows']): ?><span class="badge bg-secondary">Oklar</span><?php endif; ?>
                                <?php if ($slider['show_dots']): ?><span class="badge bg-secondary">Noktalar</span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100">
                            <a href="/admin/sliders/items.php?id=<?= $slider['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-images"></i> Görseller
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="editSlider(<?= htmlspecialchars(json_encode($slider)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $slider['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu slider\'ı silmek istediğinize emin misiniz?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Slider Modal -->
<div class="modal fade" id="sliderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="sliderForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="slider_id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Slider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Slider Adı <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="slider_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Slider Tipi</label>
                                <select name="type" id="slider_type" class="form-select">
                                    <option value="main">Ana Slider</option>
                                    <option value="mini">Mini Slider</option>
                                    <option value="popup">Popup Slider</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Genişlik (px)</label>
                                <input type="number" name="width" id="slider_width" class="form-control" value="1920">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Yükseklik (px)</label>
                                <input type="number" name="height" id="slider_height" class="form-control" value="600">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Otomatik Geçiş (ms)</label>
                                <input type="number" name="autoplay_speed" id="slider_speed" class="form-control" value="5000" step="500">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Animasyon Tipi</label>
                        <select name="animation" id="slider_animation" class="form-select">
                            <option value="slide">Kayma (Slide)</option>
                            <option value="fade">Solma (Fade)</option>
                            <option value="zoom">Yakınlaştırma (Zoom)</option>
                            <option value="flip">Çevirme (Flip)</option>
                            <option value="cube">Küp (Cube)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="autoplay" id="slider_autoplay" class="form-check-input" checked>
                                <label class="form-check-label" for="slider_autoplay">Otomatik Oynat</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="show_arrows" id="slider_arrows" class="form-check-input" checked>
                                <label class="form-check-label" for="slider_arrows">Okları Göster</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="show_dots" id="slider_dots" class="form-check-input" checked>
                                <label class="form-check-label" for="slider_dots">Noktaları Göster</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="status" id="slider_status" class="form-check-input" checked>
                                <label class="form-check-label" for="slider_status">Aktif</label>
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
function editSlider(slider) {
    document.getElementById('modalTitle').textContent = 'Slider Düzenle';
    document.getElementById('slider_id').value = slider.id;
    document.getElementById('slider_name').value = slider.name;
    document.getElementById('slider_type').value = slider.type;
    document.getElementById('slider_width').value = slider.width;
    document.getElementById('slider_height').value = slider.height;
    document.getElementById('slider_speed').value = slider.autoplay_speed;
    document.getElementById('slider_animation').value = slider.animation;
    document.getElementById('slider_autoplay').checked = slider.autoplay == 1;
    document.getElementById('slider_arrows').checked = slider.show_arrows == 1;
    document.getElementById('slider_dots').checked = slider.show_dots == 1;
    document.getElementById('slider_status').checked = slider.status == 1;

    new bootstrap.Modal(document.getElementById('sliderModal')).show();
}

document.getElementById('sliderModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Yeni Slider';
    document.getElementById('sliderForm').reset();
    document.getElementById('slider_id').value = '0';
    document.getElementById('slider_autoplay').checked = true;
    document.getElementById('slider_arrows').checked = true;
    document.getElementById('slider_dots').checked = true;
    document.getElementById('slider_status').checked = true;
});
</script>

<?php include '../includes/footer.php'; ?>
