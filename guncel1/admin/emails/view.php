<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$campaignId = (int)get('id');
$campaign = dbQueryOne("
    SELECT ec.*, au.full_name as sender_name, au.email as sender_email
    FROM email_campaigns ec
    LEFT JOIN admin_users au ON ec.created_by = au.id
    WHERE ec.id = ?
", [$campaignId]);

if (!$campaign) {
    setFlash('email', 'Kampanya bulunamadı', 'error');
    redirect(siteUrl('admin/emails/index.php'));
}

$pageTitle = 'Kampanya Detayı';

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-eye"></i> Kampanya Detayı</h2>
    <div>
        <?php if ($campaign['status'] == 'draft'): ?>
            <a href="compose.php?id=<?php echo $campaign['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Düzenle
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <!-- Email Preview -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-envelope-open"></i> Email Önizleme</h5>
            </div>
            <div class="card-body">
                <div class="email-header mb-4 pb-4 border-bottom">
                    <h3><?php echo htmlspecialchars($campaign['subject']); ?></h3>
                    <div class="text-muted">
                        <small>
                            <i class="fas fa-user"></i> Gönderen: <?php echo htmlspecialchars($campaign['sender_name']); ?> (<?php echo htmlspecialchars($campaign['sender_email']); ?>)<br>
                            <i class="fas fa-clock"></i> Tarih: <?php echo formatDate($campaign['created_at']); ?>
                            <?php if ($campaign['sent_at']): ?>
                                <br><i class="fas fa-paper-plane"></i> Gönderim: <?php echo formatDate($campaign['sent_at']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <div class="email-content">
                    <?php echo $campaign['message']; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Campaign Info -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Kampanya Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Durum</label>
                    <div>
                        <?php
                        $statusBadges = [
                            'draft' => '<span class="badge bg-secondary fs-6">Taslak</span>',
                            'sending' => '<span class="badge bg-warning fs-6"><i class="fas fa-spinner fa-spin"></i> Gönderiliyor</span>',
                            'completed' => '<span class="badge bg-success fs-6">Tamamlandı</span>'
                        ];
                        echo $statusBadges[$campaign['status']] ?? $campaign['status'];
                        ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small">Alıcı Tipi</label>
                    <div>
                        <?php
                        $types = [
                            'all_customers' => '<span class="badge bg-primary">Tüm Müşteriler</span>',
                            'newsletter' => '<span class="badge bg-info">Bülten Aboneleri</span>',
                            'custom' => '<span class="badge bg-warning">Özel Liste</span>'
                        ];
                        echo $types[$campaign['recipient_type']] ?? $campaign['recipient_type'];
                        ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small">Toplam Alıcı</label>
                    <div><strong><?php echo number_format($campaign['recipient_count']); ?></strong> kişi</div>
                </div>

                <?php if ($campaign['status'] == 'completed'): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Başarılı Gönderim</label>
                        <div><strong class="text-success"><?php echo number_format($campaign['sent_count']); ?></strong> email</div>
                    </div>

                    <?php if ($campaign['failed_count'] > 0): ?>
                        <div class="mb-3">
                            <label class="text-muted small">Başarısız</label>
                            <div><strong class="text-danger"><?php echo number_format($campaign['failed_count']); ?></strong> email</div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="text-muted small">Başarı Oranı</label>
                        <?php
                        $successRate = $campaign['recipient_count'] > 0 ? ($campaign['sent_count'] / $campaign['recipient_count']) * 100 : 0;
                        ?>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $successRate; ?>%">
                                <?php echo number_format($successRate, 1); ?>%
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="text-muted small">Oluşturan</label>
                    <div><?php echo htmlspecialchars($campaign['sender_name']); ?></div>
                </div>

                <div>
                    <label class="text-muted small">Oluşturulma Tarihi</label>
                    <div><?php echo formatDate($campaign['created_at'], 'd.m.Y H:i'); ?></div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($campaign['status'] == 'draft'): ?>
            <div class="card">
                <div class="card-body">
                    <form action="send.php" method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-paper-plane"></i> Hemen Gönder
                        </button>
                    </form>
                    <a href="delete.php?id=<?php echo $campaign['id']; ?>" class="btn btn-danger w-100 delete-confirm">
                        <i class="fas fa-trash"></i> Sil
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
