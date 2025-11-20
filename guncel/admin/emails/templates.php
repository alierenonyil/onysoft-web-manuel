<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$pageTitle = 'Email Şablonları';

// Predefined templates
$predefinedTemplates = [
    [
        'name' => 'Hoşgeldin Emaili',
        'subject' => 'Hoş Geldiniz - {site_name}',
        'description' => 'Yeni müşteriler için hoşgeldin mesajı',
        'content' => '
            <h2>Merhaba {name}!</h2>
            <p>Aramıza hoş geldiniz! {site_name} ailesine katıldığınız için çok mutluyuz.</p>
            <p>Size özel fırsatlar ve yeni ürünlerden haberdar olmak için bizi takip edin.</p>
            <p><a href="{site_url}" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Alışverişe Başla</a></p>
            <p>Teşekkürler,<br>{site_name} Ekibi</p>
        '
    ],
    [
        'name' => 'İndirim Kampanyası',
        'subject' => '🔥 Özel İndirim Sizin İçin!',
        'description' => 'İndirim duyurusu şablonu',
        'content' => '
            <h2>Özel İndirim!</h2>
            <p>Merhaba {name},</p>
            <p>Sadece sizin için hazırladığımız <strong>%20 indirim</strong> fırsatını kaçırmayın!</p>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;">
                <h1 style="color: #667eea; margin: 0;">%20 İNDİRİM</h1>
                <p style="margin: 10px 0;">Tüm ürünlerde geçerli</p>
            </div>
            <p><a href="{site_url}" style="background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Hemen Alışveriş Yap</a></p>
            <p><small>Bu kampanya sınırlı sürelidir.</small></p>
        '
    ],
    [
        'name' => 'Yeni Ürün Duyurusu',
        'subject' => 'Yeni Ürünler Geldi! 🎉',
        'description' => 'Yeni ürün tanıtımı',
        'content' => '
            <h2>Yeni Ürünlerimiz Sizlerle!</h2>
            <p>Merhaba {name},</p>
            <p>Heyecan verici yeni ürünlerimizi sizlerle paylaşmaktan mutluluk duyuyoruz!</p>
            <p>En yeni trendleri ve kaliteli ürünleri keşfetmek için mağazamızı ziyaret edin.</p>
            <p><a href="{site_url}/products" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Yeni Ürünleri İncele</a></p>
            <p>İyi alışverişler!<br>{site_name}</p>
        '
    ],
    [
        'name' => 'Sepet Hatırlatma',
        'subject' => 'Sepetinizdeki Ürünler Sizi Bekliyor!',
        'description' => 'Terk edilmiş sepet hatırlatması',
        'content' => '
            <h2>Sepetinizi Unutmayın!</h2>
            <p>Merhaba {name},</p>
            <p>Sepetinizde ürünler var ve sizi bekliyor! Alışverişinizi tamamlamak ister misiniz?</p>
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
                <p style="margin: 0;"><strong>İpucu:</strong> Stoklar hızla tükeniyor!</p>
            </div>
            <p><a href="{site_url}/cart" style="background: #ffc107; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Sepetime Dön</a></p>
            <p>Sorularınız için bize ulaşabilirsiniz.</p>
        '
    ],
    [
        'name' => 'Teşekkür Mesajı',
        'subject' => 'Siparişiniz İçin Teşekkürler!',
        'description' => 'Sipariş sonrası teşekkür mesajı',
        'content' => '
            <h2>Siparişiniz Alındı!</h2>
            <p>Merhaba {name},</p>
            <p>Siparişiniz için teşekkür ederiz! Ürününüz en kısa sürede hazırlanıp size ulaştırılacak.</p>
            <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0;">✓ Sipariş onaylandı</p>
                <p style="margin: 5px 0;">⏳ Hazırlanıyor</p>
                <p style="margin: 5px 0;">🚚 Kargoya verilecek</p>
            </div>
            <p><a href="{site_url}/account" style="background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Siparişimi Takip Et</a></p>
            <p>Bizi tercih ettiğiniz için teşekkürler!<br>{site_name}</p>
        '
    ]
];

// Save template if posted
if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $templateData = [
            'name' => cleanText(post('name')),
            'subject' => cleanText(post('subject')),
            'description' => cleanText(post('description')),
            'content' => post('content')
        ];

        if (dbInsert('email_templates', $templateData)) {
            setFlash('email', 'Şablon kaydedildi', 'success');
            redirect(siteUrl('admin/emails/templates.php'));
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-palette"></i> Email Şablonları</h2>
        <p class="text-muted mb-0">Hazır şablonlardan seçin veya kendiniz oluşturun</p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Geri
    </a>
</div>

<?php displayFlash('email'); ?>

<div class="row g-4">
    <?php foreach ($predefinedTemplates as $template): ?>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-file-alt text-primary"></i>
                        <?php echo htmlspecialchars($template['name']); ?>
                    </h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($template['description']); ?></p>
                    <p class="small"><strong>Konu:</strong> <?php echo htmlspecialchars($template['subject']); ?></p>
                </div>
                <div class="card-footer bg-white">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#previewModal" onclick='showPreview(<?php echo json_encode($template); ?>)'>
                        <i class="fas fa-eye"></i> Önizle
                    </button>
                    <form method="POST" class="d-inline">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($template['name']); ?>">
                        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($template['subject']); ?>">
                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($template['description']); ?>">
                        <input type="hidden" name="content" value="<?php echo htmlspecialchars($template['content']); ?>">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-save"></i> Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Konu:</strong> <span id="previewSubject"></span>
                </div>
                <div id="previewContent" style="padding: 20px; background: #f8f9fa; border-radius: 5px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showPreview(template) {
    document.getElementById('previewTitle').textContent = template.name;
    document.getElementById('previewSubject').textContent = template.subject;
    document.getElementById('previewContent').innerHTML = template.content;
}
</script>

<?php require_once '../includes/footer.php'; ?>
