<?php
/**
 * Genel Site Ayarları - Her Şey Yönetilebilir
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Genel Ayarlar';

// Ayarları Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Güvenlik hatası.');
        redirect('/admin/settings/general.php');
    }

    // Site ayarlarını güncelle
    foreach ($_POST['settings'] as $key => $value) {
        $exists = dbQueryOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            dbUpdate('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            dbInsert('site_settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => 'general'
            ]);
        }
    }

    setFlash('success', 'Ayarlar kaydedildi.');
    redirect('/admin/settings/general.php');
}

// Mevcut ayarları getir
$settings = [];
$result = dbQuery("SELECT setting_key, setting_value FROM site_settings WHERE setting_group IN ('general', 'social', 'features')");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Varsayılan değerler
$defaults = [
    'site_name' => 'E-Ticaret Sitem',
    'site_description' => 'Modern E-Ticaret Platformu',
    'site_email' => 'info@eticaret.com',
    'site_phone' => '+90 XXX XXX XX XX',
    'site_address' => '',
    'footer_text' => '© 2025 Tüm hakları saklıdır.',
    'free_shipping_threshold' => '500',
    'show_featured_section' => '1',
    'show_new_section' => '1',
    'show_bestseller_section' => '1',
    'show_special_section' => '1',
    'show_categories_section' => '1',
    'show_brands_section' => '0',
    'products_per_page' => '12',
    'facebook_url' => '',
    'instagram_url' => '',
    'twitter_url' => '',
    'youtube_url' => '',
    'whatsapp_number' => '',
    'enable_wishlist' => '1',
    'enable_compare' => '1',
    'enable_reviews' => '1',
    'maintenance_mode' => '0'
];

$settings = array_merge($defaults, $settings);

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-cog"></i> Genel Ayarlar</h1>
</div>

<?php displayFlash(); ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="row">
        <div class="col-lg-8">
            <!-- Site Bilgileri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Site Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Site Adı</label>
                        <input type="text" name="settings[site_name]" class="form-control" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site Açıklaması</label>
                        <input type="text" name="settings[site_description]" class="form-control" value="<?= htmlspecialchars($settings['site_description']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="settings[site_email]" class="form-control" value="<?= htmlspecialchars($settings['site_email']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="settings[site_phone]" class="form-control" value="<?= htmlspecialchars($settings['site_phone']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea name="settings[site_address]" class="form-control" rows="2"><?= htmlspecialchars($settings['site_address']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Footer Metni</label>
                        <input type="text" name="settings[footer_text]" class="form-control" value="<?= htmlspecialchars($settings['footer_text']) ?>">
                    </div>
                </div>
            </div>

            <!-- Ana Sayfa Bölümleri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-home"></i> Ana Sayfa Bölümleri</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Ana sayfada hangi bölümlerin gösterileceğini seçin</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_featured_section]" value="1" <?= $settings['show_featured_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Öne Çıkan Ürünler</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_new_section]" value="1" <?= $settings['show_new_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Yeni Ürünler</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_bestseller_section]" value="1" <?= $settings['show_bestseller_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Çok Satanlar</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_special_section]" value="1" <?= $settings['show_special_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">İndirimli Ürünler</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_categories_section]" value="1" <?= $settings['show_categories_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Kategoriler</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[show_brands_section]" value="1" <?= $settings['show_brands_section'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Markalar</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alışveriş Ayarları -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Alışveriş Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ücretsiz Kargo Limiti (TL)</label>
                            <input type="number" name="settings[free_shipping_threshold]" class="form-control" value="<?= htmlspecialchars($settings['free_shipping_threshold']) ?>">
                            <small class="text-muted">Bu tutarın üzeri ücretsiz kargo</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sayfa Başına Ürün</label>
                            <input type="number" name="settings[products_per_page]" class="form-control" value="<?= htmlspecialchars($settings['products_per_page']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Özellikler -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Özellikler</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[enable_wishlist]" value="1" <?= $settings['enable_wishlist'] ? 'checked' : '' ?>>
                                <label class="form-check-label">İstek Listesi</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[enable_compare]" value="1" <?= $settings['enable_compare'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Ürün Karşılaştır</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="settings[enable_reviews]" value="1" <?= $settings['enable_reviews'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Ürün Yorumları</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sosyal Medya -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fab fa-facebook"></i> Sosyal Medya</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                            <input type="url" name="settings[facebook_url]" class="form-control" value="<?= htmlspecialchars($settings['facebook_url']) ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                            <input type="url" name="settings[instagram_url]" class="form-control" value="<?= htmlspecialchars($settings['instagram_url']) ?>" placeholder="https://instagram.com/...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fab fa-twitter text-info"></i> Twitter</label>
                            <input type="url" name="settings[twitter_url]" class="form-control" value="<?= htmlspecialchars($settings['twitter_url']) ?>" placeholder="https://twitter.com/...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fab fa-youtube text-danger"></i> YouTube</label>
                            <input type="url" name="settings[youtube_url]" class="form-control" value="<?= htmlspecialchars($settings['youtube_url']) ?>" placeholder="https://youtube.com/...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fab fa-whatsapp text-success"></i> WhatsApp</label>
                            <input type="text" name="settings[whatsapp_number]" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_number']) ?>" placeholder="+90 5XX XXX XX XX">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bakım Modu -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-wrench"></i> Bakım Modu</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" value="1" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Bakım Modunu Aktifleştir</label>
                    </div>
                    <small class="text-muted">Bakım modu aktifken sadece admin girişi yapabilir</small>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon -->
        <div class="col-lg-4">
            <div class="card position-sticky" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-save"></i> İşlemler</h5>
                </div>
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> <strong>Bilgi:</strong>
                        <small class="d-block mt-2">
                            Bu sayfadan sitenizin genel ayarlarını yönetebilirsiniz. Değişiklikler hemen siteye yansıyacaktır.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
