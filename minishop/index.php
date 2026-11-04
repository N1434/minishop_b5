<?php
// Đường dẫn file: project/index.php
require_once __DIR__ . '/includes/init.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
} elseif (!isset($pdo) && function_exists('getDB')) {
    $pdo = getDB();
}

// 1. NHẬN CÁC THAM SỐ BỘ LỌC TỪ URL (Hỗ trợ cả tên biến category hoặc category_id)
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : (isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0);
$keyword    = trim($_GET['q'] ?? $_GET['keyword'] ?? '');
$perPage    = 8; // Yêu cầu Bài 3: Mỗi trang 8 sản phẩm
$page       = max(1, (int)($_GET['page'] ?? 1));

// 2. LẤY DANH SÁCH DANH MỤC CHO BỘ LỌC
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// 3. XÂY DỰNG ĐIỀU KIỆN LỌC (WHERE)
$whereClauses = [];
$params = [];

if ($categoryId > 0) {
    $whereClauses[] = "category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if ($keyword !== '') {
    $whereClauses[] = "name LIKE :keyword";
    $params[':keyword'] = '%' . $keyword . '%';
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 4. ĐẾM TỔNG SỐ SẢN PHẨM THỎA ĐIỀU KIỆN ĐỂ TÍNH SỐ TRANG
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products {$whereSql}");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalProducts / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// 5. TRUY VẤN 8 SẢN PHẨM CỦA TRANG HIỆN TẠI
$sql = "SELECT * FROM products {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm tạo link phân trang giữ nguyên bộ lọc danh mục và từ khóa
function buildPaginationUrl(int $targetPage, int $categoryId, string $keyword): string {
    $queryParams = [];
    if ($categoryId > 0) {
        $queryParams['category'] = $categoryId;
    }
    if ($keyword !== '') {
        $queryParams['q'] = $keyword;
    }
    $queryParams['page'] = $targetPage;
    return 'index.php?' . http_build_query($queryParams);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 1100px; margin: 20px auto; padding: 15px;">
    <h1>Danh sách sản phẩm</h1>

    <!-- Bộ lọc Danh mục & Từ khóa -->
    <form method="get" action="index.php" style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
        <select name="category" style="padding: 8px; min-width: 180px;">
            <option value="0">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" 
               placeholder="Tìm kiếm sản phẩm..." style="padding: 8px; flex: 1; min-width: 200px;">

        <button type="submit" style="padding: 8px 16px; cursor: pointer;">Lọc / Tìm kiếm</button>

        <?php if ($categoryId > 0 || $keyword !== ''): ?>
            <a href="index.php" style="padding: 8px 12px; border: 1px solid #ccc; text-decoration: none; background: #f5f5f5;">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <!-- Lưới hiển thị sản phẩm (8 sản phẩm / trang) -->
    <?php if (empty($products)): ?>
        <p>Không tìm thấy sản phẩm nào phù hợp.</p>
    <?php else: ?>
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px;">
            <?php foreach ($products as $prod): ?>
                <div class="product-card" style="border: 1px solid #ddd; border-radius: 6px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <a href="product.php?id=<?= (int)$prod['id'] ?>" style="text-decoration: none; color: inherit;">
                            <?php if (!empty($prod['image'])): ?>
                                <img src="<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" 
                                     style="width: 100%; height: 180px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;">
                            <?php endif; ?>
                            <h3 style="font-size: 16px; margin: 5px 0;"><?= htmlspecialchars($prod['name']) ?></h3>
                        </a>
                        <p style="color: #d32f2f; font-weight: bold; margin: 8px 0;">
                            <?= number_format((float)$prod['price'], 0, ',', '.') ?> đ
                        </p>
                    </div>

                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                        <a href="product.php?id=<?= (int)$prod['id'] ?>" 
                           style="flex: 1; text-align: center; padding: 6px; border: 1px solid #555; text-decoration: none; border-radius: 4px;">
                            Chi tiết
                        </a>
                        <form method="post" action="cart-add.php" style="flex: 1; margin: 0;">
                            <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" style="width: 100%; padding: 6px; cursor: pointer;">+ Giỏ hàng</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Thanh phân trang giữ nguyên bộ lọc -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 30px; display: flex; justify-content: center; gap: 8px; align-items: center;">
            <?php if ($page > 1): ?>
                <a href="<?= htmlspecialchars(buildPaginationUrl($page - 1, $categoryId, $keyword)) ?>" 
                   style="padding: 6px 12px; border: 1px solid #ccc; text-decoration: none;">&laquo; Trước</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <strong style="padding: 6px 12px; background: #333; color: #fff; border: 1px solid #333;">
                        <?= $i ?>
                    </strong>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(buildPaginationUrl($i, $categoryId, $keyword)) ?>" 
                       style="padding: 6px 12px; border: 1px solid #ccc; text-decoration: none; color: #333;">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= htmlspecialchars(buildPaginationUrl($page + 1, $categoryId, $keyword)) ?>" 
                   style="padding: 6px 12px; border: 1px solid #ccc; text-decoration: none;">Sau &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>