<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

requireAdmin();

$campaignId = (int)get('id');

if ($campaignId <= 0) {
    setFlash('email', 'Geçersiz kampanya ID', 'error');
    redirect(siteUrl('admin/emails/index.php'));
}

$campaign = dbQueryOne("SELECT * FROM email_campaigns WHERE id = ?", [$campaignId]);

if (!$campaign) {
    setFlash('email', 'Kampanya bulunamadı', 'error');
    redirect(siteUrl('admin/emails/index.php'));
}

// Delete campaign
if (dbDelete('email_campaigns', 'id = ?', [$campaignId])) {
    logActivity($_SESSION['admin_id'], 'admin', 'email_campaign_delete', 'Deleted email campaign: ' . $campaign['subject']);
    setFlash('email', 'Kampanya başarıyla silindi', 'success');
} else {
    setFlash('email', 'Kampanya silinirken bir hata oluştu', 'error');
}

redirect(siteUrl('admin/emails/index.php'));
