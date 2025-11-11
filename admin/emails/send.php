<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

// Check CSRF
if (!verifyCsrf()) {
    setFlash('email', 'Güvenlik kontrolü başarısız', 'error');
    redirect(siteUrl('admin/emails/index.php'));
}

$campaignId = (int)post('campaign_id');

// Get campaign details
$campaign = dbQueryOne("
    SELECT * FROM email_campaigns WHERE id = ? AND status = 'draft'
", [$campaignId]);

if (!$campaign) {
    setFlash('email', 'Kampanya bulunamadı veya zaten gönderilmiş', 'error');
    redirect(siteUrl('admin/emails/index.php'));
}

// Update status to sending
dbUpdate('email_campaigns',
    ['status' => 'sending'],
    'id = ?',
    [$campaignId]
);

// Get recipients based on type
$recipients = [];

switch ($campaign['recipient_type']) {
    case 'all_customers':
        $recipients = dbQuery("
            SELECT id, first_name, last_name, email
            FROM customers
            WHERE status = 1 AND email IS NOT NULL AND email != ''
        ");
        break;

    case 'newsletter':
        $recipients = dbQuery("
            SELECT id, first_name, last_name, email
            FROM customers
            WHERE status = 1 AND newsletter = 1 AND email IS NOT NULL AND email != ''
        ");
        break;

    case 'custom':
        // For custom recipients, emails are stored in recipient_emails field
        if (!empty($campaign['recipient_emails'])) {
            $emails = explode(',', $campaign['recipient_emails']);
            foreach ($emails as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Try to find customer by email
                    $customer = dbQueryOne("
                        SELECT id, first_name, last_name, email
                        FROM customers
                        WHERE email = ?
                    ", [$email]);

                    if ($customer) {
                        $recipients[] = $customer;
                    } else {
                        // Create dummy recipient for email-only addresses
                        $recipients[] = [
                            'id' => 0,
                            'first_name' => '',
                            'last_name' => '',
                            'email' => $email
                        ];
                    }
                }
            }
        }
        break;
}

// Update recipient count
$recipientCount = count($recipients);
dbUpdate('email_campaigns',
    ['recipient_count' => $recipientCount],
    'id = ?',
    [$campaignId]
);

// Send emails
$sentCount = 0;
$failedCount = 0;

foreach ($recipients as $recipient) {
    // Personalize message
    $fullName = trim($recipient['first_name'] . ' ' . $recipient['last_name']);
    if (empty($fullName)) {
        $fullName = 'Değerli Müşterimiz';
    }

    $personalizedMessage = str_replace(
        ['{name}', '{email}', '{site_name}', '{site_url}'],
        [$fullName, $recipient['email'], SITE_NAME, SITE_URL],
        $campaign['message']
    );

    // Wrap in email template
    $emailHtml = getEmailTemplate($campaign['subject'], $personalizedMessage);

    // Send email
    $sent = sendEmail(
        $recipient['email'],
        $campaign['subject'],
        $emailHtml
    );

    if ($sent) {
        $sentCount++;
    } else {
        $failedCount++;
    }

    // Prevent server overload - add small delay
    usleep(100000); // 0.1 second delay between emails
}

// Update campaign status
dbUpdate('email_campaigns',
    [
        'status' => 'completed',
        'sent_count' => $sentCount,
        'failed_count' => $failedCount,
        'sent_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$campaignId]
);

// Log activity
$admin = getAdmin();
logActivity(
    $admin['id'],
    'admin',
    'email_campaign_sent',
    "Email kampanyası gönderildi: {$campaign['subject']} - {$sentCount} başarılı, {$failedCount} başarısız"
);

// Set flash message
if ($failedCount > 0) {
    setFlash('email', "Kampanya gönderildi: {$sentCount} başarılı, {$failedCount} başarısız", 'warning');
} else {
    setFlash('email', "Kampanya başarıyla gönderildi: {$sentCount} alıcıya ulaştı", 'success');
}

redirect(siteUrl('admin/emails/view.php?id=' . $campaignId));
