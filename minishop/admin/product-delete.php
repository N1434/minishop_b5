<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/products.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Đã xóa sản phẩm.');
} catch (PDOException $e) {
    // Sản phẩm đã nằm trong đơn hàng: khóa ngoại không cho xóa
    set_flash('error', 'Không thể xóa: sản phẩm đã có trong đơn hàng. Hãy đặt tồn kho về 0 để ngừng bán.');
}

redirect('admin/products.php');
