<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';

$pageTitle = 'Ürün Düzenle';

$productId = (int)get('id');
if ($productId <= 0) {
    redirect(siteUrl('admin/products/index.php'));
}

$product = dbQueryOne("SELECT * FROM products WHERE id = ?", [$productId]);
if (!$product) {
    setFlash('product', 'Ürün bulunamadı', 'error');
    redirect(siteUrl('admin/products/index.php'));
}

$errors = [];
$formData = $product;

if (isPost()) {
    if (!verifyCsrfToken(post('csrf_token'))) {
        $errors[] = 'Geçersiz form gönderimi';
    } else {
        $formData = [
            'category_id' => (int)post('category_id'),
            'name' => cleanText(post('name')),
            'sku' => cleanText(post('sku')),
            'description' => post('description'),
            'short_description' => cleanText(post('short_description')),
            'price' => (float)post('price'),
            'sale_price' => !empty(post('sale_price')) ? (float)post('sale_price') : null,
            'cost_price' => !empty(post('cost_price')) ? (float)post('cost_price') : null,
            'stock' => (int)post('stock'),
            'low_stock_threshold' => (int)post('low_stock_threshold', 5),
            'weight' => !empty(post('weight')) ? (float)post('weight') : null,
            'dimensions' => cleanText(post('dimensions')),
            'meta_title' => cleanText(post('meta_title')),
            'meta_description' => cleanText(post('meta_description')),
            'meta_keywords' => cleanText(post('meta_keywords')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_new' => isset($_POST['is_new']) ? 1 : 0,
            'status' => isset($_POST['status']) ? 1 : 0,
            'sort_order' => (int)post('sort_order', 0)
        ];

        if (empty($formData['name'])) {
            $errors[] = 'Ürün adı zorunludur';
        }

        if ($formData['category_id'] <= 0) {
            $errors[] = 'Kategori seçiniz';
        }

        if ($formData['price'] <= 0) {
            $errors[] = 'Geçerli bir fiyat giriniz';
        }

        // Update slug if name changed
        if ($formData['name'] !== $product['name']) {
            $formData['slug'] = generateSlug($formData['name']);
            if (dbExists('products', 'slug = ? AND id != ?', [$formData['slug'], $productId])) {
                $formData['slug'] .= '-' . time();
            }
        }

        // Check SKU
        if (!empty($formData['sku']) && dbExists('products', 'sku = ? AND id != ?', [$formData['sku'], $productId])) {
            $errors[] = 'Bu SKU zaten kullanılıyor';
        }

        // Handle main image upload
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['main_image'], UPLOAD_PATH . '/products');
            if ($uploadResult['success']) {
                // Delete old image
                if (!empty($product['main_image'])) {
                    deleteFile(UPLOAD_PATH . '/products/' . $product['main_image']);
                }
                $formData['main_image'] = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['error'];
            }
        }

        // Handle additional images
        $existingImages = !empty($product['images']) ? json_decode($product['images'], true) : [];
        $additionalImages = $existingImages;

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    $uploadResult = uploadFile($file, UPLOAD_PATH . '/products');
                    if ($uploadResult['success']) {
                        $additionalImages[] = $uploadResult['filename'];
                    }
                }
            }
        }

        if (!empty($additionalImages)) {
            $formData['images'] = json_encode($additionalImages);
        } else {
            $formData['images'] = null;
        }

        if (empty($errors)) {
            if (dbUpdate('products', $formData, 'id = ?', [$productId])) {
                logActivity($_SESSION['admin_id'], 'admin', 'product_update', 'Updated product: ' . $formData['name']);
                setFlash('product', 'Ürün başarıyla güncellendi', 'success');
                redirect(siteUrl('admin/products/index.php'));
            } else {
                $errors[] = 'Ürün güncellenirken bir hata oluştu';
            }
        }
    }
}

$categories = dbQuery("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit"></i> Ürün Düzenle: <?php echo htmlspecialchars($product['name']); ?></h2>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Geri
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php echo csrfField(); ?>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Temel Bilgiler</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($formData['sku'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $formData['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kısa Açıklama</label>
                        <textarea name="short_description" class="form-control" rows="2" maxlength="500"><?php echo htmlspecialchars($formData['short_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detaylı Açıklama</label>
                        <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Fiyatlandırma</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Normal Fiyat <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo $formData['price']; ?>" required>
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">İndirimli Fiyat</label>
                            <div class="input-group">
                                <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?php echo $formData['sale_price'] ?? ''; ?>">
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maliyet Fiyatı</label>
                            <div class="input-group">
                                <input type="number" name="cost_price" class="form-control" step="0.01" min="0" value="<?php echo $formData['cost_price'] ?? ''; ?>">
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Stok ve Boyutlar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stok Miktarı</label>
                            <input type="number" name="stock" class="form-control" min="0" value="<?php echo $formData['stock']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Düşük Stok Eşiği</label>
                            <input type="number" name="low_stock_threshold" class="form-control" min="0" value="<?php echo $formData['low_stock_threshold']; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ağırlık (kg)</label>
                            <input type="number" name="weight" class="form-control" step="0.01" min="0" value="<?php echo $formData['weight'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Boyutlar</label>
                            <input type="text" name="dimensions" class="form-control" value="<?php echo htmlspecialchars($formData['dimensions'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">SEO Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta Başlık</label>
                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($formData['meta_title'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Açıklama</label>
                        <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($formData['meta_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Anahtar Kelimeler</label>
                        <input type="text" name="meta_keywords" class="form-control" value="<?php echo htmlspecialchars($formData['meta_keywords'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Durum ve Özellikler</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="status" class="form-check-input" id="status" <?php echo $formData['status'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" <?php echo $formData['is_featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_featured">Öne Çıkan Ürün</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_new" class="form-check-input" id="is_new" <?php echo $formData['is_new'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_new">Yeni Ürün</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sıra</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="<?php echo $formData['sort_order']; ?>">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Ürün Görselleri</h5>
                </div>
                <div class="card-body">
                    <?php if ($product['main_image']): ?>
                        <div class="mb-3 text-center">
                            <img src="<?php echo siteUrl('uploads/products/' . $product['main_image']); ?>" class="img-thumbnail" style="max-width: 100%; max-height: 200px;">
                            <p class="small text-muted mt-2">Mevcut Ana Görsel</p>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Ana Görsel Değiştir</label>
                        <input type="file" name="main_image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ek Görseller Ekle</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> Değişiklikleri Kaydet
                    </button>
                    <a href="delete.php?id=<?php echo $productId; ?>" class="btn btn-danger w-100 delete-confirm">
                        <i class="fas fa-trash"></i> Ürünü Sil
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>
