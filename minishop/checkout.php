<?php
require __DIR__ . '/includes/init.php';
require_login();

[$items, $total] = get_cart_items($pdo);
if (!$items) {
    set_flash('error', 'Giỏ hàng đang trống.');
    redirect('cart.php');
}

// Điền sẵn thông tin nhận hàng từ tài khoản
$stmt = $pdo->prepare('SELECT fullname, phone, address FROM users WHERE id = ?');
$stmt->execute([current_user()['id']]);
$account = $stmt->fetch();

$errors = [];
$fullname = $account['fullname'];
$phone = $account['phone'];
$address = $account['address'];
$note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên phải có từ 2 đến 100 ký tự.';
    }
    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $errors['phone'] = 'Số điện thoại gồm 10 chữ số, bắt đầu bằng số 0.';
    }
    if (mb_strlen($address) < 10 || mb_strlen($address) > 255) {
        $errors['address'] = 'Địa chỉ phải có từ 10 đến 255 ký tự.';
    }
    if (mb_strlen($note) > 500) {
        $errors['note'] = 'Ghi chú không quá 500 ký tự.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // 1. Khóa từng dòng sản phẩm, đọc lại giá và tồn kho mới nhất
            $lock = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE');
            $lines = [];
            $orderTotal = 0;
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $lock->execute([$productId]);
                $product = $lock->fetch();
                if (!$product) {
                    throw new RuntimeException('Một sản phẩm trong giỏ không còn được bán.');
                }
                if ($product['stock'] < $quantity) {
                    throw new RuntimeException(
                        'Sản phẩm "' . $product['name'] . '" chỉ còn ' . $product['stock'] . ' cái.'
                    );
                }
                $product['quantity'] = $quantity;
                $lines[] = $product;
                $orderTotal += $product['price'] * $quantity;
            }

            // 2. Ghi đơn hàng
            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, fullname, phone, address, note, total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([current_user()['id'], $fullname, $phone, $address, $note, $orderTotal]);
            $orderId = (int) $pdo->lastInsertId();

            // 3. Ghi từng dòng hàng và trừ tồn kho
            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $reduceStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($lines as $line) {
                $insertItem->execute([$orderId, $line['id'], $line['name'], $line['price'], $line['quantity']]);
                $reduceStock->execute([$line['quantity'], $line['id']]);
            }

            // 4. Mọi thứ thành công: xác nhận giao dịch
            $pdo->commit();

            unset($_SESSION['cart']);
            set_flash('success', 'Đặt hàng thành công. Mã đơn hàng của bạn là #' . $orderId . '.');
            redirect('order-detail.php?id=' . $orderId);
        } catch (RuntimeException $e) {
            // Lỗi nghiệp vụ (hết hàng...): hủy mọi thay đổi và báo cho người dùng
            $pdo->rollBack();
            $errors['order'] = $e->getMessage();
        } catch (PDOException $e) {
            // Lỗi CSDL: hủy mọi thay đổi, rồi để lỗi tiếp tục nổi lên
            $pdo->rollBack();
            throw $e;
        }
    }
}

$pageTitle = 'Đặt hàng';
require __DIR__ . '/includes/header.php';
?>
<h2>Đặt hàng</h2>

<?php if (isset($errors['order'])): ?>
    <p class="alert alert-error"><?= e($errors['order']) ?></p>
<?php endif; ?>

<h3>Đơn hàng của bạn</h3>
<table>
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Đơn giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['name']) ?></td>
                <td><?= format_price($item['price']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= format_price($item['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3">Tổng cộng</th>
            <th><?= format_price($total) ?></th>
        </tr>
    </tfoot>
</table>

<h3>Thông tin nhận hàng</h3>
<form action="<?= url('checkout.php') ?>" method="post" class="form">
    <?= csrf_field() ?>
    <p>
        <label for="fullname">Người nhận</label><br>
        <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>" required>
        <span class="error"><?= e($errors['fullname'] ?? '') ?></span>
    </p>
    <p>
        <label for="phone">Số điện thoại</label><br>
        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" required>
        <span class="error"><?= e($errors['phone'] ?? '') ?></span>
    </p>
    <p>
        <label for="address">Địa chỉ nhận hàng</label><br>
        <textarea id="address" name="address" rows="3" required><?= e($address) ?></textarea>
        <span class="error"><?= e($errors['address'] ?? '') ?></span>
    </p>
    <p>
        <label for="note">Ghi chú</label><br>
        <textarea id="note" name="note" rows="2"><?= e($note) ?></textarea>
        <span class="error"><?= e($errors['note'] ?? '') ?></span>
    </p>
    <p>
        <button type="submit" class="btn btn-accent">Xác nhận đặt hàng</button>
        <a href="<?= url('cart.php') ?>">Quay lại giỏ hàng</a>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
