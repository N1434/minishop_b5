<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra quyền Admin (điều chỉnh theo hàm có sẵn trong includes/functions.php của em)
if (function_exists('requireAdmin')) {
    requireAdmin();
} elseif (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// 1. XỬ LÝ FORM SUBMIT (THÊM / SỬA / XÓA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm danh mục mới
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Tên danh mục không được để trống.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $success = 'Thêm danh mục thành công!';
        }
    }

    // Sửa danh mục
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id <= 0 || $name === '') {
            $error = 'Dữ liệu cập nhật không hợp lệ.';
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            $success = 'Cập nhật danh mục thành công!';
        }
    }

    // Xóa danh mục (Kiểm tra ràng buộc sản phẩm trước khi xóa)
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Đếm số sản phẩm đang thuộc danh mục này
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $checkStmt->execute([':id' => $id]);
        $productCount = (int)$checkStmt->fetchColumn();

        if ($productCount > 0) {
            $error = "Không thể xóa! Danh mục này đang có {$productCount} sản phẩm.";
        } else {
            $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $delStmt->execute([':id' => $id]);
            $success = 'Đã xóa danh mục thành công!';
        }
    }
}

// 2. LẤY DỮ LIỆU ĐỂ HIỂN THỊ TRÊN FORM SỬA (NẾU CÓ ?edit=ID)
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. LẤY DANH SÁCH DANH MỤC KÈM SỐ LƯỢNG SẢN PHẨM
$sql = "SELECT c.*, COUNT(p.id) AS product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.id DESC";
$categories = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Quản lý danh mục</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="color: red; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="color: green; margin-bottom: 15px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Form Thêm / Sửa Danh Mục -->
    <form method="post" action="categories.php" style="margin-bottom: 25px;">
        <input type="hidden" name="action" value="<?= $editCategory ? 'edit' : 'add' ?>">
        <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?= (int)$editCategory['id'] ?>">
        <?php endif; ?>

        <label for="name"><strong><?= $editCategory ? 'Sửa danh mục:' : 'Thêm danh mục mới:' ?></strong></label>
        <input type="text" id="name" name="name" required 
               value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" 
               placeholder="Nhập tên danh mục...">
        <button type="submit"><?= $editCategory ? 'Cập nhật' : 'Thêm mới' ?></button>
        <?php if ($editCategory): ?>
            <a href="categories.php">Hủy sửa</a>
        <?php endif; ?>
    </form>

    <!-- Bảng Danh Sách Danh Mục -->
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Số sản phẩm</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= (int)$cat['id'] ?></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= (int)$cat['product_count'] ?></td>
                    <td>
                        <a href="categories.php?edit=<?= (int)$cat['id'] ?>">Sửa</a>
                        
                        <form method="post" action="categories.php" style="display:inline;" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                            <button type="submit" <?= $cat['product_count'] > 0 ? 'disabled title="Đang có sản phẩm, không thể xóa"' : '' ?>>
                                Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>