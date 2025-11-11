<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$pageTitle = 'Mail Kampanyaları';

$page = max(1, (int)get('page', 1));
$status = get('status', '');

$where = '1=1';
$params = [];

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$total = dbCount('email_campaigns', $where, $params);
$pagination = paginate($total, $page, ADMIN_ITEMS_PER_PAGE);

$campaigns = dbQuery("
    SELECT ec.*, au.full_name as sender_name
    FROM email_campaigns ec
    LEFT JOIN admin_users au ON ec.created_by = au.id
    WHERE $where
    ORDER BY ec.created_at DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
", $params);

// Get stats
$stats = [
    'total' => dbCount('email_campaigns'),
    'sent' => dbCount('email_campaigns', "status = 'completed'"),
    'draft' => dbCount('email_campaigns', "status = 'draft'"),
    'total_recipients' => dbGetValue("SELECT SUM(recipient_count) FROM email_campaigns WHERE status = 'completed'") ?? 0
];

require_once '../includes/header.php';
?>

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.1);
    transform: rotate(45deg);
}
.stat-card-icon {
    font-size: 3rem;
    opacity: 0.3;
    position: absolute;
    right: 20px;
    bottom: 20px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-envelope"></i> Mail Kampanyaları</h2>
        <p class="text-muted mb-0">Toplu mail gönderimlerinizi yönetin</p>
    </div>
    <a href="compose.php" class="btn btn-primary btn-lg">
        <i class="fas fa-plus"></i> Yeni Kampanya
    </a>
</div>

<?php displayFlash('email'); ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div style="position: relative; z-index: 1;">
                <h6 class="mb-0">Toplam Kampanya</h6>
                <h2 class="mb-0"><?php echo number_format($stats['total']); ?></h2>
            </div>
            <i class="fas fa-chart-line stat-card-icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div style="position: relative; z-index: 1;">
                <h6 class="mb-0">Gönderilen</h6>
                <h2 class="mb-0"><?php echo number_format($stats['sent']); ?></h2>
            </div>
            <i class="fas fa-check-circle stat-card-icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div style="position: relative; z-index: 1;">
                <h6 class="mb-0">Taslak</h6>
                <h2 class="mb-0"><?php echo number_format($stats['draft']); ?></h2>
            </div>
            <i class="fas fa-file-alt stat-card-icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div style="position: relative; z-index: 1;">
                <h6 class="mb-0">Toplam Alıcı</h6>
                <h2 class="mb-0"><?php echo number_format($stats['total_recipients']); ?></h2>
            </div>
            <i class="fas fa-users stat-card-icon"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="draft" <?php echo $status=='draft'?'selected':''; ?>>Taslak</option>
                    <option value="sending" <?php echo $status=='sending'?'selected':''; ?>>Gönderiliyor</option>
                    <option value="completed" <?php echo $status=='completed'?'selected':''; ?>>Tamamlandı</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filtrele</button>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Campaigns -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Konu</th>
                        <th>Alıcı Tipi</th>
                        <th>Alıcı Sayısı</th>
                        <th>Gönderilen / Başarısız</th>
                        <th>Durum</th>
                        <th>Gönderen</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Henüz kampanya bulunmuyor</p>
                                <a href="compose.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> İlk Kampanyanızı Oluşturun
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($campaign['subject']); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $types = [
                                        'all_customers' => '<span class="badge bg-primary">Tüm Müşteriler</span>',
                                        'newsletter' => '<span class="badge bg-info">Bülten Aboneleri</span>',
                                        'custom' => '<span class="badge bg-warning">Özel Liste</span>'
                                    ];
                                    echo $types[$campaign['recipient_type']] ?? $campaign['recipient_type'];
                                    ?>
                                </td>
                                <td><?php echo number_format($campaign['recipient_count']); ?></td>
                                <td>
                                    <?php if ($campaign['status'] == 'completed'): ?>
                                        <span class="text-success"><?php echo number_format($campaign['sent_count']); ?></span>
                                        <?php if ($campaign['failed_count'] > 0): ?>
                                            / <span class="text-danger"><?php echo number_format($campaign['failed_count']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => '<span class="badge bg-secondary">Taslak</span>',
                                        'sending' => '<span class="badge bg-warning"><i class="fas fa-spinner fa-spin"></i> Gönderiliyor</span>',
                                        'completed' => '<span class="badge bg-success">Tamamlandı</span>'
                                    ];
                                    echo $statusBadges[$campaign['status']] ?? $campaign['status'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($campaign['sender_name'] ?? 'Admin'); ?></td>
                                <td>
                                    <?php echo formatDate($campaign['created_at']); ?>
                                    <?php if ($campaign['sent_at']): ?>
                                        <br><small class="text-muted">Gönderim: <?php echo formatDate($campaign['sent_at']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $campaign['id']; ?>" class="btn btn-info" title="Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($campaign['status'] == 'draft'): ?>
                                            <a href="compose.php?id=<?php echo $campaign['id']; ?>" class="btn btn-warning" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete.php?id=<?php echo $campaign['id']; ?>" class="btn btn-danger delete-confirm" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php echo displayPagination($pagination, 'index.php'); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
