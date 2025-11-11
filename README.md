# StarAvcısı E-Ticaret Sistemi

Modern, güvenli ve özelleştirilebilir PHP + MySQL tabanlı e-ticaret platformu.

## Özellikler

### 🛒 Müşteri Tarafı (Frontend)
- **Modern ve Responsive Tasarım** - Mobil uyumlu arayüz
- **Ürün Kataloğu** - Detaylı ürün listeleme ve arama
- **Gelişmiş Filtreleme** - Kategori, fiyat, sıralama filtreleri
- **Sepet Sistemi** - Kolay sepet yönetimi
- **Güvenli Ödeme** - Sanal POS entegrasyonu hazır (iyzico, PayTR)
- **Kullanıcı Hesapları** - Kayıt, giriş, sipariş takibi
- **Slider Yönetimi** - Dinamik ana sayfa slider
- **SEO Uyumlu** - Meta tags, breadcrumb, temiz URL'ler

### 🎛️ Admin Paneli
- **Dashboard** - İstatistikler ve raporlar
- **Ürün Yönetimi** - Sınırsız ürün, varyant, stok takibi
- **Kategori Yönetimi** - Sınırsız alt kategori desteği
- **Sipariş Yönetimi** - Sipariş durumu, fatura, kargo takibi
- **Müşteri Yönetimi** - Müşteri bilgileri ve sipariş geçmişi
- **Slider Yönetimi** - Ana sayfa slider düzenleme
- **Sayfa Yönetimi** - Hakkımızda, İletişim vb. statik sayfalar
- **Site Ayarları** - Genel ayarlar, SMTP, ödeme sistemleri

### 🔒 Güvenlik
- **SQL Injection Koruması** - PDO prepared statements
- **XSS Koruması** - Input sanitization
- **CSRF Koruması** - Token-based validation
- **Password Hashing** - Bcrypt algoritması
- **Session Güvenliği** - Secure session yönetimi
- **Rate Limiting** - Brute force koruması

## Sistem Gereksinimleri

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri / MariaDB 10.2+
- Apache/Nginx web sunucusu
- PHP Extensions:
  - PDO
  - pdo_mysql
  - gd (görsel işleme)
  - mbstring
  - json

## Kurulum

### 1. Dosyaları Yükleyin

Tüm dosyaları web sunucunuzun root dizinine yükleyin.

### 2. Veritabanı Yapılandırması

Veritabanı bilgileriniz `includes/config.php` dosyasında tanımlıdır:

```php
define('DB_HOST', 'staravcisi.com');
define('DB_NAME', 'wawahousesql');
define('DB_USER', 'wawahousekullanici');
define('DB_PASS', 'guvenli_sifre');
```

### 3. Otomatik Kurulum

Tarayıcınızda `http://yourdomain.com/install.php` adresine gidin ve kurulum sihirbazını takip edin.

**Kurulum Adımları:**
1. Gereksinim kontrolü
2. Veritabanı kurulumu
3. Site yapılandırması

### 4. İzinleri Ayarlayın

```bash
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/sliders/
chmod 755 uploads/pages/
```

### 5. Admin Paneline Giriş

**URL:** `http://yourdomain.com/admin/`

**Varsayılan Giriş Bilgileri:**
- Kullanıcı Adı: `admin`
- Şifre: `admin123`

⚠️ **ÖNEMLİ:** İlk girişten sonra şifrenizi mutlaka değiştirin!

## Dizin Yapısı

```
/
├── admin/                  # Admin paneli
│   ├── products/          # Ürün yönetimi
│   ├── categories/        # Kategori yönetimi
│   ├── orders/            # Sipariş yönetimi
│   ├── customers/         # Müşteri yönetimi
│   ├── sliders/           # Slider yönetimi
│   ├── pages/             # Sayfa yönetimi
│   └── settings/          # Site ayarları
├── assets/                # CSS, JS, resimler
│   ├── css/
│   ├── js/
│   └── images/
├── includes/              # Core dosyalar
│   ├── config.php         # Yapılandırma
│   ├── database.php       # Veritabanı fonksiyonları
│   ├── functions.php      # Yardımcı fonksiyonlar
│   └── security.php       # Güvenlik fonksiyonları
├── uploads/               # Yüklenen dosyalar
│   ├── products/
│   ├── sliders/
│   └── pages/
├── index.php              # Ana sayfa
├── product.php            # Ürün detay
├── category.php           # Kategori listeleme
├── cart.php               # Sepet
├── checkout.php           # Ödeme
├── database.sql           # Veritabanı şeması
└── install.php            # Kurulum sihirbazı
```

## Yapılandırma

### Site Ayarları

`includes/config.php` dosyasından temel ayarları yapılandırabilirsiniz:

```php
// Site URL
define('SITE_URL', 'http://staravcisi.com');

// Maksimum dosya boyutu (5MB)
define('MAX_FILE_SIZE', 5242880);

// Sayfa başına ürün sayısı
define('ITEMS_PER_PAGE', 12);

// KDV Oranı
define('TAX_RATE', 20);
```

### Email Ayarları (SMTP)

Admin panelden **Ayarlar > Email** bölümünden SMTP ayarlarını yapılandırabilirsiniz.

### Ödeme Sistemleri

Admin panelden **Ayarlar > Payment** bölümünden:
- iyzico API anahtarları
- PayTR merchant bilgileri

yapılandırılabilir.

## Sanal POS Entegrasyonu

### iyzico

1. [iyzico.com](https://www.iyzico.com) adresinden hesap oluşturun
2. API anahtarlarınızı alın
3. Admin panelden girişlerinizi yapın

### PayTR

1. [paytr.com](https://www.paytr.com) adresinden hesap oluşturun
2. Merchant bilgilerinizi alın
3. Admin panelden girişlerinizi yapın

## Güvenlik Önerileri

1. **İlk kurulumdan sonra:**
   - `install.php` dosyasını silin
   - Admin şifresini değiştirin
   - Veritabanı şifresini güçlü yapın

2. **Production ortamında:**
   - `config.php` içinde `display_errors` kapalı olmalı
   - HTTPS kullanın
   - Düzenli yedek alın
   - Güvenlik güncellemelerini takip edin

3. **Dosya İzinleri:**
   - Klasörler: 755
   - Dosyalar: 644
   - `includes/config.php`: 600 (önerilir)

## Özelleştirme

### Tema Değiştirme

CSS dosyaları `assets/css/` klasöründe:
- `style.css` - Frontend stilleri
- `admin.css` - Admin panel stilleri

### Logo ve Favicon

Admin panelden **Ayarlar > Genel** bölümünden logo ve favicon yükleyebilirsiniz.

### Footer Metni

Admin panelden **Ayarlar > Genel > Footer Text** alanından değiştirilebilir.

## Destek

Sorularınız için:
- Email: info@staravcisi.com
- GitHub Issues: [Proje Repository]

## Lisans

Bu proje özel kullanım için geliştirilmiştir.

## Sürüm Geçmişi

### v1.0.0 (2025)
- İlk sürüm
- Temel e-ticaret özellikleri
- Admin paneli
- Güvenlik özellikleri
- Sanal POS altyapısı

## Katkıda Bulunanlar

- **Geliştirici:** Claude AI
- **Müşteri:** StarAvcısı

---

**Teşekkürler!**
StarAvcısı E-Ticaret Sistemini tercih ettiğiniz için teşekkür ederiz.
