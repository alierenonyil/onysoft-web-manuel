<?php
// Backup of original index.php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$pageTitle = getSetting('site_name') . ' - ' . getSetting('site_description');

// Get active sliders
$sliders = dbQuery("SELECT * FROM sliders WHERE status = 1 ORDER BY sort_order ASC LIMIT 5");

// Get featured products
$featuredProducts = dbQuery("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.is_featured = 1
    ORDER BY p.sort_order ASC
    LIMIT 8
");
