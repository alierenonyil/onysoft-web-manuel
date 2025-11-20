<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Veritabanı Yedekleme';

// Backup directory
$backupDir = ROOT_PATH . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Create .htaccess to protect backups
$htaccessFile = $backupDir . '/.htaccess';
if (!file_exists($htaccessFile)) {
    file_put_contents($htaccessFile, "Deny from all\n");
}

// Handle actions
if (isPost() && verifyCsrfToken(post('csrf_token'))) {
    $action = post('action');

    if ($action === 'create_backup') {
        $notes = cleanText(post('notes', ''));
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        try {
            // Create backup using mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($filepath)) {
                $filesize = filesize($filepath);

                // Save to database
                dbInsert('database_backups', [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'filesize' => $filesize,
                    'created_by' => $_SESSION['admin_id'],
                    'notes' => $notes,
                    'status' => 'completed'
                ]);

                logActivity($_SESSION['admin_id'], 'admin', 'backup_created', "Veritabanı yedeği oluşturuldu: $filename");

                setFlash('backup', 'Veritabanı yedeği başarıyla oluşturuldu!', 'success');
            } else {
                setFlash('backup', 'Yedekleme başarısız oldu. Lütfen sunucu ayarlarını kontrol edin.', 'error');
            }
        } catch (Exception $e) {
            setFlash('backup', 'Hata: ' . $e->getMessage(), 'error');
        }

        redirect(siteUrl('admin/tools/backup.php'));
    }

    elseif ($action === 'download_backup') {
        $backupId = (int)post('backup_id');
        $backup = dbQueryOne("SELECT * FROM database_backups WHERE id = ?", [$backupId]);

        if ($backup && file_exists($backup['filepath'])) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            header('Content-Length: ' . $backup['filesize']);
            header('Pragma: public');

            readfile($backup['filepath']);

            logActivity($_SESSION['admin_id'], 'admin', 'backup_downloaded', "Yedek indirildi: {$backup['filename']}");
            exit;
        } else {
            setFlash('backup', 'Yedek dosyası bulunamadı', 'error');
            redirect(siteUrl('admin/tools/backup.php'));
        }
    }

    elseif ($action === 'delete_backup') {
        $backupId = (int)post('backup_id');
        $backup = dbQueryOne("SELECT * FROM database_backups WHERE id = ?", [$backupId]);

        if ($backup) {
            if (file_exists($backup['filepath'])) {
                unlink($backup['filepath']);
            }

            dbDelete('database_backups', 'id = ?', [$backupId]);

            logActivity($_SESSION['admin_id'], 'admin', 'backup_deleted', "Yedek silindi: {$backup['filename']}");

            setFlash('backup', 'Yedek başarıyla silindi', 'success');
        }

        redirect(siteUrl('admin/tools/backup.php'));
    }
}

// Get all backups
$backups = dbQuery("
    SELECT b.*, a.full_name as created_by_name
    FROM database_backups b
    LEFT JOIN admin_users a ON b.created_by = a.id
    ORDER BY b.created_at DESC
");

// Calculate total backup size
$totalSize = 0;
foreach ($backups as $backup) {
    $totalSize += $backup['filesize'];
}

require_once '../includes/header.php';
?>

<style>
.backup-card {
    transition: all 0.3s;
    border-left: 4px solid #667eea;
}
.backup-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.backup-size {
    font-size: 0.9rem;
    color: #666;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-database"></i> Veritabanı Yedekleme</h2>
        <p class="text-muted mb-0">Veritabanınızı yedekleyin ve geri yükleyin</p>
    </div>
</div>

<?php displayFlash('backup'); ?>

<div class="row g-4 mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hdd fa-3x me-3"></i>
                    <div>
                        <h6 class="mb-0">Toplam Yedek</h6>
                        <h3 class="mb-0"><?php echo count($backups); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-3x me-3"></i>
                    <div>
                        <h6 class="mb-0">Başarılı</h6>
                        <h3 class="mb-0"><?php echo dbCount('database_backups', "status = 'completed'"); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-save fa-3x me-3"></i>
                    <div>
                        <h6 class="mb-0">Toplam Boyut</h6>
                        <h3 class="mb-0"><?php echo number_format($totalSize / 1024 / 1024, 2); ?> MB</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock fa-3x me-3"></i>
                    <div>
                        <h6 class="mb-0">Son Yedek</h6>
                        <h6 class="mb-0"><?php echo !empty($backups) ? timeAgo($backups[0]['created_at']) : 'Yok'; ?></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Create Backup -->
    <div class="col-lg-4">
        <div class="card backup-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Yeni Yedek Oluştur</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create_backup">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Dikkat:</strong> Yedekleme işlemi büyük veritabanlarında birkaç dakika sürebilir.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Yedek Notları (Opsiyonel)</label>
                        <textarea name="notes" class="form-control" rows="3"
                            placeholder="Örn: V2.0 öncesi yedek, Güncelleme öncesi vb."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-database"></i> Yedek Oluştur
                    </button>
                </form>

                <hr>

                <div class="mt-3">
                    <h6><i class="fas fa-shield-alt"></i> Güvenlik İpuçları</h6>
                    <ul class="small text-muted">
                        <li>Düzenli yedekleme yapın</li>
                        <li>Yedekleri güvenli yerde saklayın</li>
                        <li>Önemli değişiklikler öncesi yedek alın</li>
                        <li>Yedekleri test edin</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup List -->
    <div class="col-lg-8">
        <div class="card backup-card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Yedek Geçmişi</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Henüz Yedek Yok</h5>
                        <p class="text-muted">İlk veritabanı yedeğinizi oluşturun</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Oluşturan</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                                            <?php if ($backup['notes']): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-sticky-note"></i>
                                                    <?php echo htmlspecialchars($backup['notes']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="backup-size">
                                            <?php echo number_format($backup['filesize'] / 1024 / 1024, 2); ?> MB
                                        </td>
                                        <td><?php echo htmlspecialchars($backup['created_by_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo formatDate($backup['created_at'], 'd.m.Y H:i'); ?>
                                            <br><small class="text-muted"><?php echo timeAgo($backup['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadges = [
                                                'completed' => '<span class="badge bg-success">Tamamlandı</span>',
                                                'failed' => '<span class="badge bg-danger">Başarısız</span>',
                                                'in_progress' => '<span class="badge bg-warning">Devam Ediyor</span>'
                                            ];
                                            echo $statusBadges[$backup['status']] ?? $backup['status'];
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="download_backup">
                                                    <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                    <button type="submit" class="btn btn-success" title="İndir">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="delete_backup">
                                                    <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                    <button type="submit" class="btn btn-danger delete-confirm" title="Sil">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Automatic Backup Info -->
        <div class="card backup-card mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-robot"></i> Otomatik Yedekleme</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">Otomatik yedekleme için sunucunuzda cron job ayarlayabilirsiniz:</p>
                <div class="bg-dark text-white p-3 rounded">
                    <code>0 2 * * * mysqldump -u <?php echo DB_USER; ?> -p<?php echo DB_PASS; ?> <?php echo DB_NAME; ?> > /path/to/backup_$(date +\%Y-\%m-\%d).sql</code>
                </div>
                <small class="text-muted">Bu komut her gece saat 02:00'de otomatik yedek alır.</small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
