<?php
require __DIR__ . '/includes/init.php';
require_login();

// Chỉ lấy đơn của người đang đăng nhập
$stmt = $pdo->prepare(
    'SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC'
);
$stmt->execute([current_user()['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Đơn hàng của tôi';
require __DIR__ . '/includes/header.php';
?>
<h2>Đơn hàng của tôi</h2>

<?php if (!$orders): ?>
    <p>Bạn chưa có đơn hàng nào. <a href="<?= url('index.php') ?>">Xem sản phẩm</a></p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= format_price($order['total']) ?></td>
                    <td><?= e(order_status_label($order['status'])) ?></td>
                    <td><a href="<?= url('order-detail.php?id=' . $order['id']) ?>">Xem</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
