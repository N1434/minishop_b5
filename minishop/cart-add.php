<?php
require __DIR__ . '/includes/init.php';

// Trang này chỉ xử lý, không hiển thị: chỉ nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

// Sản phẩm phải tồn tại và còn hàng
$stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['stock'] < 1) {
    set_flash('error', 'Sản phẩm không tồn tại hoặc đã hết hàng.');
    redirect('index.php');
}

// Cộng dồn số lượng, không vượt quá tồn kho
$current = $_SESSION['cart'][$productId] ?? 0;
$_SESSION['cart'][$productId] = min($current + $quantity, $product['stock']);

set_flash('success', 'Đã thêm "' . $product['name'] . '" vào giỏ hàng.');
redirect('cart.php');
