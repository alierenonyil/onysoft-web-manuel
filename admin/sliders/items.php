<?php
/**
 * Slider Görselleri Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$slider_id = (int)($_GET['id'] ?? 0);
$slider = dbQueryOne("SELECT * FROM sliders WHERE id = ?", [$slider_id]);

if (!$slider) {
    setFlash('error', 'Slider bulunamadı.');
    redirect('/admin/sliders/');
}

$page_title = $slider['name'] . ' - Görseller';

// Upload klasörü oluştur
$upload_dir = UPLOAD_PATH . '/sliders';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Item Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        $id = (int)$_GET['delete'];
        $item = dbQueryOne("SELECT * FROM slider_items WHERE id = ? AND slider_id = ?", [$id, $slider_id]);

        if ($item) {
            if ($item['image'] && file_exists($upload_dir . '/' . $item['image'])) {
                unlink($upload_dir . '/' . $item['image']);
            }
            if ($item['image_mobile'] && file_exists($upload_dir . '/' . $item['image_mobile'])) {
                unlink($upload_dir . '/' . $item['image_mobile']);
            }
            dbDelete('slider_items', 'id = ?', [$id]);
            setFlash('success', 'Görsel silindi.');
        }
    }
    redirect('/admin/sliders/items.php?id=' . $slider_id);
}

// Item Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/sliders/items.php?id=' . $slider_id);
    }

    $id = (int)($_POST['id'] ?? 0);

    $data = [
        'slider_id' => $slider_id,
        'title' => sanitize($_POST['title']),
        'subtitle' => sanitize($_POST['subtitle']),
        'description' => sanitize($_POST['description']),
        'button_text' => sanitize($_POST['button_text']),
        'button_url' => sanitize($_POST['button_url']),
        'button_style' => sanitize($_POST['button_style']),
        'text_position' => sanitize($_POST['text_position']),
        'text_color' => sanitize($_POST['text_color']),
        'overlay_color' => sanitize($_POST['overlay_color']),
        'sort_order' => (int)$_POST['sort_order'],
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    // Ana görsel yükleme
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $filename = 'slider_' . time() . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $filename)) {
                // Eski görseli sil
                if ($id > 0) {
                    $old = dbQueryOne("SELECT image FROM slider_items WHERE id = ?", [$id]);
                    if ($old && $old['image'] && file_exists($upload_dir . '/' . $old['image'])) {
                        unlink($upload_dir . '/' . $old['image']);
                    }
                }
                $data['image'] = $filename;
            }
        }
    }

    // Mobil görsel yükleme
    if (isset($_FILES['image_mobile']) && $_FILES['image_mobile']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_mobile'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $filename = 'slider_mobile_' . time() . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $filename)) {
                if ($id > 0) {
                    $old = dbQueryOne("SELECT image_mobile FROM slider_items WHERE id = ?", [$id]);
                    if ($old && $old['image_mobile'] && file_exists($upload_dir . '/' . $old['image_mobile'])) {
                        unlink($upload_dir . '/' . $old['image_mobile']);
                    }
                }
                $data['image_mobile'] = $filename;
            }
        }
    }

    if ($id > 0) {
        dbUpdate('slider_items', $data, 'id = ?', [$id]);
        setFlash('success', 'Görsel güncellendi.');
    } else {
        if (empty($data['image'])) {
            setFlash('error', 'Lütfen bir görsel yükleyin.');
            redirect('/admin/sliders/items.php?id=' . $slider_id);
        }
        dbInsert('slider_items', $data);
        setFlash('success', 'Görsel eklendi.');
    }

    redirect('/admin/sliders/items.php?id=' . $slider_id);
}

// Görselleri Getir
$items = dbQuery("SELECT * FROM slider_items WHERE slider_id = ? ORDER BY sort_order", [$slider_id]);

include '../includes/header.php';
?>

<style>
.slider-item-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.slider-item-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.slider-item-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    padding: 20px 15px 15px;
}

.slider-item-overlay h6 {
    margin: 0 0 5px;
    font-size: 14px;
}

.slider-item-overlay small {
    opacity: 0.8;
}

.slider-item-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}

.slider-item-badge {
    position: absolute;
    top: 10px;
    left: 10px;
}

.color-preview {
    width: 30px;
    height: 30px;
    border-radius: 4px;
    border: 2px solid #ddd;
    cursor: pointer;
}
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-images"></i> <?= htmlspecialchars($slider['name']) ?> - Görseller
    </h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
            <i class="fas fa-plus"></i> Yeni Görsel
        </button>
        <a href="/admin/sliders/" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
</div>

<?php displayFlash(); ?>

<div class="row">
    <?php if (empty($items)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Henüz görsel eklenmemiş.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
                        <i class="fas fa-plus"></i> İlk Görseli Ekle
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card slider-item-card">
                    <img src="/uploads/sliders/<?= htmlspecialchars($item['image']) ?>" alt="">

                    <div class="slider-item-badge">
                        <span class="badge bg-<?= $item['status'] ? 'success' : 'danger' ?>">
                            <?= $item['status'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>

                    <div class="slider-item-actions">
                        <button type="button" class="btn btn-sm btn-warning" onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?id=<?= $slider_id ?>&delete=<?= $item['id'] ?>&token=<?= generateCsrfToken() ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>

                    <div class="slider-item-overlay">
                        <?php if ($item['title']): ?>
                            <h6><?= htmlspecialchars($item['title']) ?></h6>
                        <?php endif; ?>
                        <?php if ($item['subtitle']): ?>
                            <small><?= htmlspecialchars($item['subtitle']) ?></small>
                        <?php endif; ?>
                        <div class="mt-2">
                            <small class="text-white-50">Sıra: <?= $item['sort_order'] ?></small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="itemForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="item_id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalTitle">Yeni Görsel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Sol Kolon - Görseller -->
                        <div class="col-md-5">
                            <h6 class="mb-3">Görseller</h6>

                            <div class="mb-3">
                                <label class="form-label">Ana Görsel <span class="text-danger">*</span></label>
                                <input type="file" name="image" id="item_image_file" class="form-control" accept="image/*">
                                <small class="text-muted">Önerilen: <?= $slider['width'] ?>x<?= $slider['height'] ?>px</small>
                                <div id="current_image" class="mt-2" style="display: none;">
                                    <img src="" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mobil Görsel (Opsiyonel)</label>
                                <input type="file" name="image_mobile" class="form-control" accept="image/*">
                                <small class="text-muted">Mobil cihazlar için optimize edilmiş görsel</small>
                            </div>

                            <hr>

                            <h6 class="mb-3">Görünüm Ayarları</h6>

                            <div class="mb-3">
                                <label class="form-label">Metin Pozisyonu</label>
                                <select name="text_position" id="item_position" class="form-select">
                                    <option value="left">Sola Yasla</option>
                                    <option value="center">Ortala</option>
                                    <option value="right">Sağa Yasla</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Metin Rengi</label>
                                        <input type="color" name="text_color" id="item_text_color" class="form-control form-control-color" value="#ffffff">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Overlay Rengi</label>
                                        <input type="text" name="overlay_color" id="item_overlay" class="form-control" value="rgba(0,0,0,0.3)" placeholder="rgba(0,0,0,0.3)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sağ Kolon - İçerik -->
                        <div class="col-md-7">
                            <h6 class="mb-3">İçerik</h6>

                            <div class="mb-3">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="title" id="item_title" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alt Başlık</label>
                                <input type="text" name="subtitle" id="item_subtitle" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" id="item_description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Buton Metni</label>
                                        <input type="text" name="button_text" id="item_btn_text" class="form-control" placeholder="Satın Al">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Buton URL</label>
                                        <input type="text" name="button_url" id="item_btn_url" class="form-control" placeholder="/urun/...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Buton Stili</label>
                                        <select name="button_style" id="item_btn_style" class="form-select">
                                            <option value="primary">Primary</option>
                                            <option value="secondary">Secondary</option>
                                            <option value="success">Success</option>
                                            <option value="danger">Danger</option>
                                            <option value="warning">Warning</option>
                                            <option value="info">Info</option>
                                            <option value="light">Light</option>
                                            <option value="dark">Dark</option>
                                            <option value="outline-primary">Outline Primary</option>
                                            <option value="outline-light">Outline Light</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">Zamanlama</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Başlangıç Tarihi</label>
                                        <input type="datetime-local" name="start_date" id="item_start" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bitiş Tarihi</label>
                                        <input type="datetime-local" name="end_date" id="item_end" class="form-control">
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
function editItem(item) {
    document.getElementById('itemModalTitle').textContent = 'Görsel Düzenle';
    document.getElementById('item_id').value = item.id;
    document.getElementById('item_title').value = item.title || '';
    document.getElementById('item_subtitle').value = item.subtitle || '';
    document.getElementById('item_description').value = item.description || '';
    document.getElementById('item_btn_text').value = item.button_text || '';
    document.getElementById('item_btn_url').value = item.button_url || '';
    document.getElementById('item_btn_style').value = item.button_style || 'primary';
    document.getElementById('item_position').value = item.text_position || 'center';
    document.getElementById('item_text_color').value = item.text_color || '#ffffff';
    document.getElementById('item_overlay').value = item.overlay_color || 'rgba(0,0,0,0.3)';
    document.getElementById('item_order').value = item.sort_order || 0;
    document.getElementById('item_status').checked = item.status == 1;

    if (item.start_date) {
        document.getElementById('item_start').value = item.start_date.replace(' ', 'T');
    }
    if (item.end_date) {
        document.getElementById('item_end').value = item.end_date.replace(' ', 'T');
    }

    // Mevcut görseli göster
    if (item.image) {
        const imgDiv = document.getElementById('current_image');
        imgDiv.style.display = 'block';
        imgDiv.querySelector('img').src = '/uploads/sliders/' + item.image;
        document.getElementById('item_image_file').removeAttribute('required');
    }

    new bootstrap.Modal(document.getElementById('itemModal')).show();
}

document.getElementById('itemModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('itemModalTitle').textContent = 'Yeni Görsel';
    document.getElementById('itemForm').reset();
    document.getElementById('item_id').value = '0';
    document.getElementById('item_status').checked = true;
    document.getElementById('item_text_color').value = '#ffffff';
    document.getElementById('item_overlay').value = 'rgba(0,0,0,0.3)';
    document.getElementById('current_image').style.display = 'none';
});
</script>

<?php include '../includes/footer.php'; ?>
