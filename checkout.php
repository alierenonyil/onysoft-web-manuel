<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = 'Sipariş Ver';

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    redirect(siteUrl('cart.php'));
}

// Get cart items
$cartItems = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
    $product = dbQueryOne("SELECT * FROM products WHERE id = ?", [$item['product_id']]);
    if ($product) {
        $price = $product['sale_price'] ?? $product['price'];
        $cartItems[] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'],
            'price' => $price,
            'quantity' => $item['quantity'],
            'total' => $price * $item['quantity']
        ];
        $subtotal += $price * $item['quantity'];
    }
}

$tax = calculateTax($subtotal);
$shippingCost = 0; // Kargo ücreti
$total = $subtotal + $tax + $shippingCost;

$errors = [];
$formData = [];

if (isPost()) {
    if (!verifyCsrfToken(post('csrf_token'))) {
        $errors[] = 'Geçersiz form gönderimi';
    } else {
        $formData = [
            'first_name' => cleanText(post('first_name')),
            'last_name' => cleanText(post('last_name')),
            'email' => cleanText(post('email')),
            'phone' => cleanText(post('phone')),
            'address_line1' => cleanText(post('address_line1')),
            'city' => cleanText(post('city')),
            'postal_code' => cleanText(post('postal_code')),
            'payment_method' => post('payment_method', 'bank_transfer')
        ];

        $required = [
            'first_name' => 'Ad',
            'last_name' => 'Soyad',
            'email' => 'Email',
            'phone' => 'Telefon',
            'address_line1' => 'Adres',
            'city' => 'Şehir',
            'postal_code' => 'Posta Kodu'
        ];

        $errors = validateRequired($formData, $required);

        if (empty($errors)) {
            dbBeginTransaction();

            try {
                // Create order
                $orderData = [
                    'customer_id' => isCustomer() ? $_SESSION['customer_id'] : null,
                    'order_number' => generateOrderNumber(),
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $formData['payment_method'],
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'shipping_cost' => $shippingCost,
                    'total' => $total,
                    'billing_address' => json_encode($formData),
                    'shipping_address' => json_encode($formData),
                    'ip_address' => getClientIP()
                ];

                $orderId = dbInsert('orders', $orderData);

                if (!$orderId) {
                    throw new Exception('Sipariş oluşturulamadı');
                }

                // Create order items
                foreach ($cartItems as $item) {
                    $orderItemData = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'product_sku' => $item['sku'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['total'],
                        'total' => $item['total']
                    ];

                    dbInsert('order_items', $orderItemData);

                    // Update stock
                    dbExecute("UPDATE products SET stock = stock - ? WHERE id = ?", [$item['quantity'], $item['product_id']]);
                }

                dbCommit();

                // Clear cart
                unset($_SESSION['cart']);

                // Redirect to success page
                setFlash('order_success', 'Siparişiniz başarıyla oluşturuldu! Sipariş numaranız: ' . $orderData['order_number'], 'success');
                redirect(siteUrl('order-success.php?order=' . $orderData['order_number']));

            } catch (Exception $e) {
                dbRollback();
                $errors[] = 'Sipariş oluşturulurken bir hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Sipariş Ver</h1>

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

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header"><h5>Fatura ve Teslimat Bilgileri</h5></div>
                    <div class="card-body">
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

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon *</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres *</label>
                            <input type="text" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($formData['address_line1'] ?? ''); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şehir *</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($formData['city'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Posta Kodu *</label>
                                <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($formData['postal_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Ödeme Yöntemi</h5></div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="radio" name="payment_method" value="bank_transfer" class="form-check-input" checked>
                            <label class="form-check-label">Havale/EFT</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="payment_method" value="credit_card" class="form-check-input">
                            <label class="form-check-label">Kredi Kartı (Yakında)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>Sipariş Özeti</h5></div>
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <small><?php echo htmlspecialchars($item['name']); ?></small><br>
                                    <small class="text-muted"><?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></small>
                                </div>
                                <div><?php echo formatPrice($item['total']); ?></div>
                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <div>Ara Toplam:</div>
                            <div><?php echo formatPrice($subtotal); ?></div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <div>KDV (%<?php echo TAX_RATE; ?>):</div>
                            <div><?php echo formatPrice($tax); ?></div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <div>Kargo:</div>
                            <div><?php echo $shippingCost > 0 ? formatPrice($shippingCost) : 'Ücretsiz'; ?></div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <h5>Toplam:</h5>
                            <h5><?php echo formatPrice($total); ?></h5>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mt-3">
                            <i class="fas fa-check"></i> Sipariş Tamamla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
