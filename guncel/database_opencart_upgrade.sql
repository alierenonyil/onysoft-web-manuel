-- =====================================================
-- OPENCART SEVİYESİ E-TİCARET SİSTEMİ - VERİTABANI GÜNCELLEMESİ
-- =====================================================

-- Sayfa Düzenleri (Layouts)
CREATE TABLE IF NOT EXISTS `layouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `route` VARCHAR(255) DEFAULT NULL COMMENT 'Hangi sayfa için: home, category, product, page, etc.',
    `is_default` TINYINT(1) DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Layout Modülleri (Her layout'ta hangi widgetlar var)
CREATE TABLE IF NOT EXISTS `layout_modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `layout_id` INT NOT NULL,
    `module_type` VARCHAR(100) NOT NULL COMMENT 'slider, banner, products, categories, html, etc.',
    `position` VARCHAR(50) NOT NULL COMMENT 'top, content_top, content_bottom, left, right, bottom',
    `sort_order` INT DEFAULT 0,
    `settings` JSON COMMENT 'Modül ayarları',
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`layout_id`) REFERENCES `layouts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gelişmiş Slider Sistemi
DROP TABLE IF EXISTS `sliders`;
CREATE TABLE `sliders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'main' COMMENT 'main, mini, popup',
    `width` INT DEFAULT 1920,
    `height` INT DEFAULT 600,
    `autoplay` TINYINT(1) DEFAULT 1,
    `autoplay_speed` INT DEFAULT 5000,
    `animation` VARCHAR(50) DEFAULT 'slide' COMMENT 'slide, fade, zoom',
    `show_arrows` TINYINT(1) DEFAULT 1,
    `show_dots` TINYINT(1) DEFAULT 1,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Slider Görselleri
CREATE TABLE IF NOT EXISTS `slider_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slider_id` INT NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `image` VARCHAR(500) NOT NULL,
    `image_mobile` VARCHAR(500) DEFAULT NULL,
    `button_text` VARCHAR(100) DEFAULT NULL,
    `button_url` VARCHAR(500) DEFAULT NULL,
    `button_style` VARCHAR(50) DEFAULT 'primary',
    `text_position` VARCHAR(50) DEFAULT 'center' COMMENT 'left, center, right',
    `text_color` VARCHAR(20) DEFAULT '#ffffff',
    `overlay_color` VARCHAR(20) DEFAULT 'rgba(0,0,0,0.3)',
    `sort_order` INT DEFAULT 0,
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`slider_id`) REFERENCES `sliders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Banner Yönetimi
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `position` VARCHAR(100) DEFAULT 'home_top' COMMENT 'home_top, home_middle, sidebar, footer',
    `width` INT DEFAULT NULL,
    `height` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Banner Görselleri
CREATE TABLE IF NOT EXISTS `banner_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `banner_id` INT NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `image` VARCHAR(500) NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`banner_id`) REFERENCES `banners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menü Yönetimi
CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `position` VARCHAR(50) NOT NULL COMMENT 'header, footer, sidebar',
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menü Öğeleri
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT NOT NULL,
    `parent_id` INT DEFAULT 0,
    `title` VARCHAR(255) NOT NULL,
    `link_type` VARCHAR(50) DEFAULT 'custom' COMMENT 'custom, category, product, page',
    `link_id` INT DEFAULT NULL COMMENT 'İlgili kayıt ID',
    `url` VARCHAR(500) DEFAULT NULL,
    `target` VARCHAR(20) DEFAULT '_self',
    `icon` VARCHAR(100) DEFAULT NULL,
    `css_class` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kupon/İndirim Sistemi
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `type` VARCHAR(20) NOT NULL COMMENT 'percentage, fixed, free_shipping',
    `discount` DECIMAL(10,2) NOT NULL,
    `min_order_amount` DECIMAL(10,2) DEFAULT 0,
    `max_discount` DECIMAL(10,2) DEFAULT NULL,
    `usage_limit` INT DEFAULT NULL,
    `usage_per_customer` INT DEFAULT 1,
    `used_count` INT DEFAULT 0,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `applies_to` VARCHAR(50) DEFAULT 'all' COMMENT 'all, categories, products',
    `apply_ids` JSON COMMENT 'Kategori veya ürün IDleri',
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kupon Kullanım Geçmişi
CREATE TABLE IF NOT EXISTS `coupon_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `customer_id` INT DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Özellikleri (Attributes)
CREATE TABLE IF NOT EXISTS `attributes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Özellik Değerleri
CREATE TABLE IF NOT EXISTS `product_attributes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `attribute_id` INT NOT NULL,
    `value` TEXT NOT NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`attribute_id`) REFERENCES `attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Seçenekleri (Options - Renk, Beden vs.)
CREATE TABLE IF NOT EXISTS `options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'select, radio, checkbox, text, textarea, date, color',
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seçenek Değerleri
CREATE TABLE IF NOT EXISTS `option_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `option_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`option_id`) REFERENCES `options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Seçenekleri İlişkisi
CREATE TABLE IF NOT EXISTS `product_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `option_id` INT NOT NULL,
    `required` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_id`) REFERENCES `options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Seçenek Değerleri
