<?php
require_once '../includes/config.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isAdmin()) {
    redirect(siteUrl('admin/index.php'));
}

$error = '';

if (isPost()) {
    if (!verifyCsrfToken(post('csrf_token'))) {
        $error = 'Geçersiz form gönderimi';
    } else {
        $username = cleanText(post('username'));
        $password = post('password');

        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre gereklidir';
        } else {
            // Rate limiting
            if (!checkRateLimit('admin_login_' . getClientIP(), 5, 300)) {
                $error = 'Çok fazla giriş denemesi. Lütfen 5 dakika bekleyin.';
            } else {
                $result = adminLogin($username, $password);

                if ($result['success']) {
                    redirect(siteUrl('admin/index.php'));
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h3><i class="fas fa-lock"></i> Admin Paneli</h3>
            <p class="mb-0">Giriş Yapın</p>
        </div>
        <div class="login-body">
            <?php if (get('timeout')): ?>
                <div class="alert alert-warning">Oturumunuz sona erdi. Lütfen tekrar giriş yapın.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo csrfField(); ?>

                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı veya E-posta</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100 py-2">
                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                </button>
            </form>

            <div class="text-center mt-3 text-muted small">
                <p class="mb-0">Varsayılan: admin / admin123</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
