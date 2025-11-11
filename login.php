<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

if (isCustomer()) {
    redirect(siteUrl('account.php'));
}

$pageTitle = 'Giriş Yap';
$error = '';

if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $email = cleanText(post('email'));
        $password = post('password');

        if (empty($email) || empty($password)) {
            $error = 'Email ve şifre zorunludur';
        } else {
            $result = customerLogin($email, $password, isset($_POST['remember']));

            if ($result['success']) {
                $redirectTo = $_SESSION['redirect_after_login'] ?? siteUrl('account.php');
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo);
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'Geçersiz form gönderimi';
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4>Giriş Yap</h4></div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php echo csrfField(); ?>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Şifre *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input">
                            <label class="form-check-label">Beni Hatırla</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Hesabınız yok mu? <a href="<?php echo siteUrl('register.php'); ?>">Kayıt Olun</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
