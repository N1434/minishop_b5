<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$statuses = order_statuses();

// Cập nhật trạng thái một đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (isset($statuses[$newStatus])) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        set_flash('success', 'Đã cập nhật đơn hàng #' . $orderId . '.');
    }
    redirect('admin/orders.php');
}

// Lọc theo trạng thái: admin/orders.php?status=pending
$filter = $_GET['status'] ?? '';
if (isset($statuses[$filter])) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE status = ? ORDER BY id DESC');
    $stmt->execute([$filter]);
} else {
    $filter = '';
    $stmt = $pdo->query('SELECT * FROM orders ORDER BY id DESC');
}
$orders = $stmt->fetchAll();

$pageTitle = 'Quản lý đơn hàng';
require __DIR__ . '/../includes/header.php';
?>
<h2>Quản lý đơn hàng</h2>

<p class="category-bar">
    <a href="<?= url('admin/orders.php') ?>" class="<?= $filter === '' ? 'active' : '' ?>">Tất cả</a>
    <?php foreach ($statuses as $value => $label): ?>
        <a href="<?= url('admin/orders.php?status=' . $value) ?>"
           class="<?= $filter === $value ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= url('admin/products.php') ?>">&laquo; Quản lý sản phẩm</a>
</p>

<?php if (!$orders): ?>
    <p>Không có đơn hàng nào.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Người nhận</th>
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
                    <td><?= e($order['fullname']) ?><br><?= e($order['phone']) ?></td>
                    <td><?= format_price($order['total']) ?></td>
                    <td>
                        <form action="<?= url('admin/orders.php') ?>" method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status">
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn">Lưu</button>
                        </form>
                    </td>
                    <td><a href="<?= url('order-detail.php?id=' . $order['id']) ?>">Xem</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
