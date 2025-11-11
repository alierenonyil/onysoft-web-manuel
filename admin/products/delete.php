<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

requireAdmin();

$productId = (int)get('id');

if ($productId <= 0) {
    setFlash('product', 'Geçersiz ürün ID', 'error');
    redirect(siteUrl('admin/products/index.php'));
}

$product = dbQueryOne("SELECT * FROM products WHERE id = ?", [$productId]);

if (!$product) {
    setFlash('product', 'Ürün bulunamadı', 'error');
    redirect(siteUrl('admin/products/index.php'));
}

// Delete images
if (!empty($product['main_image'])) {
    deleteFile(UPLOAD_PATH . '/products/' . $product['main_image']);
}

if (!empty($product['images'])) {
    $images = json_decode($product['images'], true);
    foreach ($images as $image) {
        deleteFile(UPLOAD_PATH . '/products/' . $image);
    }
}

// Delete product
if (dbDelete('products', 'id = ?', [$productId])) {
    logActivity($_SESSION['admin_id'], 'admin', 'product_delete', 'Deleted product: ' . $product['name']);
    setFlash('product', 'Ürün başarıyla silindi', 'success');
} else {
    setFlash('product', 'Ürün silinirken bir hata oluştu', 'error');
}

redirect(siteUrl('admin/products/index.php'));
