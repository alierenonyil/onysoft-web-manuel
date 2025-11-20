<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = 'Alışveriş Sepeti';

// Handle cart actions
if (isPost()) {
    if (verifyCsrfToken(post('csrf_token'))) {
        $action = post('action');

        if ($action === 'update') {
            $quantities = post('quantity', []);
            foreach ($_SESSION['cart'] as &$item) {
                if (isset($quantities[$item['product_id']])) {
                    $item['quantity'] = max(1, (int)$quantities[$item['product_id']]);
                }
            }
            setFlash('cart', 'Sepet güncellendi', 'success');
        } elseif ($action === 'remove') {
            $productId = (int)post('product_id');
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
                return $item['product_id'] != $productId;
            });
            setFlash('cart', 'Ürün sepetten kaldırıldı', 'success');
        }

        redirect(siteUrl('cart.php'));
    }
}

// Get cart items with product details
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $product = dbQueryOne("SELECT * FROM products WHERE id = ?", [$item['product_id']]);
        if ($product) {
            $price = $product['sale_price'] ?? $product['price'];
            $cartItems[] = array_merge($product, [
                'cart_quantity' => $item['quantity'],
                'cart_price' => $price,
                'cart_total' => $price * $item['quantity']
            ]);
            $total += $price * $item['quantity'];
        }
    }
}

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Alışveriş Sepeti</h1>

    <?php displayFlash('cart'); ?>
    <?php displayFlash('cart_add'); ?>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            <h4>Sepetiniz boş</h4>
            <p>Alışverişe başlamak için <a href="<?php echo siteUrl(); ?>">buraya tıklayın</a>.</p>
        </div>
    <?php else: ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update">

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Fiyat</th>
                            <th>Adet</th>
                            <th>Toplam</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['main_image']): ?>
                                            <img src="<?php echo siteUrl('uploads/products/' . $item['main_image']); ?>" style="width:60px;height:60px;object-fit:cover;" class="me-3">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['sku'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo formatPrice($item['cart_price']); ?></td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['cart_quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control" style="width:80px;">
                                </td>
                                <td><strong><?php echo formatPrice($item['cart_total']); ?></strong></td>
                                <td>
                                    <button type="submit" name="action" value="remove" formaction="<?php echo siteUrl('cart.php'); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ürünü sepetten kaldırmak istediğinizden emin misiniz?')">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Ara Toplam:</strong></td>
                            <td colspan="2"><h5><?php echo formatPrice($total); ?></h5></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <a href="<?php echo siteUrl(); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Alışverişe Devam
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Sepeti Güncelle
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <a href="<?php echo siteUrl('checkout.php'); ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-shopping-cart"></i> Sipariş Ver
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
