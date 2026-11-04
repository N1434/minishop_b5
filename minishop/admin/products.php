<?php
require __DIR__ . '/../includes/init.php';
require_admin();

// Phân trang: mỗi trang 10 sản phẩm
$perPage = 10;
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);

$stmt = $pdo->prepare(
    'SELECT p.id, p.name, p.price, p.stock, p.image, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     ORDER BY p.id DESC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$pageTitle = 'Quản lý sản phẩm';
require __DIR__ . '/../includes/header.php';
?>
<h2>Quản lý sản phẩm</h2>

<p class="cart-actions">
    <a href="<?= url('admin/product-form.php') ?>" class="btn btn-accent">Thêm sản phẩm</a>
    <a href="<?= url('admin/orders.php') ?>" class="btn">Quản lý đơn hàng</a>
</p>

<table>
    <thead>
        <tr>
            <th>Mã</th>
            <th>Ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Danh mục</th>
            <th>Giá</th>
            <th>Tồn kho</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= $product['id'] ?></td>
                <td><img src="<?= url($product['image']) ?>" alt="" class="thumb"></td>
                <td><?= e($product['name']) ?></td>
                <td><?= e($product['category_name']) ?></td>
                <td><?= format_price($product['price']) ?></td>
                <td><?= $product['stock'] ?></td>
                <td>
                    <a href="<?= url('admin/product-form.php?id=' . $product['id']) ?>" class="btn">Sửa</a>
                    <form action="<?= url('admin/product-delete.php') ?>" method="post" class="inline-form"
                          onsubmit="return confirm('Xóa sản phẩm này?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= url('admin/products.php?page=' . $i) ?>"
           class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
