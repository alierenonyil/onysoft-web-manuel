<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Site Ayarları';

if (isPost() && verifyCsrfToken(post('csrf_token'))) {
    foreach ($_POST as $key => $value) {
        if ($key != 'csrf_token') {
            updateSetting($key, $value);
        }
    }
    setFlash('settings', 'Ayarlar güncellendi', 'success');
    redirect(siteUrl('admin/settings/index.php'));
}

$settings = [];
$results = dbQuery("SELECT * FROM site_settings ORDER BY setting_group ASC, setting_key ASC");
foreach ($results as $row) {
    $settings[$row['setting_group']][] = $row;
}

require_once '../includes/header.php';
?>

<h2><i class="fas fa-cog"></i> Site Ayarları</h2>
<?php displayFlash('settings'); ?>

<form method="POST">
    <?php echo csrfField(); ?>

    <div class="row g-4">
        <div class="col-md-12">
            <?php foreach ($settings as $group => $groupSettings): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?php echo ucfirst($group); ?> Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($groupSettings as $setting): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($setting['setting_key']))); ?></label>
                                    <?php if ($setting['setting_type'] == 'textarea'): ?>
                                        <textarea name="<?php echo $setting['setting_key']; ?>" class="form-control" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                    <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                                        <select name="<?php echo $setting['setting_key']; ?>" class="form-select">
                                            <option value="0" <?php echo $setting['setting_value']=='0'?'selected':''; ?>>Hayır</option>
                                            <option value="1" <?php echo $setting['setting_value']=='1'?'selected':''; ?>>Evet</option>
                                        </select>
                                    <?php elseif ($setting['setting_type'] == 'number'): ?>
                                        <input type="number" name="<?php echo $setting['setting_key']; ?>" class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php elseif ($setting['setting_type'] == 'email'): ?>
                                        <input type="email" name="<?php echo $setting['setting_key']; ?>" class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php elseif ($setting['setting_type'] == 'url'): ?>
                                        <input type="url" name="<?php echo $setting['setting_key']; ?>" class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php else: ?>
                                        <input type="text" name="<?php echo $setting['setting_key']; ?>" class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ayarları Kaydet</button>
        </div>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>
