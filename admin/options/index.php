<?php
/**
 * Ürün Seçenekleri Yönetimi (Renk, Beden vs.)
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Seçenek Yönetimi';

// Silme
if (isset($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        dbDelete('options', 'id = ?', [(int)$_GET['delete']]);
        setFlash('success', 'Seçenek silindi.');
    }
    redirect('/admin/options/');
}

// Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/options/');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'name' => sanitize($_POST['name']),
        'type' => sanitize($_POST['type']),
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    if ($id > 0) {
        dbUpdate('options', $data, 'id = ?', [$id]);
        setFlash('success', 'Seçenek güncellendi.');
    } else {
        $id = dbInsert('options', $data);
        setFlash('success', 'Seçenek eklendi.');
    }

    // Değerleri kaydet
    if (isset($_POST['values'])) {
        // Mevcut değerleri sil
        dbDelete('option_values', 'option_id = ?', [$id]);

        foreach ($_POST['values'] as $i => $value) {
            if (!empty($value)) {
                dbInsert('option_values', [
                    'option_id' => $id,
                    'name' => sanitize($value),
                    'sort_order' => $i
                ]);
            }
        }
    }

    redirect('/admin/options/');
}

$options = dbQuery("SELECT o.*, (SELECT COUNT(*) FROM option_values WHERE option_id = o.id) as value_count FROM options o ORDER BY o.sort_order");

$types = [
    'select' => 'Seçim Kutusu',
    'radio' => 'Radyo Butonu',
    'checkbox' => 'Onay Kutusu',
    'text' => 'Metin',
    'textarea' => 'Metin Alanı',
    'color' => 'Renk Seçici',
    'date' => 'Tarih'
];

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-sliders-h"></i> Seçenek Yönetimi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#optionModal">
        <i class="fas fa-plus"></i> Yeni Seçenek
    </button>
</div>

<?php displayFlash(); ?>

<div class="row">
    <?php foreach ($options as $option): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($option['name']) ?></h5>
                    <span class="badge bg-<?= $option['status'] ? 'success' : 'danger' ?>">
                        <?= $option['status'] ? 'Aktif' : 'Pasif' ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <strong>Tip:</strong> <?= $types[$option['type']] ?? $option['type'] ?>
                    </p>
                    <p class="mb-0">
                        <strong>Değerler:</strong> <?= $option['value_count'] ?> adet
                    </p>

                    <?php
                    $values = dbQuery("SELECT * FROM option_values WHERE option_id = ? ORDER BY sort_order LIMIT 5", [$option['id']]);
                    if ($values):
                    ?>
                        <div class="mt-2">
                            <?php foreach ($values as $val): ?>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($val['name']) ?></span>
                            <?php endforeach; ?>
                            <?php if ($option['value_count'] > 5): ?>
                                <span class="badge bg-light text-dark">+<?= $option['value_count'] - 5 ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-warning" onclick="editOption(<?= $option['id'] ?>)">
                        <i class="fas fa-edit"></i> Düzenle
                    </button>
                    <a href="?delete=<?= $option['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="optionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="optionForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" id="option_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Seçenek Ekle/Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Seçenek Adı *</label>
                                <input type="text" name="name" id="option_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tip</label>
                                <select name="type" id="option_type" class="form-select">
                                    <?php foreach ($types as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sıra</label>
                                <input type="number" name="sort_order" id="option_order" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="status" id="option_status" class="form-check-input" checked>
                                <label class="form-check-label" for="option_status">Aktif</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6>Seçenek Değerleri</h6>
                    <div id="valuesContainer">
                        <div class="input-group mb-2">
                            <input type="text" name="values[]" class="form-control" placeholder="Değer...">
                            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addValue()">
                        <i class="fas fa-plus"></i> Değer Ekle
                    </button>
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
function addValue() {
    const container = document.getElementById('valuesContainer');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="values[]" class="form-control" placeholder="Değer...">
        <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function editOption(id) {
    fetch('/admin/options/get.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            document.getElementById('option_id').value = data.option.id;
            document.getElementById('option_name').value = data.option.name;
            document.getElementById('option_type').value = data.option.type;
            document.getElementById('option_order').value = data.option.sort_order;
            document.getElementById('option_status').checked = data.option.status == 1;

            const container = document.getElementById('valuesContainer');
            container.innerHTML = '';

            data.values.forEach(val => {
                const div = document.createElement('div');
                div.className = 'input-group mb-2';
                div.innerHTML = `
                    <input type="text" name="values[]" class="form-control" value="${val.name}">
                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(div);
            });

            if (data.values.length === 0) addValue();

            new bootstrap.Modal(document.getElementById('optionModal')).show();
        });
}
</script>

<?php include '../includes/footer.php'; ?>
