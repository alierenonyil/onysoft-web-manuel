<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$pageTitle = 'Toplu Mail Gönder';

$errors = [];
$success = '';

// Get recipient counts
$allCustomersCount = dbCount('customers', 'status = 1');
$newsletterCount = dbCount('customers', 'status = 1 AND newsletter = 1');

// Get email templates
$templates = dbQuery("SELECT * FROM email_templates ORDER BY name ASC");

if (isPost()) {
    if (!verifyCsrfToken(post('csrf_token'))) {
        $errors[] = 'Geçersiz form gönderimi';
    } else {
        $recipientType = post('recipient_type');
        $subject = cleanText(post('subject'));
        $message = post('message'); // HTML content
        $sendNow = isset($_POST['send_now']);

        if (empty($subject) || empty($message)) {
            $errors[] = 'Konu ve mesaj alanları zorunludur';
        }

        if (empty($errors)) {
            // Get recipients based on type
            $recipients = [];

            switch ($recipientType) {
                case 'all_customers':
                    $recipients = dbQuery("SELECT email, first_name, last_name FROM customers WHERE status = 1");
                    break;
                case 'newsletter':
                    $recipients = dbQuery("SELECT email, first_name, last_name FROM customers WHERE status = 1 AND newsletter = 1");
                    break;
                case 'custom':
                    $customEmails = explode(',', post('custom_emails'));
                    foreach ($customEmails as $email) {
                        $email = trim($email);
                        if (validateEmail($email)) {
                            $recipients[] = ['email' => $email, 'first_name' => '', 'last_name' => ''];
                        }
                    }
                    break;
            }

            if (empty($recipients)) {
                $errors[] = 'Alıcı bulunamadı';
            } else {
                // Save campaign
                $campaignData = [
                    'subject' => $subject,
                    'message' => $message,
                    'recipient_type' => $recipientType,
                    'recipient_count' => count($recipients),
                    'status' => $sendNow ? 'sending' : 'draft',
                    'created_by' => $_SESSION['admin_id']
                ];

                // Save custom emails if custom recipient type
                if ($recipientType === 'custom') {
                    $campaignData['recipient_emails'] = post('custom_emails');
                }

                $campaignId = dbInsert('email_campaigns', $campaignData);

                if ($campaignId) {
                    if ($sendNow) {
                        // Send emails
                        $sent = 0;
                        $failed = 0;

                        foreach ($recipients as $recipient) {
                            // Replace placeholders
                            $personalizedMessage = str_replace(
                                ['{name}', '{email}', '{site_name}', '{site_url}'],
                                [
                                    $recipient['first_name'] . ' ' . $recipient['last_name'],
                                    $recipient['email'],
                                    SITE_NAME,
                                    SITE_URL
                                ],
                                $message
                            );

                            $emailBody = getEmailTemplate($subject, $personalizedMessage);

                            if (sendEmail($recipient['email'], $subject, $emailBody)) {
                                $sent++;
                            } else {
                                $failed++;
                            }

                            // Prevent server overload
                            usleep(100000); // 0.1 second delay
                        }

                        // Update campaign
                        dbUpdate('email_campaigns', [
                            'sent_count' => $sent,
                            'failed_count' => $failed,
                            'status' => 'completed',
                            'sent_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$campaignId]);

                        setFlash('email', "Mail kampanyası başarıyla gönderildi! Başarılı: $sent, Başarısız: $failed", 'success');
                        redirect(siteUrl('admin/emails/index.php'));
                    } else {
                        setFlash('email', 'Mail taslak olarak kaydedildi', 'success');
                        redirect(siteUrl('admin/emails/index.php'));
                    }
                } else {
                    $errors[] = 'Kampanya kaydedilemedi';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<style>
.recipient-card {
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid #e0e0e0;
}
.recipient-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}
.recipient-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
}
.email-preview {
    border: 1px solid #ddd;
    padding: 20px;
    background: white;
    border-radius: 8px;
    max-height: 500px;
    overflow-y: auto;
}
.template-card {
    cursor: pointer;
    transition: all 0.3s;
}
.template-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-paper-plane"></i> Toplu Mail Gönder</h2>
        <p class="text-muted mb-0">Müşterilerinize toplu e-posta gönderin</p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Geri
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="emailForm">
    <?php echo csrfField(); ?>

    <div class="row g-4">
        <!-- Left Column - Email Content -->
        <div class="col-lg-8">
            <!-- Email Templates -->
            <?php if (!empty($templates)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-palette"></i> Şablonlardan Seç</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($templates as $template): ?>
                            <div class="col-md-6">
                                <div class="card template-card" onclick="useTemplate(<?php echo $template['id']; ?>)">
                                    <div class="card-body">
                                        <h6><?php echo htmlspecialchars($template['name']); ?></h6>
                                        <p class="text-muted small mb-0"><?php echo truncate($template['description'], 80); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Subject -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-heading"></i> Konu</h5>
                </div>
                <div class="card-body">
                    <input type="text" name="subject" id="subject" class="form-control form-control-lg" placeholder="Mail konusu..." required>
                </div>
            </div>

            <!-- Message -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Mesaj</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="togglePreview()">
                        <i class="fas fa-eye"></i> Önizleme
                    </button>
                </div>
                <div class="card-body">
                    <div id="editor-container">
                        <textarea name="message" id="message" rows="15" class="form-control"></textarea>
                    </div>
                    <div id="preview-container" style="display:none;">
                        <div class="email-preview" id="preview"></div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <strong>Değişkenler:</strong> {name}, {email}, {site_name}, {site_url}
                    </small>
                </div>
            </div>
        </div>

        <!-- Right Column - Settings -->
        <div class="col-lg-4">
            <!-- Recipients -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Alıcılar</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="recipient-card card p-3 mb-3" onclick="selectRecipient('all_customers')">
                            <input type="radio" name="recipient_type" value="all_customers" id="all_customers" class="form-check-input me-2">
                            <label for="all_customers" class="form-check-label w-100 cursor-pointer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="fas fa-user-friends text-primary"></i> Tüm Müşteriler</strong>
                                        <div class="text-muted small"><?php echo number_format($allCustomersCount); ?> kişi</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="recipient-card card p-3 mb-3" onclick="selectRecipient('newsletter')">
                            <input type="radio" name="recipient_type" value="newsletter" id="newsletter" class="form-check-input me-2">
                            <label for="newsletter" class="form-check-label w-100 cursor-pointer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="fas fa-envelope text-info"></i> Bülten Aboneleri</strong>
                                        <div class="text-muted small"><?php echo number_format($newsletterCount); ?> kişi</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="recipient-card card p-3" onclick="selectRecipient('custom')">
                            <input type="radio" name="recipient_type" value="custom" id="custom" class="form-check-input me-2">
                            <label for="custom" class="form-check-label w-100 cursor-pointer">
                                <strong><i class="fas fa-user-edit text-warning"></i> Özel Alıcılar</strong>
                                <div class="text-muted small">Manuel email listesi</div>
                            </label>
                        </div>
                    </div>

                    <div id="customEmailsDiv" style="display:none;" class="mt-3">
                        <label class="form-label">Email Adresleri (virgülle ayırın)</label>
                        <textarea name="custom_emails" class="form-control" rows="4" placeholder="email1@example.com, email2@example.com"></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-rocket"></i> Gönderim</h5>
                </div>
                <div class="card-body">
                    <button type="submit" name="send_now" value="1" class="btn btn-success btn-lg w-100 mb-2">
                        <i class="fas fa-paper-plane"></i> Hemen Gönder
                    </button>
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-save"></i> Taslak Olarak Kaydet
                    </button>

                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Mail gönderimi birkaç dakika sürebilir. Lütfen bekleyin.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '#message',
    height: 500,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link | code | help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }'
});

// Recipient selection
function selectRecipient(type) {
    document.querySelectorAll('.recipient-card').forEach(card => {
        card.classList.remove('selected');
    });

    event.currentTarget.classList.add('selected');
    document.getElementById(type).checked = true;

    document.getElementById('customEmailsDiv').style.display = (type === 'custom') ? 'block' : 'none';
}

// Use template
const templates = <?php echo json_encode($templates); ?>;
function useTemplate(id) {
    const template = templates.find(t => t.id == id);
    if (template) {
        document.getElementById('subject').value = template.subject;
        tinymce.get('message').setContent(template.content);

        // Show success message
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `<i class="fas fa-check"></i> Şablon yüklendi: ${template.name} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.querySelector('.content-wrapper').insertBefore(alert, document.querySelector('.content-wrapper').firstChild);

        setTimeout(() => alert.remove(), 3000);
    }
}

// Preview toggle
let previewMode = false;
function togglePreview() {
    previewMode = !previewMode;

    if (previewMode) {
        const content = tinymce.get('message').getContent();
        document.getElementById('preview').innerHTML = content;
        document.getElementById('editor-container').style.display = 'none';
        document.getElementById('preview-container').style.display = 'block';
    } else {
        document.getElementById('editor-container').style.display = 'block';
        document.getElementById('preview-container').style.display = 'none';
    }
}

// Form validation
document.getElementById('emailForm').addEventListener('submit', function(e) {
    const recipientType = document.querySelector('input[name="recipient_type"]:checked');

    if (!recipientType) {
        e.preventDefault();
        alert('Lütfen alıcı grubu seçin');
        return false;
    }

    const btn = e.submitter;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
});
</script>

<?php require_once '../includes/footer.php'; ?>
