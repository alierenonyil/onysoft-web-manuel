<?php
/**
 * General Helper Functions
 */

/**
 * Redirect to URL
 */
function redirect($url, $permanent = false) {
    if (headers_sent() === false) {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
    }
    exit();
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get site URL
 */
function siteUrl($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path) {
    return siteUrl('assets/' . ltrim($path, '/'));
}

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');

    $turkish = ['ç', 'ğ', 'ı', 'i', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'];
    $english = ['c', 'g', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'];
    $string = str_replace($turkish, $english, $string);

    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');

    return $string;
}

/**
 * Format price
 */
function formatPrice($price, $showCurrency = true) {
    $formatted = number_format((float)$price, 2, ',', '.');
    return $showCurrency ? $formatted . ' ' . CURRENCY_SYMBOL : $formatted;
}

/**
 * Format date
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Time ago format
 */
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'yıl',
        'm' => 'ay',
        'w' => 'hafta',
        'd' => 'gün',
        'h' => 'saat',
        'i' => 'dakika',
        's' => 'saniye',
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' önce' : 'şimdi';
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Clean text
 */
function cleanText($text) {
    $text = trim($text);
    $text = stripslashes($text);
    return $text;
}

/**
 * Flash message functions
 */
function setFlash($key, $message, $type = 'info') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function hasFlash($key) {
    return isset($_SESSION['flash'][$key]);
}

/**
 * Display flash message
 */
function displayFlash($key) {
    $flash = getFlash($key);
    if ($flash) {
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        $class = $alertClass[$flash['type']] ?? 'alert-info';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * File upload function
 */
function uploadFile($file, $destination, $allowedTypes = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Geçersiz dosya'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Dosya yükleme hatası'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Dosya boyutu çok büyük'];
    }

    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Geçersiz dosya tipi'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), ALLOWED_IMAGE_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Geçersiz dosya uzantısı'];
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = rtrim($destination, '/') . '/' . $filename;

    if (!is_dir(dirname($uploadPath))) {
        mkdir(dirname($uploadPath), 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'error' => 'Dosya yüklenemedi'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => $uploadPath];
}

/**
 * Delete file
 */
function deleteFile($path) {
    if (file_exists($path) && is_file($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Get site settings
 */
function getSetting($key, $default = null) {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        $results = dbQuery("SELECT setting_key, setting_value FROM site_settings");
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Update site setting
 */
function updateSetting($key, $value) {
    return dbUpdate('site_settings',
        ['setting_value' => $value],
        'setting_key = ?',
        [$key]
    );
}

/**
 * Get categories tree
 */
function getCategoriesTree($parentId = 0) {
    $categories = dbQuery(
        "SELECT * FROM categories WHERE parent_id = ? AND status = 1 ORDER BY sort_order ASC, name ASC",
        [$parentId]
    );

    $tree = [];
    foreach ($categories as $category) {
        $category['children'] = getCategoriesTree($category['id']);
        $tree[] = $category;
    }

    return $tree;
}

/**
 * Get category breadcrumb
 */
function getCategoryBreadcrumb($categoryId) {
    $breadcrumb = [];

    while ($categoryId > 0) {
        $category = dbQueryOne("SELECT id, name, slug, parent_id FROM categories WHERE id = ?", [$categoryId]);
        if (!$category) break;

        array_unshift($breadcrumb, $category);
        $categoryId = $category['parent_id'];
    }

    return $breadcrumb;
}

/**
 * Calculate cart total
 */
function getCartTotal($cartItems) {
    $total = 0;
    foreach ($cartItems as $item) {
        $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
        $total += $price * $item['quantity'];
    }
    return $total;
}

/**
 * Calculate tax
 */
function calculateTax($amount, $taxRate = TAX_RATE) {
    return $amount * ($taxRate / 100);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(generateRandomString(8));
}

/**
 * Send email (basit mail fonksiyonu)
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];

    $headers = array_merge($defaultHeaders, $headers);
    $headerString = implode("\r\n", $headers);

    return mail($to, $subject, $message, $headerString);
}

/**
 * Log activity
 */
function logActivity($userId, $userType, $action, $description = null) {
    $data = [
        'user_id' => $userId,
        'user_type' => $userType,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    return dbInsert('activity_logs', $data);
}

/**
 * Get client IP
 */
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Pagination helper
 */
function paginate($totalItems, $currentPage = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;

    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Display pagination
 */
function displayPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav aria-label="Sayfa navigasyonu"><ul class="pagination justify-content-center">';

    // Previous
    if ($pagination['has_prev']) {
        $url = $baseUrl . '?page=' . ($pagination['current_page'] - 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">Önceki</a></li>';
    }

    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $pagination['current_page']) ? ' active' : '';
        $url = $baseUrl . '?page=' . $i;
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }

    // Next
    if ($pagination['has_next']) {
        $url = $baseUrl . '?page=' . ($pagination['current_page'] + 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">Sonraki</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Debug helper
 */
function dd($data, $die = true) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ($die) die();
}
