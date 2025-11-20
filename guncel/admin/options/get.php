<?php
/**
 * Seçenek Detayları API
 */
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';

requireAdmin();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$option = dbQueryOne("SELECT * FROM options WHERE id = ?", [$id]);

if (!$option) {
    echo json_encode(['error' => 'Seçenek bulunamadı']);
    exit;
}

$values = dbQuery("SELECT * FROM option_values WHERE option_id = ? ORDER BY sort_order", [$id]);

echo json_encode([
    'option' => $option,
    'values' => $values
]);
