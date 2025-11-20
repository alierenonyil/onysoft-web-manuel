<?php
/**
 * Security Functions
 * XSS, CSRF, SQL Injection Protection
 */

/**
 * Generate CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF Token Field (HTML)
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * Get CSRF Token Meta Tag
 */
function csrfMeta() {
    $token = generateCsrfToken();
    return '<meta name="csrf-token" content="' . $token . '">';
}

/**
 * Sanitize input (XSS Protection)
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Clean HTML (allow safe tags)
 */
function cleanHtml($html, $allowedTags = null) {
    if ($allowedTags === null) {
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tr><th><td><blockquote><code><pre>';
    }
    return strip_tags($html, $allowedTags);
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (TR format)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10 || strlen($phone) === 11;
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check password strength
 */
function checkPasswordStrength($password, $minLength = 6) {
    if (strlen($password) < $minLength) {
        return ['strong' => false, 'message' => 'Şifre en az ' . $minLength . ' karakter olmalıdır'];
    }

    $strength = 0;
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;

    if ($strength < 2) {
        return ['strong' => false, 'message' => 'Şifre en az harf ve rakam içermelidir'];
    }

    return ['strong' => true, 'message' => 'Şifre güçlü'];
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $errors = [];

    foreach ($fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[$field] = $label . ' alanı zorunludur';
        }
    }

    return $errors;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION[ADMIN_SESSION_NAME]) && $_SESSION[ADMIN_SESSION_NAME] === true;
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isset($_SESSION[CUSTOMER_SESSION_NAME]) && isset($_SESSION['customer_id']);
}

/**
 * Check if user is logged in (alias for isCustomer for frontend)
 */
function isLoggedIn() {
    return isCustomer();
}

/**
 * Get logged in admin
 */
function getAdmin() {
    if (!isAdmin()) {
        return null;
    }
    return $_SESSION['admin_user'] ?? null;
}

/**
 * Get logged in customer
 */
function getCustomer() {
    if (!isCustomer()) {
        return null;
    }

    if (!isset($_SESSION['customer_data'])) {
        $customer = dbQueryOne("SELECT * FROM customers WHERE id = ?", [$_SESSION['customer_id']]);
        $_SESSION['customer_data'] = $customer;
    }

    return $_SESSION['customer_data'];
}

/**
 * Require admin login
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect(siteUrl('admin/login.php'));
    }

    // Check session timeout
    if (isset($_SESSION['admin_last_activity'])) {
        if (time() - $_SESSION['admin_last_activity'] > ADMIN_SESSION_TIMEOUT) {
            session_destroy();
            redirect(siteUrl('admin/login.php?timeout=1'));
        }
    }

    $_SESSION['admin_last_activity'] = time();
}

/**
 * Require customer login
 */
function requireCustomer() {
    if (!isCustomer()) {
        $_SESSION['redirect_after_login'] = getCurrentUrl();
        redirect(siteUrl('login.php'));
    }
}

/**
 * Admin login
 */
function adminLogin($username, $password) {
    $user = dbQueryOne(
        "SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND status = 1",
        [$username, $username]
    );

    if (!$user) {
        return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı'];
    }

    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı'];
    }

    // Update last login
    dbUpdate('admin_users',
        ['last_login' => date('Y-m-d H:i:s')],
        'id = ?',
        [$user['id']]
    );

    // Set session
    $_SESSION[ADMIN_SESSION_NAME] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role']
    ];
    $_SESSION['admin_last_activity'] = time();

    // Log activity
    logActivity($user['id'], 'admin', 'login', 'Admin logged in');

    return ['success' => true, 'user' => $user];
}

/**
 * Admin logout
 */
function adminLogout() {
    if (isAdmin()) {
        logActivity($_SESSION['admin_id'], 'admin', 'logout', 'Admin logged out');
    }

    unset($_SESSION[ADMIN_SESSION_NAME]);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_last_activity']);
}

/**
 * Customer login
 */
function customerLogin($email, $password, $remember = false) {
    $customer = dbQueryOne(
        "SELECT * FROM customers WHERE email = ? AND status = 1",
        [$email]
    );

    if (!$customer) {
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı'];
    }

    if (!verifyPassword($password, $customer['password'])) {
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı'];
    }

    // Update last login
    dbUpdate('customers',
        ['last_login' => date('Y-m-d H:i:s'), 'ip_address' => getClientIP()],
        'id = ?',
        [$customer['id']]
    );

    // Set session
    $_SESSION[CUSTOMER_SESSION_NAME] = true;
    $_SESSION['customer_id'] = $customer['id'];
    unset($customer['password']);
    $_SESSION['customer_data'] = $customer;

    // Remember me (opsiyonel - cookie)
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (86400 * 30), '/');
        // Token'ı veritabanında saklayabilirsiniz
    }

    // Log activity
    logActivity($customer['id'], 'customer', 'login', 'Customer logged in');

    return ['success' => true, 'customer' => $customer];
}

/**
 * Customer logout
 */
function customerLogout() {
    if (isCustomer()) {
        logActivity($_SESSION['customer_id'], 'customer', 'logout', 'Customer logged out');
    }

    unset($_SESSION[CUSTOMER_SESSION_NAME]);
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_data']);

    // Remove remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

/**
 * Customer register
 */
function customerRegister($data) {
    // Validate required fields
    $required = [
        'email' => 'E-posta',
        'password' => 'Şifre',
        'first_name' => 'Ad',
        'last_name' => 'Soyad'
    ];

    $errors = validateRequired($data, $required);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'message' => 'Geçersiz e-posta adresi'];
    }

    // Check if email exists
    if (dbExists('customers', 'email = ?', [$data['email']])) {
        return ['success' => false, 'message' => 'Bu e-posta adresi zaten kayıtlı'];
    }

    // Check password strength
    $passwordCheck = checkPasswordStrength($data['password']);
    if (!$passwordCheck['strong']) {
        return ['success' => false, 'message' => $passwordCheck['message']];
    }

    // Insert customer
    $insertData = [
        'email' => $data['email'],
        'password' => hashPassword($data['password']),
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'] ?? null,
        'newsletter' => isset($data['newsletter']) ? 1 : 0,
        'ip_address' => getClientIP()
    ];

    $customerId = dbInsert('customers', $insertData);

    if ($customerId) {
        logActivity($customerId, 'customer', 'register', 'New customer registered');
        return ['success' => true, 'customer_id' => $customerId];
    }

    return ['success' => false, 'message' => 'Kayıt işlemi başarısız'];
}

/**
 * Rate limiting (basit)
 */
function checkRateLimit($key, $limit = 5, $period = 60) {
    $storageKey = 'rate_limit_' . $key;

    if (!isset($_SESSION[$storageKey])) {
        $_SESSION[$storageKey] = ['count' => 0, 'start' => time()];
    }

    $data = $_SESSION[$storageKey];

    if (time() - $data['start'] > $period) {
        $_SESSION[$storageKey] = ['count' => 1, 'start' => time()];
        return true;
    }

    if ($data['count'] >= $limit) {
        return false;
    }

    $_SESSION[$storageKey]['count']++;
    return true;
}

/**
 * Prevent directory traversal
 */
function preventDirectoryTraversal($path) {
    $path = str_replace(['../', '..\\'], '', $path);
    return $path;
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get POST data
 */
function post($key = null, $default = null) {
    if ($key === null) {
        return $_POST;
    }
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function get($key = null, $default = null) {
    if ($key === null) {
        return $_GET;
    }
    return $_GET[$key] ?? $default;
}

/**
 * JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

