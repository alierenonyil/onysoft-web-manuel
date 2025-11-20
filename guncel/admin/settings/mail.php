<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Mail Ayarları';

if (isPost() && verifyCsrfToken(post('csrf_token'))) {
    $action = post('action');

    if ($action === 'update_settings') {
        // Update SMTP settings
        $smtpSettings = [
            'smtp_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_from_email',
            'smtp_from_name',
            'smtp_debug'
        ];

        foreach ($smtpSettings as $key) {
            $value = post($key, '');
            updateSetting($key, $value);
        }

        setFlash('mail_settings', 'Mail ayarları güncellendi', 'success');
        redirect(siteUrl('admin/settings/mail.php'));

    } elseif ($action === 'test_email') {
        // Send test email
        $testEmail = post('test_email');

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            setFlash('mail_settings', 'Geçerli bir e-posta adresi girin', 'error');
        } else {
            $subject = 'Test Email - ' . SITE_NAME;
            $message = getEmailTemplate(
                $subject,
                '<h2>Test Email</h2>
                <p>Bu bir test e-postasıdır. SMTP ayarlarınız doğru çalışıyor!</p>
                <p><strong>Gönderim Zamanı:</strong> ' . date('d.m.Y H:i:s') . '</p>
                <p><strong>SMTP Aktif:</strong> ' . (getSetting('smtp_enabled') == '1' ? 'Evet' : 'Hayır (PHP mail() kullanılıyor)') . '</p>'
            );

            $sent = sendEmail($testEmail, $subject, $message);

            if ($sent) {
                setFlash('mail_settings', 'Test e-postası gönderildi: ' . $testEmail, 'success');
            } else {
                setFlash('mail_settings', 'Test e-postası gönderilemedi. Lütfen ayarları kontrol edin.', 'error');
            }
        }

        redirect(siteUrl('admin/settings/mail.php'));
    }
}

// Get current SMTP settings
$smtpEnabled = getSetting('smtp_enabled', '0');
$smtpHost = getSetting('smtp_host', 'smtp.gmail.com');
$smtpPort = getSetting('smtp_port', '587');
$smtpUsername = getSetting('smtp_username', '');
$smtpPassword = getSetting('smtp_password', '');
$smtpEncryption = getSetting('smtp_encryption', 'tls');
$smtpFromEmail = getSetting('smtp_from_email', MAIL_FROM);
$smtpFromName = getSetting('smtp_from_name', MAIL_FROM_NAME);
$smtpDebug = getSetting('smtp_debug', '0');

require_once '../includes/header.php';
?>

