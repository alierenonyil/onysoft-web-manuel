<?php
require_once 'includes/config.php';

if (!isPost() || !verifyCsrfToken(post('csrf_token'))) {
    redirect(siteUrl());
}

$productId = (int)post('product_id');
$quantity = max(1, (int)post('quantity', 1));

$product = dbQueryOne("SELECT * FROM products WHERE id = ? AND status = 1", [$productId]);

if (!$product) {
    setFlash('error', 'Ürün bulunamadı', 'error');
    redirect(siteUrl());
}

if ($product['stock'] < $quantity) {
    setFlash('error', 'Yeterli stok yok', 'error');
    redirect($_SERVER['HTTP_REFERER'] ?? siteUrl());
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product already in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $productId) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart'][] = [
        'product_id' => $productId,
        'quantity' => $quantity
    ];
}

setFlash('cart_add', 'Ürün sepete eklendi', 'success');
redirect(siteUrl('cart.php'));
