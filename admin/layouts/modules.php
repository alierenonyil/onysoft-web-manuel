<?php
/**
 * Layout Modülleri - Sürükle-Bırak Widget Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$layout_id = (int)($_GET['id'] ?? 0);
$layout = dbQueryOne("SELECT * FROM layouts WHERE id = ?", [$layout_id]);

if (!$layout) {
    setFlash('error', 'Düzen bulunamadı.');
    redirect('/admin/layouts/');
}

$page_title = 'Modüller: ' . $layout['name'];

// Modül Kaydetme (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası']);
        exit;
    }

    $action = $_POST['action'];

    // Modül Ekleme
    if ($action === 'add') {
        $data = [
            'layout_id' => $layout_id,
            'module_type' => sanitize($_POST['module_type']),
            'position' => sanitize($_POST['position']),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'settings' => json_encode($_POST['settings'] ?? []),
            'status' => 1
        ];

        $id = dbInsert('layout_modules', $data);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    // Modül Güncelleme
    if ($action === 'update') {
        $id = (int)$_POST['module_id'];
        $data = [
            'position' => sanitize($_POST['position']),
            'sort_order' => (int)$_POST['sort_order'],
            'settings' => json_encode($_POST['settings'] ?? []),
            'status' => isset($_POST['status']) ? 1 : 0
        ];

        dbUpdate('layout_modules', $data, 'id = ? AND layout_id = ?', [$id, $layout_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Modül Silme
    if ($action === 'delete') {
        $id = (int)$_POST['module_id'];
        dbDelete('layout_modules', 'id = ? AND layout_id = ?', [$id, $layout_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Sıralama Güncelleme
    if ($action === 'reorder') {
        $modules = $_POST['modules'] ?? [];
        foreach ($modules as $position => $items) {
            foreach ($items as $order => $module_id) {
                dbUpdate('layout_modules', [
                    'position' => $position,
                    'sort_order' => $order
                ], 'id = ? AND layout_id = ?', [(int)$module_id, $layout_id]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Mevcut Modülleri Getir
$modules = dbQuery("SELECT * FROM layout_modules WHERE layout_id = ? ORDER BY position, sort_order", [$layout_id]);

// Modül Tipleri
$module_types = [
    'slider' => ['name' => 'Slider', 'icon' => 'images', 'color' => '#3b82f6'],
    'banner' => ['name' => 'Banner', 'icon' => 'image', 'color' => '#8b5cf6'],
    'featured_products' => ['name' => 'Öne Çıkan Ürünler', 'icon' => 'star', 'color' => '#f59e0b'],
    'new_products' => ['name' => 'Yeni Ürünler', 'icon' => 'sparkles', 'color' => '#10b981'],
    'bestsellers' => ['name' => 'Çok Satanlar', 'icon' => 'fire', 'color' => '#ef4444'],
    'special_offers' => ['name' => 'İndirimli Ürünler', 'icon' => 'tag', 'color' => '#ec4899'],
    'categories' => ['name' => 'Kategoriler', 'icon' => 'folder', 'color' => '#6366f1'],
    'brands' => ['name' => 'Markalar', 'icon' => 'award', 'color' => '#14b8a6'],
    'html' => ['name' => 'HTML İçerik', 'icon' => 'code', 'color' => '#64748b'],
    'newsletter' => ['name' => 'Bülten Formu', 'icon' => 'envelope', 'color' => '#0ea5e9'],
    'testimonials' => ['name' => 'Müşteri Yorumları', 'icon' => 'quote-left', 'color' => '#a855f7'],
    'features' => ['name' => 'Özellikler', 'icon' => 'check-circle', 'color' => '#22c55e'],
    'countdown' => ['name' => 'Geri Sayım', 'icon' => 'clock', 'color' => '#f97316']
];

// Pozisyonlar
$positions = [
    'top' => 'Üst Alan',
    'content_top' => 'İçerik Üstü',
    'left' => 'Sol Kolon',
    'content' => 'Ana İçerik',
    'right' => 'Sağ Kolon',
    'content_bottom' => 'İçerik Altı',
    'bottom' => 'Alt Alan'
];

include '../includes/header.php';
?>

<style>
.module-builder {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
    min-height: 600px;
}

.module-palette {
    background: #f8fafc;
    border-radius: 8px;
    padding: 15px;
}

.module-palette h6 {
    font-weight: 600;
    margin-bottom: 15px;
    color: #374151;
}

.module-item {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    cursor: grab;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.module-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.module-item:active {
    cursor: grabbing;
}

.module-item i {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: white;
    font-size: 14px;
}

.module-item span {
    font-size: 13px;
    font-weight: 500;
}

.layout-preview {
    background: white;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    overflow: hidden;
}

.layout-header {
    background: #f1f5f9;
    padding: 10px 15px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    font-size: 14px;
}

.position-zone {
    min-height: 80px;
    padding: 10px;
    border: 2px dashed #d1d5db;
    border-radius: 6px;
    margin: 10px;
    background: #fafafa;
    transition: all 0.2s;
}

.position-zone.drag-over {
    border-color: #3b82f6;
    background: #eff6ff;
}

.position-zone .zone-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.placed-module {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: move;
}

.placed-module:last-child {
    margin-bottom: 0;
}

.placed-module .module-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.placed-module .module-info i {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: white;
    font-size: 12px;
}

.placed-module .module-name {
    font-size: 13px;
    font-weight: 500;
}

.placed-module .module-actions {
    display: flex;
    gap: 5px;
}

.placed-module .module-actions button {
    padding: 4px 8px;
    font-size: 12px;
}

.content-row {
    display: grid;
    grid-template-columns: 200px 1fr 200px;
    gap: 10px;
    padding: 10px;
}

.layout-section {
    padding: 10px;
}

@media (max-width: 1200px) {
    .module-builder {
        grid-template-columns: 1fr;
    }

    .content-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-puzzle-piece"></i> <?= htmlspecialchars($layout['name']) ?> - Modüller
    </h1>
    <div>
        <button type="button" class="btn btn-success" onclick="saveLayout()">
            <i class="fas fa-save"></i> Kaydet
        </button>
        <a href="/admin/layouts/" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
</div>

<?php displayFlash(); ?>

<div class="module-builder">
    <!-- Modül Paleti -->
    <div class="module-palette">
        <h6><i class="fas fa-th-large"></i> Modüller</h6>
        <p class="text-muted small mb-3">Modülleri sürükleyip bırakın</p>

        <?php foreach ($module_types as $type => $info): ?>
            <div class="module-item" draggable="true" data-type="<?= $type ?>">
                <i class="fas fa-<?= $info['icon'] ?>" style="background: <?= $info['color'] ?>"></i>
                <span><?= $info['name'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Layout Önizleme -->
    <div class="layout-preview">
        <div class="layout-header">
            <i class="fas fa-desktop"></i> Sayfa Düzeni
        </div>

        <!-- Üst Alan -->
        <div class="layout-section">
            <div class="position-zone" data-position="top">
                <div class="zone-label">Üst Alan</div>
                <div class="modules-container"></div>
            </div>
        </div>

        <!-- İçerik Üstü -->
        <div class="layout-section">
            <div class="position-zone" data-position="content_top">
                <div class="zone-label">İçerik Üstü</div>
                <div class="modules-container"></div>
            </div>
        </div>

        <!-- 3 Kolonlu Alan -->
        <div class="content-row">
            <div class="position-zone" data-position="left">
                <div class="zone-label">Sol Kolon</div>
                <div class="modules-container"></div>
            </div>
            <div class="position-zone" data-position="content">
                <div class="zone-label">Ana İçerik</div>
                <div class="modules-container"></div>
            </div>
            <div class="position-zone" data-position="right">
                <div class="zone-label">Sağ Kolon</div>
                <div class="modules-container"></div>
            </div>
        </div>

        <!-- İçerik Altı -->
        <div class="layout-section">
            <div class="position-zone" data-position="content_bottom">
                <div class="zone-label">İçerik Altı</div>
                <div class="modules-container"></div>
            </div>
        </div>

        <!-- Alt Alan -->
        <div class="layout-section">
            <div class="position-zone" data-position="bottom">
                <div class="zone-label">Alt Alan</div>
                <div class="modules-container"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modül Ayarları Modal -->
<div class="modal fade" id="moduleSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modül Ayarları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="moduleSettingsBody">
                <!-- Dinamik içerik -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveModuleSettings()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrf_token = '<?= generateCsrfToken() ?>';
const layout_id = <?= $layout_id ?>;
const moduleTypes = <?= json_encode($module_types) ?>;
let currentEditingModule = null;

// Mevcut modülleri yükle
const existingModules = <?= json_encode($modules) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Mevcut modülleri yerleştir
    existingModules.forEach(module => {
        const zone = document.querySelector(`[data-position="${module.position}"] .modules-container`);
        if (zone) {
            zone.appendChild(createPlacedModule(module));
        }
    });

    // Drag & Drop için event listener'lar
    initDragDrop();
});

function initDragDrop() {
    // Modül paleti - drag start
    document.querySelectorAll('.module-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('moduleType', this.dataset.type);
            e.dataTransfer.setData('isNew', 'true');
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });

    // Pozisyon zonları
    document.querySelectorAll('.position-zone').forEach(zone => {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const isNew = e.dataTransfer.getData('isNew') === 'true';
            const container = this.querySelector('.modules-container');

            if (isNew) {
                const moduleType = e.dataTransfer.getData('moduleType');
                addModule(moduleType, this.dataset.position, container);
            } else {
                const moduleId = e.dataTransfer.getData('moduleId');
                const moduleElement = document.querySelector(`[data-module-id="${moduleId}"]`);
                if (moduleElement) {
                    container.appendChild(moduleElement);
                }
            }
        });
    });
}

function addModule(type, position, container) {
    const module = {
        id: 'temp_' + Date.now(),
        module_type: type,
        position: position,
        settings: {},
        status: 1
    };

    // API'ye kaydet
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('csrf_token', csrf_token);
    formData.append('module_type', type);
    formData.append('position', position);
    formData.append('sort_order', container.children.length);

    fetch('/admin/layouts/modules.php?id=' + layout_id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            module.id = data.id;
            container.appendChild(createPlacedModule(module));
        }
    });
}

function createPlacedModule(module) {
    const info = moduleTypes[module.module_type] || {name: module.module_type, icon: 'cube', color: '#6b7280'};

    const div = document.createElement('div');
    div.className = 'placed-module';
    div.draggable = true;
    div.dataset.moduleId = module.id;

    div.innerHTML = `
        <div class="module-info">
            <i class="fas fa-${info.icon}" style="background: ${info.color}"></i>
            <span class="module-name">${info.name}</span>
        </div>
        <div class="module-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editModule(${module.id}, '${module.module_type}')">
                <i class="fas fa-cog"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteModule(${module.id}, this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    // Drag events
    div.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('moduleId', module.id);
        e.dataTransfer.setData('isNew', 'false');
    });

    return div;
}

function editModule(id, type) {
    currentEditingModule = id;

    const info = moduleTypes[type];
    let settingsHtml = '';

    // Modül tipine göre ayarlar
    switch(type) {
        case 'slider':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">Slider Seçin</label>
                    <select class="form-select" name="slider_id">
                        <option value="1">Ana Sayfa Slider</option>
                    </select>
                </div>
            `;
            break;

        case 'featured_products':
        case 'new_products':
        case 'bestsellers':
        case 'special_offers':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">Gösterilecek Ürün Sayısı</label>
                    <input type="number" class="form-control" name="limit" value="8" min="1" max="24">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kolon Sayısı</label>
                    <select class="form-select" name="columns">
                        <option value="3">3 Kolon</option>
                        <option value="4" selected>4 Kolon</option>
                        <option value="5">5 Kolon</option>
                        <option value="6">6 Kolon</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" class="form-control" name="title" value="${info.name}">
                </div>
            `;
            break;

        case 'categories':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">Görünüm Tipi</label>
                    <select class="form-select" name="view_type">
                        <option value="grid">Grid</option>
                        <option value="list">Liste</option>
                        <option value="carousel">Carousel</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gösterilecek Kategori Sayısı</label>
                    <input type="number" class="form-control" name="limit" value="6" min="1" max="12">
                </div>
            `;
            break;

        case 'html':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">HTML İçerik</label>
                    <textarea class="form-control" name="html_content" rows="10"></textarea>
                </div>
            `;
            break;

        case 'banner':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">Banner Grubu</label>
                    <select class="form-select" name="banner_id">
                        <option value="">Seçin...</option>
                    </select>
                </div>
            `;
            break;

        case 'countdown':
            settingsHtml = `
                <div class="mb-3">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="datetime-local" class="form-control" name="end_date">
                </div>
                <div class="mb-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" class="form-control" name="title" value="Kampanya Bitimine">
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <input type="text" class="form-control" name="description">
                </div>
            `;
            break;

        default:
            settingsHtml = '<p class="text-muted">Bu modül için ayar bulunmuyor.</p>';
    }

    document.getElementById('moduleSettingsBody').innerHTML = settingsHtml;
    new bootstrap.Modal(document.getElementById('moduleSettingsModal')).show();
}

function saveModuleSettings() {
    const modal = document.getElementById('moduleSettingsModal');
    const form = modal.querySelector('.modal-body');
    const settings = {};

    form.querySelectorAll('input, select, textarea').forEach(input => {
        settings[input.name] = input.value;
    });

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('csrf_token', csrf_token);
    formData.append('module_id', currentEditingModule);

    const moduleElement = document.querySelector(`[data-module-id="${currentEditingModule}"]`);
    if (moduleElement) {
        formData.append('position', moduleElement.closest('.position-zone').dataset.position);
        formData.append('sort_order', Array.from(moduleElement.parentNode.children).indexOf(moduleElement));
    }

    // Settings'i formData'ya ekle
    Object.keys(settings).forEach(key => {
        formData.append(`settings[${key}]`, settings[key]);
    });

    fetch('/admin/layouts/modules.php?id=' + layout_id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(modal).hide();
        }
    });
}

function deleteModule(id, btn) {
    if (!confirm('Bu modülü silmek istediğinize emin misiniz?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('csrf_token', csrf_token);
    formData.append('module_id', id);

    fetch('/admin/layouts/modules.php?id=' + layout_id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.closest('.placed-module').remove();
        }
    });
}

function saveLayout() {
    const modules = {};

    document.querySelectorAll('.position-zone').forEach(zone => {
        const position = zone.dataset.position;
        const items = [];

        zone.querySelectorAll('.placed-module').forEach(module => {
            items.push(module.dataset.moduleId);
        });

        modules[position] = items;
    });

    const formData = new FormData();
    formData.append('action', 'reorder');
    formData.append('csrf_token', csrf_token);

    Object.keys(modules).forEach(position => {
        modules[position].forEach((id, order) => {
            formData.append(`modules[${position}][]`, id);
        });
    });

    fetch('/admin/layouts/modules.php?id=' + layout_id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Düzen kaydedildi!');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
