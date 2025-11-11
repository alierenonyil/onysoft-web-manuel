<?php
define('FRONTEND_PAGE', true);
require_once 'includes/config.php';

$slug = get('slug');
$page = dbQueryOne("SELECT * FROM pages WHERE slug = ? AND status = 1", [$slug]);

if (!$page) {
    header("HTTP/1.0 404 Not Found");
    die('Sayfa bulunamadı');
}

$pageTitle = $page['meta_title'] ?? $page['title'];
$pageDescription = $page['meta_description'] ?? '';

require_once 'includes/header_frontend.php';
?>

<div class="container my-5">
    <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    <hr>
    <div class="content">
        <?php echo nl2br(htmlspecialchars($page['content'])); ?>
    </div>
</div>

<?php require_once 'includes/footer_frontend.php'; ?>