<style>
.setting-card {
    transition: all 0.3s;
}
.setting-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.password-toggle {
    cursor: pointer;
    position: absolute;
    right: 10px;
    top: 38px;
    color: #666;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-envelope-open-text"></i> Mail Ayarları</h2>
        <p class="text-muted mb-0">SMTP mail sunucu ayarlarını yönetin</p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Tüm Ayarlar
    </a>
</div>

<?php displayFlash('mail_settings'); ?>

<div class="row g-4">
    <!-- SMTP Settings Form -->
    <div class="col-lg-8">
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_settings">

            <div class="card setting-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-server"></i> SMTP Sunucu Ayarları</h5>
                </div>
                <div class="card-body">
                    <!-- SMTP Enable/Disable -->
                    <div class="mb-4 p-3 bg-light rounded">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="smtp_enabled" value="1" class="form-check-input" id="smtpEnabled"
                                <?php echo $smtpEnabled == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="smtpEnabled">
                                <strong>SMTP'yi Etkinleştir</strong>
                                <small class="d-block text-muted">Kapalıysa PHP mail() fonksiyonu kullanılır</small>
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_host" class="form-control"
                                value="<?php echo htmlspecialchars($smtpHost); ?>"
                                placeholder="smtp.gmail.com" required>
                            <small class="text-muted">
                                Örnekler: smtp.gmail.com, smtp.yandex.com, mail.siteniz.com
                            </small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port <span class="text-danger">*</span></label>
                            <input type="number" name="smtp_port" class="form-control"
                                value="<?php echo htmlspecialchars($smtpPort); ?>"
                                placeholder="587" required>
                            <small class="text-muted">TLS: 587, SSL: 465</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Şifreleme Türü</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="tls" <?php echo $smtpEncryption == 'tls' ? 'selected' : ''; ?>>TLS (Önerilen)</option>
                            <option value="ssl" <?php echo $smtpEncryption == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo $smtpEncryption == 'none' ? 'selected' : ''; ?>>Şifreleme Yok</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP Kullanıcı Adı (Email)</label>
                        <input type="email" name="smtp_username" class="form-control"
                            value="<?php echo htmlspecialchars($smtpUsername); ?>"
                            placeholder="email@domain.com">
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label">SMTP Şifre</label>
                        <input type="password" name="smtp_password" id="smtpPassword" class="form-control"
                            value="<?php echo htmlspecialchars($smtpPassword); ?>"
                            placeholder="••••••••">
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('smtpPassword')"></i>
                    </div>
                </div>
            </div>

            <div class="card setting-card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Gönderici Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Gönderici Email <span class="text-danger">*</span></label>
                        <input type="email" name="smtp_from_email" class="form-control"
                            value="<?php echo htmlspecialchars($smtpFromEmail); ?>"
                            placeholder="noreply@domain.com" required>
                        <small class="text-muted">Bu adres gönderilen emaillerde görünür</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gönderici İsmi <span class="text-danger">*</span></label>
                        <input type="text" name="smtp_from_name" class="form-control"
                            value="<?php echo htmlspecialchars($smtpFromName); ?>"
                            placeholder="<?php echo SITE_NAME; ?>" required>
                    </div>
                </div>
            </div>

            <div class="card setting-card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-bug"></i> Debug & Geliştirici Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="smtp_debug" value="1" class="form-check-input" id="smtpDebug"
                            <?php echo $smtpDebug == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="smtpDebug">
                            <strong>Debug Modu</strong>
                            <small class="d-block text-muted">SMTP hatalarını error.log dosyasına kaydet</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Test Email & Info -->
    <div class="col-lg-4">
        <!-- Test Email -->
        <div class="card setting-card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-vial"></i> Test Email Gönder</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="test_email">

                    <div class="mb-3">
                        <label class="form-label">Test Email Adresi</label>
                        <input type="email" name="test_email" class="form-control"
                            placeholder="test@example.com" required>
                    </div>

                    <button type="submit" class="btn btn-info w-100">
                        <i class="fas fa-paper-plane"></i> Test Emaili Gönder
                    </button>

                    <div class="alert alert-light mt-3 mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Test emaili mevcut ayarlarla gönderilecektir.
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <!-- SMTP Provider Examples -->
        <div class="card setting-card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-book"></i> Popüler SMTP Ayarları</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><i class="fab fa-google"></i> Gmail</strong>
                    <small class="d-block text-muted">
                        Host: smtp.gmail.com<br>
                        Port: 587 (TLS)<br>
                        <a href="https://myaccount.google.com/apppasswords" target="_blank">Uygulama Şifresi Oluştur</a>
                    </small>
                </div>

                <div class="mb-3">
                    <strong><i class="fas fa-envelope"></i> Yandex</strong>
                    <small class="d-block text-muted">
                        Host: smtp.yandex.com<br>
                        Port: 587 (TLS)
                    </small>
                </div>

                <div class="mb-3">
                    <strong><i class="fab fa-microsoft"></i> Outlook/Hotmail</strong>
                    <small class="d-block text-muted">
                        Host: smtp-mail.outlook.com<br>
                        Port: 587 (TLS)
                    </small>
                </div>

                <div>
                    <strong><i class="fas fa-server"></i> Kendi Sunucunuz</strong>
                    <small class="d-block text-muted">
                        Host: mail.domaininiz.com<br>
                        Port: 587 veya 465
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.target;

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
