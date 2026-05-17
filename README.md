# StarAvcısı E-Ticaret Sistemi

Modern, güvenli ve özelleştirilebilir **PHP + MySQL** tabanlı e-ticaret platformu. Çok kategorili ürün yönetimi, sanal POS entegrasyonu altyapısı ve kapsamlı admin paneli içerir.

## ✨ Özellikler

### 🛒 Müşteri Tarafı (Frontend)

- **Modern ve Responsive Tasarım** — Mobil uyumlu, mobile-first arayüz
- **Ürün Kataloğu** — Detaylı ürün listeleme, arama ve hızlı önizleme
- **Gelişmiş Filtreleme** — Kategori, fiyat, sıralama filtreleri
- **Sepet Sistemi** — Session bazlı, kullanıcı bazlı hibrit sepet
- **Güvenli Ödeme** — Sanal POS entegrasyonu hazır altyapı (iyzico, PayTR)
- **Kullanıcı Hesapları** — Kayıt, giriş, sipariş takibi, adres yönetimi
- **Slider Yönetimi** — Dinamik ana sayfa slider
- **SEO Uyumlu** — Meta tags, breadcrumb, temiz URL'ler

### 🎛️ Admin Paneli

- **Dashboard** — Satış istatistikleri ve özet raporlar
- **Ürün Yönetimi** — Sınırsız ürün, varyant, stok takibi
- **Kategori Yönetimi** — Sınırsız alt kategori desteği
- **Sipariş Yönetimi** — Sipariş durumu, fatura, kargo takibi
- **Müşteri Yönetimi** — Müşteri bilgileri ve sipariş geçmişi
- **Slider Yönetimi** — Ana sayfa slider düzenleme
- **Sayfa Yönetimi** — Hakkımızda, İletişim vb. statik sayfalar
- **Site Ayarları** — Genel ayarlar, SMTP, ödeme sistemleri

### 🔒 Güvenlik

- **SQL Injection Koruması** — PDO prepared statements
- **XSS Koruması** — Input sanitization, output escaping
- **CSRF Koruması** — Token tabanlı doğrulama
- **Password Hashing** — Bcrypt algoritması
- **Session Güvenliği** — Secure session yönetimi, regenerate ID
- **Rate Limiting** — Brute force koruması (giriş denemeleri)

## 🛠️ Sistem Gereksinimleri

- **PHP** 7.4 veya üzeri (PHP 8.x önerilir)
- **MySQL** 5.7 veya üzeri / MariaDB 10.2+
- **Web sunucusu:** Apache (mod_rewrite ile) veya Nginx
- **PHP Extensions:** PDO, pdo_mysql, gd, mbstring, json, curl

## 🚀 Kurulum

### 1. Repoyu klonlayın veya dosyaları yükleyin

```bash
git clone https://github.com/alierenonyil/onysoft-web-manuel.git
cd onysoft-web-manuel
```

### 2. Veritabanı yapılandırması

`includes/config.php` dosyasını **kendi sunucu bilgilerinize göre düzenleyin**:

```php
define('DB_HOST', 'localhost');           // Veritabanı host
define('DB_NAME', 'veritabani_adi');      // Veritabanı adı
define('DB_USER', 'kullanici_adi');       // Veritabanı kullanıcısı
define('DB_PASS', 'guvenli_sifre');       // Veritabanı şifresi
```

> ⚠️ **Güvenlik notu:** Production ortamında veritabanı bilgilerini doğrudan `config.php`'ye yazmak yerine `.env` dosyası kullanmanız önerilir. Repodaki `.gitignore` dosyası `.env`'i otomatik olarak hariç tutar.

### 3. Otomatik Kurulum

Tarayıcınızdan `http://yourdomain.com/install.php` adresine giderek kurulum sihirbazını takip edin:

1. Gereksinim kontrolü (PHP versiyonu, eklentiler, izinler)
2. Veritabanı tablolarının oluşturulması
3. Site temel yapılandırması
4. Admin hesabı oluşturma

### 4. Klasör izinlerini ayarlayın

```bash
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/sliders/
chmod 755 uploads/pages/
chmod 600 includes/config.php   # Sadece sahibi okusun
```

### 5. Admin paneline giriş

