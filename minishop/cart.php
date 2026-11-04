<?php
require __DIR__ . '/includes/init.php';

[$items, $total] = get_cart_items($pdo);

$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>
<h2>Giỏ hàng</h2>

<?php if (!$items): ?>
    <p>Giỏ hàng đang trống. <a href="<?= url('index.php') ?>">Xem sản phẩm</a></p>
<?php else: ?>
    <form action="<?= url('cart-update.php') ?>" method="post">
        <?= csrf_field() ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr data-price="<?= $item['price'] ?>">
                        <td><?= e($item['name']) ?></td>
                        <td><?= format_price($item['price']) ?></td>
                        <td>
                            <input type="number" class="qty" name="qty[<?= $item['id'] ?>]"
                                   value="<?= $item['quantity'] ?>" min="1" max="99">
                        </td>
                        <td class="line-total"><?= format_price($item['line_total']) ?></td>
                        <td>
                            <button type="submit" form="remove-form" name="remove"
                                    value="<?= $item['id'] ?>" class="btn btn-danger">Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Tổng cộng</th>
                    <th id="cart-total"><?= format_price($total) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <p class="cart-actions">
            <button type="submit" name="update" value="1" class="btn">Cập nhật giỏ hàng</button>
            <button type="submit" name="clear" value="1" class="btn btn-danger">Xóa giỏ hàng</button>
            <a href="<?= url('checkout.php') ?>" class="btn btn-accent">Đặt hàng</a>
        </p>
    </form>

    <!-- Form riêng cho các nút "Xóa" từng dòng (nối với nút qua thuộc tính form) -->
    <form id="remove-form" action="<?= url('cart-update.php') ?>" method="post">
        <?= csrf_field() ?>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
