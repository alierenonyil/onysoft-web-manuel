<div class="card h-100 product-card">
    <a href="<?php echo siteUrl('product.php?slug=' . $product['slug']); ?>">
        <?php if ($product['main_image']): ?>
            <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height:200px;object-fit:cover;">
        <?php else: ?>
            <div class="bg-light text-center" style="height:200px;line-height:200px;">
                <i class="fas fa-image fa-3x text-muted"></i>
            </div>
        <?php endif; ?>
    </a>

    <div class="card-body">
        <?php if ($product['is_new']): ?>
            <span class="badge bg-info mb-2">Yeni</span>
        <?php endif; ?>
        <?php if ($product['is_featured']): ?>
            <span class="badge bg-warning mb-2">Öne Çıkan</span>
        <?php endif; ?>

        <h6 class="card-title">
            <a href="<?php echo siteUrl('product.php?slug=' . $product['slug']); ?>" class="text-dark text-decoration-none">
                <?php echo htmlspecialchars($product['name']); ?>
            </a>
        </h6>

        <p class="text-muted small mb-2">
            <?php echo htmlspecialchars($product['category_name'] ?? ''); ?>
        </p>

        <div class="d-flex justify-content-between align-items-center">
            <div class="price">
                <?php if ($product['sale_price']): ?>
                    <del class="text-muted small"><?php echo formatPrice($product['price']); ?></del><br>
                    <strong class="text-danger"><?php echo formatPrice($product['sale_price']); ?></strong>
                <?php else: ?>
                    <strong><?php echo formatPrice($product['price']); ?></strong>
                <?php endif; ?>
            </div>

            <form action="<?php echo siteUrl('cart-add.php'); ?>" method="POST" class="add-to-cart-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-primary btn-sm" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </form>
        </div>

        <?php if ($product['stock'] <= 0): ?>
            <div class="mt-2"><span class="badge bg-danger">Stokta Yok</span></div>
        <?php elseif ($product['stock'] <= $product['low_stock_threshold']): ?>
            <div class="mt-2"><span class="badge bg-warning">Son <?php echo $product['stock']; ?> Ürün</span></div>
        <?php endif; ?>
    </div>
</div>
