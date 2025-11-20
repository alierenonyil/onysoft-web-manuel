<?php
/**
 * Müşteri Yorumları Yönetimi
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

requireAdmin();

$page_title = 'Yorum Yönetimi';

// Durum Güncelleme
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        $id = (int)$_GET['id'];
        $action = $_GET['action'];

        if ($action === 'approve') {
            dbUpdate('reviews', ['status' => 1], 'id = ?', [$id]);
            // Ürün rating güncelle
            $review = dbQueryOne("SELECT product_id FROM reviews WHERE id = ?", [$id]);
            if ($review) {
                updateProductRating($review['product_id']);
            }
            setFlash('success', 'Yorum onaylandı.');
        } elseif ($action === 'reject') {
            dbUpdate('reviews', ['status' => 2], 'id = ?', [$id]);
            setFlash('success', 'Yorum reddedildi.');
        } elseif ($action === 'delete') {
            $review = dbQueryOne("SELECT product_id FROM reviews WHERE id = ?", [$id]);
            dbDelete('reviews', 'id = ?', [$id]);
            if ($review) {
                updateProductRating($review['product_id']);
            }
            setFlash('success', 'Yorum silindi.');
        }
    }
    redirect('/admin/reviews/');
}

// Yanıt Gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $id = (int)$_POST['review_id'];
        $reply = sanitize($_POST['admin_reply']);

        dbUpdate('reviews', [
            'admin_reply' => $reply,
            'reply_date' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);

        setFlash('success', 'Yanıt gönderildi.');
    }
    redirect('/admin/reviews/');
}

// Ürün rating güncelleme fonksiyonu
function updateProductRating($product_id) {
    $stats = dbQueryOne("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ? AND status = 1", [$product_id]);
    dbUpdate('products', [
        'rating' => round($stats['avg_rating'], 1),
        'review_count' => $stats['count']
    ], 'id = ?', [$product_id]);
}

// Filtrele
$status_filter = $_GET['status'] ?? 'pending';
$where = "1=1";
if ($status_filter === 'pending') $where = "r.status = 0";
elseif ($status_filter === 'approved') $where = "r.status = 1";
elseif ($status_filter === 'rejected') $where = "r.status = 2";

$reviews = dbQuery("
    SELECT r.*, p.name as product_name, p.image as product_image
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT 100
");

// İstatistikler
$stats = [
    'pending' => dbCount('reviews', 'status = 0'),
    'approved' => dbCount('reviews', 'status = 1'),
    'rejected' => dbCount('reviews', 'status = 2')
];

include '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-star"></i> Yorum Yönetimi</h1>
</div>

<?php displayFlash(); ?>

<!-- İstatistikler -->
<div class="row mb-4">
    <div class="col-md-4">
        <a href="?status=pending" class="card text-decoration-none <?= $status_filter === 'pending' ? 'border-warning' : '' ?>">
            <div class="card-body text-center">
                <h3 class="text-warning"><?= $stats['pending'] ?></h3>
                <p class="mb-0">Bekleyen</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="?status=approved" class="card text-decoration-none <?= $status_filter === 'approved' ? 'border-success' : '' ?>">
            <div class="card-body text-center">
                <h3 class="text-success"><?= $stats['approved'] ?></h3>
                <p class="mb-0">Onaylı</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="?status=rejected" class="card text-decoration-none <?= $status_filter === 'rejected' ? 'border-danger' : '' ?>">
            <div class="card-body text-center">
                <h3 class="text-danger"><?= $stats['rejected'] ?></h3>
                <p class="mb-0">Reddedilen</p>
            </div>
        </a>
    </div>
</div>

<!-- Yorumlar -->
<?php if (empty($reviews)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
            <p class="text-muted">Henüz yorum bulunmuyor.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($reviews as $review): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <?php if ($review['product_image']): ?>
                            <img src="/uploads/products/<?= htmlspecialchars($review['product_image']) ?>" class="img-thumbnail mb-2" style="max-width: 100px;">
                        <?php endif; ?>
                        <div class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa<?= $i <= $review['rating'] ? 's' : 'r' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6><?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="text-muted small mb-2">
                            <strong><?= htmlspecialchars($review['author_name']) ?></strong>
                            <?php if ($review['is_verified_purchase']): ?>
                                <span class="badge bg-success">Doğrulanmış Alıcı</span>
                            <?php endif; ?>
                            <br>
                            <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                        </p>
                        <?php if ($review['title']): ?>
                            <h6><?= htmlspecialchars($review['title']) ?></h6>
                        <?php endif; ?>
                        <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>

                        <?php if ($review['pros']): ?>
                            <p class="text-success small"><i class="fas fa-plus"></i> <?= htmlspecialchars($review['pros']) ?></p>
                        <?php endif; ?>
                        <?php if ($review['cons']): ?>
                            <p class="text-danger small"><i class="fas fa-minus"></i> <?= htmlspecialchars($review['cons']) ?></p>
                        <?php endif; ?>

                        <?php if ($review['admin_reply']): ?>
                            <div class="alert alert-light mt-2">
                                <strong>Yanıtınız:</strong><br>
                                <?= nl2br(htmlspecialchars($review['admin_reply'])) ?>
                                <br><small class="text-muted"><?= date('d.m.Y H:i', strtotime($review['reply_date'])) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid gap-2">
                            <?php if ($review['status'] == 0): ?>
                                <a href="?action=approve&id=<?= $review['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Onayla
                                </a>
                                <a href="?action=reject&id=<?= $review['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-times"></i> Reddet
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-info btn-sm" onclick="showReplyModal(<?= $review['id'] ?>, '<?= addslashes($review['admin_reply'] ?? '') ?>')">
                                <i class="fas fa-reply"></i> Yanıtla
                            </button>
                            <a href="?action=delete&id=<?= $review['id'] ?>&token=<?= generateCsrfToken() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                <i class="fas fa-trash"></i> Sil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Yanıt Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="review_id" id="reply_review_id">
                <div class="modal-header">
                    <h5 class="modal-title">Yoruma Yanıt Ver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea name="admin_reply" id="admin_reply" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showReplyModal(id, reply) {
    document.getElementById('reply_review_id').value = id;
    document.getElementById('admin_reply').value = reply;
    new bootstrap.Modal(document.getElementById('replyModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
