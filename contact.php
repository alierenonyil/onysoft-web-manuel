<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = 'İletişim';
$errors = [];
$success = '';

if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $formData = [
            'name' => cleanText(post('name')),
            'email' => cleanText(post('email')),
            'phone' => cleanText(post('phone')),
            'subject' => cleanText(post('subject')),
            'message' => cleanText(post('message')),
            'ip_address' => getClientIP()
        ];

        $required = [
            'name' => 'Ad Soyad',
            'email' => 'Email',
            'subject' => 'Konu',
            'message' => 'Mesaj'
        ];

        $errors = validateRequired($formData, $required);

        if (!validateEmail($formData['email'])) {
            $errors['email'] = 'Geçersiz email adresi';
        }

        if (empty($errors)) {
            if (dbInsert('contact_messages', $formData)) {
                // Send email notification
                $emailBody = "
                    <h3>Yeni İletişim Mesajı</h3>
                    <p><strong>Ad:</strong> {$formData['name']}</p>
                    <p><strong>Email:</strong> {$formData['email']}</p>
                    <p><strong>Telefon:</strong> {$formData['phone']}</p>
                    <p><strong>Konu:</strong> {$formData['subject']}</p>
                    <p><strong>Mesaj:</strong><br>{$formData['message']}</p>
                ";

                sendEmail(getSetting('site_email'), 'Yeni İletişim Mesajı', $emailBody);

                $success = 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.';
                $formData = []; // Clear form
            } else {
                $errors[] = 'Mesaj gönderilirken bir hata oluştu';
            }
        }
    } else {
        $errors[] = 'Geçersiz form gönderimi';
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1 class="mb-4">İletişim</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-map-marker-alt text-primary"></i> Adres</h5>
                    <p><?php echo nl2br(htmlspecialchars(getSetting('site_address'))); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-phone text-primary"></i> Telefon</h5>
                    <p><a href="tel:<?php echo getSetting('site_phone'); ?>"><?php echo htmlspecialchars(getSetting('site_phone')); ?></a></p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-envelope text-primary"></i> Email</h5>
                    <p><a href="mailto:<?php echo getSetting('site_email'); ?>"><?php echo htmlspecialchars(getSetting('site_email')); ?></a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">İletişim Formu</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>

                        <div class="mb-3">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konu *</label>
                            <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($formData['subject'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mesajınız *</label>
                            <textarea name="message" class="form-control" rows="5" required><?php echo htmlspecialchars($formData['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Gönder
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Konum</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Google Maps embed (örnek) -->
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3060.2989469098824!2d32.85384931527444!3d39.92077997942213!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d34f190a9c6b13%3A0x8f3f6d3e8d3f6d3f!2sAnkara%2C%20Turkey!5e0!3m2!1sen!2str!4v1234567890123!5m2!1sen!2str"
                        width="100%"
                        height="400"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Çalışma Saatleri</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Pazartesi - Cuma:</strong> 09:00 - 18:00</p>
                            <p><strong>Cumartesi:</strong> 10:00 - 16:00</p>
                            <p><strong>Pazar:</strong> Kapalı</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Sosyal Medya</h6>
                            <div class="social-links">
                                <?php if (getSetting('facebook_url')): ?>
                                    <a href="<?php echo getSetting('facebook_url'); ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                        <i class="fab fa-facebook"></i> Facebook
                                    </a>
                                <?php endif; ?>
                                <?php if (getSetting('instagram_url')): ?>
                                    <a href="<?php echo getSetting('instagram_url'); ?>" class="btn btn-outline-danger btn-sm me-2" target="_blank">
                                        <i class="fab fa-instagram"></i> Instagram
                                    </a>
                                <?php endif; ?>
                                <?php if (getSetting('twitter_url')): ?>
                                    <a href="<?php echo getSetting('twitter_url'); ?>" class="btn btn-outline-info btn-sm me-2" target="_blank">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