- **URL:** `http://yourdomain.com/admin/`
- **Varsayılan kullanıcı:** Kurulum sihirbazında oluşturduğunuz hesap

> ⚠️ **ÖNEMLİ:** Kurulumdan sonra mutlaka `install.php` dosyasını silin veya rename edin.

## 📁 Dizin Yapısı

```
onysoft-web-manuel/
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
├── index.php              # Ana sayfa
├── product.php            # Ürün detay
├── category.php           # Kategori listeleme
├── cart.php               # Sepet
├── checkout.php           # Ödeme
├── database.sql           # Veritabanı şeması
└── install.php            # Kurulum sihirbazı
```

## ⚙️ Yapılandırma

### Site Ayarları

`includes/config.php`:

```php
define('SITE_URL', 'http://yourdomain.com');
define('MAX_FILE_SIZE', 5242880);   // 5MB
define('ITEMS_PER_PAGE', 12);
define('TAX_RATE', 20);              // %20 KDV
```

### E-posta Ayarları (SMTP)

Admin panelden **Ayarlar → Email** bölümünden SMTP ayarlarını yapın.

### Ödeme Sistemleri

Admin panelden **Ayarlar → Payment**:

- **iyzico:** API anahtarları
- **PayTR:** Merchant bilgileri

## 💳 Sanal POS Entegrasyonu

### iyzico

1. [iyzico.com](https://www.iyzico.com) üzerinden hesap oluşturun
2. API anahtarlarınızı (`api_key` + `secret_key`) alın
3. Admin panelden girişlerinizi yapın

### PayTR

1. [paytr.com](https://www.paytr.com) üzerinden hesap oluşturun
2. Merchant bilgilerinizi (`merchant_id`, `merchant_key`, `merchant_salt`) alın
3. Admin panelden girişlerinizi yapın

## 🔐 Güvenlik Önerileri

1. **İlk kurulumdan sonra:**
   - `install.php` dosyasını silin
   - Admin şifresini güçlü bir parolaya değiştirin
   - Veritabanı şifresini güçlü yapın (en az 16 karakter, karışık)

2. **Production ortamında:**
   - `config.php` içinde `display_errors` kapalı olsun
   - HTTPS (SSL/TLS) kullanın — Let's Encrypt ücretsizdir
   - Düzenli yedek alın (`database.sql` + `uploads/`)
   - PHP ve MySQL güvenlik güncellemelerini takip edin

3. **Dosya izinleri:**
   - Klasörler: `755`
   - Dosyalar: `644`
   - `includes/config.php`: `600` (önerilir)

## 🎨 Özelleştirme

### Tema Değiştirme

CSS dosyaları `assets/css/` klasöründe:

- `style.css` — Frontend stilleri
- `admin.css` — Admin panel stilleri

### Logo ve Favicon

Admin panelden **Ayarlar → Genel** bölümünden logo ve favicon yükleyebilirsiniz.

### Footer Metni

Admin panelden **Ayarlar → Genel → Footer Text** alanından değiştirilebilir.

## 📜 Sürüm Geçmişi

### v1.0.0 (2025)

- İlk sürüm
- Temel e-ticaret özellikleri (ürün, kategori, sepet, sipariş)
- Admin paneli
- Güvenlik özellikleri (SQL injection, XSS, CSRF korumaları)
- Sanal POS entegrasyon altyapısı (iyzico, PayTR)

## 👥 Katkıda Bulunanlar (Contributors)

- **Ali Eren Onyıl** — [@alierenonyil](https://github.com/alierenonyil) — Tasarım, geliştirme, mimari

## 🌐 Geliştirici

**Ali Eren Onyıl**
Onysoft Veri Merkezi A.Ş. — Kurucu & Full-Stack Developer

- 🌍 Portfolyo: [ali.onysoft.com](https://ali.onysoft.com)
- 💼 LinkedIn: [linkedin.com/in/alierenonyil](https://linkedin.com/in/alierenonyil)
- 🐙 GitHub: [github.com/alierenonyil](https://github.com/alierenonyil)
- 📧 E-posta: alieren@onysoft.com

## 📄 Lisans

Bu proje portfolyo ve eğitim amaçlı paylaşılmıştır. Ticari kullanım için iletişime geçiniz.
