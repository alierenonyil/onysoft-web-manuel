<?php
define('ADMIN_PAGE', true);
require_once '../../includes/config.php';
$pageTitle = 'Müşteri Yönetimi';

$page = max(1, (int)get('page', 1));
$search = get('search', '');

$where = '1=1';
$params = [];
if ($search) {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = '%' . dbEscapeLike($search) . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$total = dbCount('customers', $where, $params);
$pagination = paginate($total, $page, ADMIN_ITEMS_PER_PAGE);

$customers = dbQuery("SELECT * FROM customers WHERE $where ORDER BY created_at DESC LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}", $params);

require_once '../includes/header.php';
?>

<h2><i class="fas fa-users"></i> Müşteri Yönetimi</h2>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Ad, Soyad veya Email ara..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Ara</button>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Ad Soyad</th><th>Email</th><th>Telefon</th><th>Durum</th><th>Kayıt Tarihi</th><th>İşlem</th></tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="text-center py-4">Müşteri bulunamadı</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                            <td><?php echo $customer['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'; ?></td>
                            <td><?php echo formatDate($customer['created_at']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer"><?php echo displayPagination($pagination, 'index.php'); ?></div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
