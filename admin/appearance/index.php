<?php
/**
 * Tema ve Görünüm Ayarları
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Tema Ayarları';

// Ayarları Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/appearance/');
    }

    // Tema ayarlarını güncelle
    foreach ($_POST['settings'] as $key => $value) {
        $exists = dbQueryOne("SELECT id FROM theme_settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            dbUpdate('theme_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            dbInsert('theme_settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }

    // Logo yükleme
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_PATH . '/theme';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $filename = 'logo_' . time() . '.' . $ext;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . '/' . $filename)) {
            $exists = dbQueryOne("SELECT id FROM theme_settings WHERE setting_key = 'logo'", []);
            if ($exists) {
                dbUpdate('theme_settings', ['setting_value' => $filename], 'setting_key = ?', ['logo']);
            } else {
                dbInsert('theme_settings', ['setting_key' => 'logo', 'setting_value' => $filename]);
            }
        }
    }

    // Favicon yükleme
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_PATH . '/theme';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        $filename = 'favicon_' . time() . '.' . $ext;

        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_dir . '/' . $filename)) {
            $exists = dbQueryOne("SELECT id FROM theme_settings WHERE setting_key = 'favicon'", []);
            if ($exists) {
                dbUpdate('theme_settings', ['setting_value' => $filename], 'setting_key = ?', ['favicon']);
            } else {
                dbInsert('theme_settings', ['setting_key' => 'favicon', 'setting_value' => $filename]);
            }
        }
    }

    setFlash('success', 'Tema ayarları güncellendi.');
    redirect('/admin/appearance/');
}

// Mevcut ayarları getir
$settings = [];
$result = dbQuery("SELECT setting_key, setting_value FROM theme_settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Varsayılan değerler
$defaults = [
    'primary_color' => '#2563eb',
    'secondary_color' => '#7c3aed',
    'accent_color' => '#f59e0b',
    'text_color' => '#1f2937',
    'background_color' => '#f9fafb',
    'header_bg' => '#ffffff',
    'header_text' => '#1f2937',
    'footer_bg' => '#1f2937',
    'footer_text' => '#ffffff',
    'font_family' => 'Inter',
    'border_radius' => '8'
];

$settings = array_merge($defaults, $settings);

include '../includes/header.php';
?>

<style>
.color-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
.color-input-group input[type="color"] {
    width: 50px;
    height: 38px;
    padding: 2px;
    border-radius: 4px;
}
.color-input-group input[type="text"] {
    flex: 1;
}
.preview-box {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 30px;
    border-radius: var(--radius);
    margin-bottom: 20px;
}
.preview-box h3 {
    margin: 0 0 10px;
}
.preview-box .btn-preview {
    background: var(--accent);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: var(--radius);
    cursor: pointer;
}
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-palette"></i> Tema Ayarları</h1>
</div>

<?php displayFlash(); ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="row">
        <!-- Sol Kolon - Ayarlar -->
        <div class="col-lg-8">
            <!-- Renkler -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tint"></i> Renkler</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ana Renk (Primary)</label>
                            <div class="color-input-group">
                                <input type="color" id="primary_color" value="<?= $settings['primary_color'] ?>" onchange="document.getElementById('primary_text').value = this.value; updatePreview()">
                                <input type="text" name="settings[primary_color]" id="primary_text" class="form-control" value="<?= $settings['primary_color'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İkincil Renk (Secondary)</label>
                            <div class="color-input-group">
                                <input type="color" id="secondary_color" value="<?= $settings['secondary_color'] ?>" onchange="document.getElementById('secondary_text').value = this.value; updatePreview()">
                                <input type="text" name="settings[secondary_color]" id="secondary_text" class="form-control" value="<?= $settings['secondary_color'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vurgu Rengi (Accent)</label>
                            <div class="color-input-group">
                                <input type="color" id="accent_color" value="<?= $settings['accent_color'] ?>" onchange="document.getElementById('accent_text').value = this.value; updatePreview()">
                                <input type="text" name="settings[accent_color]" id="accent_text" class="form-control" value="<?= $settings['accent_color'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Metin Rengi</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['text_color'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[text_color]" class="form-control" value="<?= $settings['text_color'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arka Plan Rengi</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['background_color'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[background_color]" class="form-control" value="<?= $settings['background_color'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header & Footer -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-columns"></i> Header & Footer</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Header Arka Plan</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['header_bg'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[header_bg]" class="form-control" value="<?= $settings['header_bg'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Header Metin</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['header_text'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[header_text]" class="form-control" value="<?= $settings['header_text'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Footer Arka Plan</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['footer_bg'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[footer_bg]" class="form-control" value="<?= $settings['footer_bg'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Footer Metin</label>
                            <div class="color-input-group">
                                <input type="color" value="<?= $settings['footer_text'] ?>" onchange="this.nextElementSibling.value = this.value">
                                <input type="text" name="settings[footer_text]" class="form-control" value="<?= $settings['footer_text'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tipografi -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-font"></i> Tipografi & Genel</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Font Ailesi</label>
                            <select name="settings[font_family]" class="form-select">
                                <?php
                                $fonts = ['Inter', 'Roboto', 'Open Sans', 'Poppins', 'Montserrat', 'Lato', 'Nunito', 'Raleway'];
                                foreach ($fonts as $font):
                                ?>
                                    <option value="<?= $font ?>" <?= $settings['font_family'] === $font ? 'selected' : '' ?>><?= $font ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Köşe Yuvarlaklığı (px)</label>
                            <input type="number" name="settings[border_radius]" class="form-control" value="<?= $settings['border_radius'] ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo & Favicon -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Logo & Favicon</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Logo</label>
                            <?php if (!empty($settings['logo'])): ?>
                                <div class="mb-2">
                                    <img src="/uploads/theme/<?= htmlspecialchars($settings['logo']) ?>" style="max-height: 60px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Favicon</label>
                            <?php if (!empty($settings['favicon'])): ?>
                                <div class="mb-2">
                                    <img src="/uploads/theme/<?= htmlspecialchars($settings['favicon']) ?>" style="max-height: 32px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="favicon" class="form-control" accept="image/*,.ico">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon - Önizleme -->
        <div class="col-lg-4">
            <div class="card position-sticky" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye"></i> Önizleme</h5>
                </div>
                <div class="card-body">
                    <div class="preview-box" id="previewBox" style="--primary: <?= $settings['primary_color'] ?>; --secondary: <?= $settings['secondary_color'] ?>; --accent: <?= $settings['accent_color'] ?>; --radius: <?= $settings['border_radius'] ?>px;">
                        <h3>Merhaba!</h3>
                        <p>Bu bir önizleme metnidir.</p>
                        <button class="btn-preview">Buton</button>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function updatePreview() {
    const box = document.getElementById('previewBox');
    box.style.setProperty('--primary', document.getElementById('primary_text').value);
    box.style.setProperty('--secondary', document.getElementById('secondary_text').value);
    box.style.setProperty('--accent', document.getElementById('accent_text').value);
}
</script>

<?php include '../includes/footer.php'; ?>