CREATE TABLE IF NOT EXISTS `product_option_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_option_id` INT NOT NULL,
    `option_value_id` INT NOT NULL,
    `quantity` INT DEFAULT 0,
    `subtract_stock` TINYINT(1) DEFAULT 1,
    `price_prefix` CHAR(1) DEFAULT '+',
    `price` DECIMAL(10,2) DEFAULT 0,
    `sku` VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (`product_option_id`) REFERENCES `product_options`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_value_id`) REFERENCES `option_values`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Yorumları ve Puanlama
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `customer_id` INT DEFAULT NULL,
    `author_name` VARCHAR(255) NOT NULL,
    `author_email` VARCHAR(255) DEFAULT NULL,
    `rating` TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    `title` VARCHAR(255) DEFAULT NULL,
    `comment` TEXT NOT NULL,
    `pros` TEXT COMMENT 'Artıları',
    `cons` TEXT COMMENT 'Eksileri',
    `is_verified_purchase` TINYINT(1) DEFAULT 0,
    `helpful_count` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 0 COMMENT '0=beklemede, 1=onaylı, 2=reddedildi',
    `admin_reply` TEXT,
    `reply_date` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İstek Listesi (Wishlist)
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_wishlist` (`customer_id`, `product_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Karşılaştırma
CREATE TABLE IF NOT EXISTS `product_compare` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(100) NOT NULL,
    `customer_id` INT DEFAULT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kargo Yöntemleri
CREATE TABLE IF NOT EXISTS `shipping_methods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT 'Bu tutarın üzeri ücretsiz',
    `min_weight` DECIMAL(10,2) DEFAULT NULL,
    `max_weight` DECIMAL(10,2) DEFAULT NULL,
    `estimated_days` VARCHAR(50) DEFAULT NULL COMMENT '1-3 iş günü',
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kargo Bölgeleri
CREATE TABLE IF NOT EXISTS `shipping_zones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `countries` JSON COMMENT 'Ülke kodları',
    `regions` JSON COMMENT 'İl/Bölge kodları',
    `zip_codes` JSON COMMENT 'Posta kodları',
    `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kargo Yöntem-Bölge İlişkisi
CREATE TABLE IF NOT EXISTS `shipping_method_zones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shipping_method_id` INT NOT NULL,
    `zone_id` INT NOT NULL,
    `cost` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ödeme Yöntemleri
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `instructions` TEXT COMMENT 'Müşteriye gösterilecek talimatlar',
    `settings` JSON COMMENT 'API anahtarları vs.',
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bildirimler
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL COMMENT 'order, review, stock, customer',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO URL'ler
CREATE TABLE IF NOT EXISTS `seo_urls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL COMMENT 'product, category, page',
    `type_id` INT NOT NULL,
    `keyword` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sayfa Bölümleri (Page Sections)
CREATE TABLE IF NOT EXISTS `page_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT DEFAULT NULL COMMENT 'NULL = global section',
    `section_type` VARCHAR(100) NOT NULL COMMENT 'hero, features, testimonials, cta, gallery, faq, team, pricing',
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `content` LONGTEXT COMMENT 'JSON formatında içerik',
    `background_color` VARCHAR(20) DEFAULT '#ffffff',
    `background_image` VARCHAR(500) DEFAULT NULL,
    `text_color` VARCHAR(20) DEFAULT '#333333',
    `padding_top` INT DEFAULT 60,
    `padding_bottom` INT DEFAULT 60,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tema Ayarları
CREATE TABLE IF NOT EXISTS `theme_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT,
    `setting_type` VARCHAR(50) DEFAULT 'text' COMMENT 'text, color, image, json, boolean',
    `category` VARCHAR(100) DEFAULT 'general',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sosyal Medya Linkleri
CREATE TABLE IF NOT EXISTS `social_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform` VARCHAR(50) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İlgili Ürünler
CREATE TABLE IF NOT EXISTS `product_related` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `related_id` INT NOT NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`related_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ürün Görselleri (Galeri)
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image` VARCHAR(500) NOT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vergi Sınıfları
CREATE TABLE IF NOT EXISTS `tax_classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vergi Oranları
CREATE TABLE IF NOT EXISTS `tax_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tax_class_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `rate` DECIMAL(5,2) NOT NULL,
    `type` VARCHAR(20) DEFAULT 'percentage' COMMENT 'percentage, fixed',
    `priority` INT DEFAULT 1,
    FOREIGN KEY (`tax_class_id`) REFERENCES `tax_classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan Veriler Ekle
-- =====================================================

-- Varsayılan Layout
INSERT INTO `layouts` (`name`, `route`, `is_default`, `status`) VALUES
('Ana Sayfa', 'home', 1, 1),
('Kategori Sayfası', 'category', 1, 1),
('Ürün Sayfası', 'product', 1, 1),
('Sayfa', 'page', 1, 1);

-- Varsayılan Slider
INSERT INTO `sliders` (`name`, `type`, `width`, `height`, `autoplay`, `autoplay_speed`, `animation`, `show_arrows`, `show_dots`, `status`) VALUES
('Ana Sayfa Slider', 'main', 1920, 600, 1, 5000, 'slide', 1, 1, 1);

-- Varsayılan Menüler
INSERT INTO `menus` (`name`, `position`, `status`) VALUES
('Üst Menü', 'header', 1),
('Alt Menü', 'footer', 1),
('Mobil Menü', 'mobile', 1);

-- Varsayılan Kargo Yöntemi
INSERT INTO `shipping_methods` (`name`, `description`, `cost`, `free_shipping_threshold`, `estimated_days`, `sort_order`, `status`) VALUES
('Standart Kargo', 'MNG Kargo ile teslimat', 29.90, 500.00, '2-4 iş günü', 1, 1),
('Hızlı Kargo', 'Aynı gün kargo', 49.90, NULL, '1-2 iş günü', 2, 1),
('Mağazadan Teslim', 'Mağazamızdan ücretsiz teslim', 0.00, NULL, 'Aynı gün', 3, 1);

-- Varsayılan Ödeme Yöntemleri
INSERT INTO `payment_methods` (`code`, `name`, `description`, `instructions`, `sort_order`, `status`) VALUES
('bank_transfer', 'Havale/EFT', 'Banka havalesi ile ödeme', 'Siparişinizi tamamladıktan sonra aşağıdaki banka hesabına ödemenizi yapabilirsiniz.', 1, 1),
('credit_card', 'Kredi Kartı', 'Güvenli kredi kartı ile ödeme', NULL, 2, 1),
('cash_on_delivery', 'Kapıda Ödeme', 'Teslimat sırasında nakit ödeme', 'Siparişiniz size ulaştığında kurye/kargo görevlisine ödeme yapabilirsiniz.', 3, 1);

-- Varsayılan Özellikler
INSERT INTO `attributes` (`name`, `sort_order`, `status`) VALUES
('Marka', 1, 1),
('Menşei', 2, 1),
('Garanti', 3, 1),
('Malzeme', 4, 1);

-- Varsayılan Seçenekler
INSERT INTO `options` (`name`, `type`, `sort_order`, `status`) VALUES
('Renk', 'radio', 1, 1),
('Beden', 'select', 2, 1),
('Kapasite', 'select', 3, 1);

-- Renk Değerleri
INSERT INTO `option_values` (`option_id`, `name`, `sort_order`) VALUES
(1, 'Siyah', 1),
(1, 'Beyaz', 2),
(1, 'Kırmızı', 3),
(1, 'Mavi', 4),
(1, 'Yeşil', 5);

-- Beden Değerleri
INSERT INTO `option_values` (`option_id`, `name`, `sort_order`) VALUES
(2, 'XS', 1),
(2, 'S', 2),
(2, 'M', 3),
(2, 'L', 4),
(2, 'XL', 5),
(2, 'XXL', 6);

-- Kapasite Değerleri
INSERT INTO `option_values` (`option_id`, `name`, `sort_order`) VALUES
(3, '32GB', 1),
(3, '64GB', 2),
(3, '128GB', 3),
(3, '256GB', 4),
(3, '512GB', 5);

-- Varsayılan Vergi Sınıfı
INSERT INTO `tax_classes` (`name`, `description`) VALUES
('Standart KDV', 'Standart %20 KDV oranı');

INSERT INTO `tax_rates` (`tax_class_id`, `name`, `rate`, `type`, `priority`) VALUES
(1, 'KDV %20', 20.00, 'percentage', 1);

-- Varsayılan Tema Ayarları
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`) VALUES
('primary_color', '#2563eb', 'color', 'colors'),
('secondary_color', '#7c3aed', 'color', 'colors'),
('accent_color', '#f59e0b', 'color', 'colors'),
('text_color', '#1f2937', 'color', 'colors'),
('background_color', '#f9fafb', 'color', 'colors'),
('header_bg', '#ffffff', 'color', 'header'),
('header_text', '#1f2937', 'color', 'header'),
('footer_bg', '#1f2937', 'color', 'footer'),
('footer_text', '#ffffff', 'color', 'footer'),
('logo', '', 'image', 'general'),
('favicon', '', 'image', 'general'),
('font_family', 'Inter', 'text', 'typography'),
('border_radius', '8', 'text', 'general');

-- Sosyal Medya Linkleri
INSERT INTO `social_links` (`platform`, `url`, `icon`, `sort_order`, `status`) VALUES
('Facebook', 'https://facebook.com', 'fab fa-facebook-f', 1, 1),
('Instagram', 'https://instagram.com', 'fab fa-instagram', 2, 1),
('Twitter', 'https://twitter.com', 'fab fa-twitter', 3, 1),
('YouTube', 'https://youtube.com', 'fab fa-youtube', 4, 1);

-- Orders tablosuna kupon alanları ekle
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `coupon_code` VARCHAR(50) DEFAULT NULL AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `coupon_discount` DECIMAL(10,2) DEFAULT 0 AFTER `coupon_code`;

-- Products tablosuna ek alanlar
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `weight` DECIMAL(10,2) DEFAULT 0 AFTER `stock`,
ADD COLUMN IF NOT EXISTS `dimensions` VARCHAR(100) DEFAULT NULL AFTER `weight`,
ADD COLUMN IF NOT EXISTS `tax_class_id` INT DEFAULT 1 AFTER `dimensions`,
ADD COLUMN IF NOT EXISTS `rating` DECIMAL(2,1) DEFAULT 0 AFTER `tax_class_id`,
ADD COLUMN IF NOT EXISTS `review_count` INT DEFAULT 0 AFTER `rating`;

-- Pages tablosuna ek alanlar
ALTER TABLE `pages`
ADD COLUMN IF NOT EXISTS `layout_id` INT DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `show_title` TINYINT(1) DEFAULT 1 AFTER `layout_id`,
ADD COLUMN IF NOT EXISTS `show_breadcrumb` TINYINT(1) DEFAULT 1 AFTER `show_title`;

-- İndeksler
CREATE INDEX IF NOT EXISTS `idx_reviews_product` ON `reviews`(`product_id`);
CREATE INDEX IF NOT EXISTS `idx_reviews_status` ON `reviews`(`status`);
CREATE INDEX IF NOT EXISTS `idx_wishlist_customer` ON `wishlist`(`customer_id`);
CREATE INDEX IF NOT EXISTS `idx_coupons_code` ON `coupons`(`code`);
CREATE INDEX IF NOT EXISTS `idx_seo_urls_keyword` ON `seo_urls`(`keyword`);
CREATE INDEX IF NOT EXISTS `idx_slider_items_slider` ON `slider_items`(`slider_id`);
CREATE INDEX IF NOT EXISTS `idx_menu_items_menu` ON `menu_items`(`menu_id`);
