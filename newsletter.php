<?php
require_once 'includes/config.php';

if (!isPost() || !verifyCsrfToken(post('csrf_token'))) {
    redirect(siteUrl());
}

$email = cleanText(post('email'));

if (!validateEmail($email)) {
    setFlash('newsletter', 'Geçersiz email adresi', 'error');
    redirect($_SERVER['HTTP_REFERER'] ?? siteUrl());
}

if (dbExists('newsletter', 'email = ?', [$email])) {
    setFlash('newsletter', 'Bu email zaten kayıtlı', 'warning');
} else {
    if (dbInsert('newsletter', ['email' => $email])) {
        setFlash('newsletter', 'Bültene başarıyla kaydoldunuz!', 'success');
    } else {
        setFlash('newsletter', 'Bir hata oluştu', 'error');
    }
}

redirect($_SERVER['HTTP_REFERER'] ?? siteUrl());
