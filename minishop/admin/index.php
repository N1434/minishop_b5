<?php
// Đường dẫn file: project/admin/index.php
require_once __DIR__ . '/../includes/init.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
} elseif (!isset($pdo) && function_exists('getDB')) {
    $pdo = getDB();
}

// Kiểm tra quyền Admin
if (function_exists('require_admin')) {
    require_admin();
} elseif (function_exists('requireAdmin')) {
    requireAdmin();
} elseif (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Tự động nhận diện tên cột tiền đơn hàng (total_amount hoặc total) và cột tồn kho (stock hoặc quantity)
$orderColumns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
$totalCol = in_array('total_amount', $orderColumns, true) ? 'total_amount' : (in_array('total', $orderColumns, true) ? 'total' : 'total_price');

$productColumns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
$stockCol = in_array('stock', $productColumns, true) ? 'stock' : (in_array('quantity', $productColumns, true) ? 'quantity' : 'stock');

// 1. Thống kê số đơn theo từng trạng thái (Dùng COUNT và GROUP BY)
$statusSql = "SELECT status, COUNT(*) AS total_orders 
              FROM orders 
              GROUP BY status";
$ordersByStatus = $pdo->query($statusSql)->fetchAll(PDO::FETCH_ASSOC);

// 2. Doanh thu các đơn hoàn tất (Dùng SUM)
$revenueSql = "SELECT COALESCE(SUM({$totalCol}), 0) AS completed_revenue 
               FROM orders 
               WHERE status IN ('completed', 'hoan_tat', 'done', 'delivered')";
$completedRevenue = (float)$pdo->query($revenueSql)->fetchColumn();

// 3. Lấy 5 sản phẩm sắp hết hàng
$lowStockSql = "SELECT id, name, price, {$stockCol} AS stock_qty 
                FROM products 
                ORDER BY {$stockCol} ASC 
                LIMIT 5";
$lowStockProducts = $pdo->query($lowStockSql)->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 950px; margin: 20px auto; padding: 15px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Trang tổng quan quản trị</h1>
        <div>
            <a href="categories.php">Danh mục</a> | 
            <a href="products.php">Sản phẩm</a> | 
            <a href="orders.php">Đơn hàng</a>
        </div>
    </div>

    <!-- Khối 1: Tổng doanh thu đơn hoàn tất -->
    <div style="background: #e8f5e9; border: 1px solid #a5d6a7; padding: 18px; border-radius: 6px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 8px 0;">Doanh thu các đơn hoàn tất</h3>
        <div style="font-size: 26px; font-weight: bold; color: #2e7d32;">
            <?= number_format($completedRevenue, 0, ',', '.') ?> đ
        </div>
    </div>

    <!-- Khối 2: Số đơn hàng theo từng trạng thái -->
    <div style="margin-bottom: 25px;">
        <h3>Số đơn hàng theo từng trạng thái</h3>
        <table border="1" cellpadding="10" cellspacing="0" width="100%" style="border-collapse: collapse;">
            <thead style="background: #f1f1f1;">
                <tr>
                    <th>Trạng thái đơn hàng</th>
                    <th width="200">Số lượng đơn</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordersByStatus)): ?>
                    <tr>
                        <td colspan="2" style="text-align: center;">Chưa có đơn hàng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordersByStatus as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$item['status']) ?></td>
                            <td style="text-align: center; font-weight: bold;"><?= (int)$item['total_orders'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Khối 3: 5 sản phẩm sắp hết hàng -->
    <div>
        <h3>5 sản phẩm sắp hết hàng</h3>
        <table border="1" cellpadding="10" cellspacing="0" width="100%" style="border-collapse: collapse;">
            <thead style="background: #f1f1f1;">
                <tr>
                    <th width="80">ID</th>
                    <th>Tên sản phẩm</th>
                    <th width="180">Giá bán</th>
                    <th width="150">Tồn kho</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockProducts)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Chưa có sản phẩm nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $prod): ?>
                        <tr>
                            <td style="text-align: center;"><?= (int)$prod['id'] ?></td>
                            <td><?= htmlspecialchars($prod['name']) ?></td>
                            <td style="text-align: right;"><?= number_format((float)$prod['price'], 0, ',', '.') ?> đ</td>
                            <td style="text-align: center; font-weight: bold; color: <?= (int)$prod['stock_qty'] <= 5 ? '#d32f2f' : '#333' ?>;">
                                <?= (int)$prod['stock_qty'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>