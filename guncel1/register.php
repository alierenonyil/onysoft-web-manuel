<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

if (isCustomer()) {
    redirect(siteUrl('account.php'));
}

$pageTitle = 'Kayıt Ol';
$errors = [];
$formData = [];

if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $formData = [
            'first_name' => cleanText(post('first_name')),
            'last_name' => cleanText(post('last_name')),
            'email' => cleanText(post('email')),
            'phone' => cleanText(post('phone')),
            'password' => post('password'),
            'password_confirm' => post('password_confirm'),
            'newsletter' => isset($_POST['newsletter'])
        ];

        if ($formData['password'] !== $formData['password_confirm']) {
            $errors[] = 'Şifreler eşleşmiyor';
        }

        $result = customerRegister($formData);

        if ($result['success']) {
            // Auto login
            customerLogin($formData['email'], $formData['password']);
            setFlash('account', 'Kayıt başarılı! Hoş geldiniz.', 'success');
            redirect(siteUrl('account.php'));
        } else {
            if (isset($result['errors'])) {
                $errors = array_values($result['errors']);
            } else {
                $errors[] = $result['message'];
            }
        }
    } else {
        $errors[] = 'Geçersiz form gönderimi';
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4>Kayıt Ol</h4></div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php echo csrfField(); ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad *</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Soyad *</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Şifre *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Şifre Tekrar *</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="newsletter" class="form-check-input">
                            <label class="form-check-label">E-Bülten almak istiyorum</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Zaten hesabınız var mı? <a href="<?php echo siteUrl('login.php'); ?>">Giriş Yapın</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
