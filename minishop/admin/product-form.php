<?php
// Đường dẫn file: project/admin/product-form.php
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

// Tự động nhận diện tên cột tồn kho trong bảng products (stock hoặc quantity)
$productColumns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
$stockCol = in_array('stock', $productColumns, true) ? 'stock' : (in_array('quantity', $productColumns, true) ? 'quantity' : 'stock');
$hasCategoryCol = in_array('category_id', $productColumns, true);
$hasDescCol = in_array('description', $productColumns, true);

// Lấy danh sách danh mục để hiển thị trong thẻ <select>
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Kiểm tra xem đang ở chế độ Sửa (?id=...) hay Thêm mới
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$product = [
    'name'        => '',
    'category_id' => 0,
    'price'       => '',
    $stockCol     => 0,
    'image'       => '',
    'description' => ''
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existingProduct) {
        header('Location: products.php');
        exit;
    }
    $product = $existingProduct;
}

$error = '';

// XỬ LÝ KHI SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price      = (float)($_POST['price'] ?? 0);
    $stock      = (int)($_POST['stock'] ?? 0);
    $desc       = trim($_POST['description'] ?? '');
    $imagePath  = $product['image'] ?? '';

    if ($name === '') {
        $error = 'Tên sản phẩm không được để trống.';
    } elseif ($price < 0) {
        $error = 'Giá sản phẩm không hợp lệ.';
    } elseif ($stock < 0) {
        $error = 'Số lượng tồn kho không hợp lệ.';
    } else {
        // Xử lý tải ảnh mới nếu người dùng có chọn file
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $error = 'Định dạng ảnh không hợp lệ (chỉ chấp nhận jpg, jpeg, png, gif, webp).';
            } else {
                $newFileName = uniqid('product_', true) . '.' . $ext;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                    // ==========================================================
                    // YÊU CẦU BÀI 2: XÓA ẢNH CŨ TRONG UPLOADS BẰNG UNLINK()
                    // Chỉ xóa khi đang sửa sản phẩm và đường dẫn cũ bắt đầu bằng 'uploads/'
                    // ==========================================================
                    $oldImage = $product['image'] ?? '';
                    if ($isEdit && !empty($oldImage) && strpos($oldImage, 'uploads/') === 0) {
                        $oldFileFullPath = __DIR__ . '/../' . $oldImage;
                        if (is_file($oldFileFullPath)) {
                            unlink($oldFileFullPath);
                        }
                    }

                    // Cập nhật đường dẫn ảnh mới để lưu vào DB
                    $imagePath = 'uploads/' . $newFileName;
                } else {
                    $error = 'Không thể lưu tệp ảnh tải lên.';
                }
            }
        }

        // Lưu vào cơ sở dữ liệu nếu không có lỗi
        if ($error === '') {
            if ($isEdit) {
                $setParts = ["name = :name", "price = :price", "{$stockCol} = :stock", "image = :image"];
                $params = [
                    ':name'  => $name,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $imagePath,
                    ':id'    => $id
                ];
                if ($hasCategoryCol) {
                    $setParts[] = "category_id = :category_id";
                    $params[':category_id'] = $categoryId > 0 ? $categoryId : null;
                }
                if ($hasDescCol) {
                    $setParts[] = "description = :description";
                    $params[':description'] = $desc;
                }

                $updateSql = "UPDATE products SET " . implode(', ', $setParts) . " WHERE id = :id";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute($params);
            } else {
                $cols = ['name', 'price', $stockCol, 'image'];
                $vals = [':name', ':price', ':stock', ':image'];
                $params = [
                    ':name'  => $name,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $imagePath
                ];
                if ($hasCategoryCol) {
                    $cols[] = 'category_id';
                    $vals[] = ':category_id';
                    $params[':category_id'] = $categoryId > 0 ? $categoryId : null;
                }
                if ($hasDescCol) {
                    $cols[] = 'description';
                    $vals[] = ':description';
                    $params[':description'] = $desc;
                }

                $insertSql = "INSERT INTO products (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute($params);
            }

            header('Location: products.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 700px; margin: 20px auto; padding: 15px;">
    <h1><?= $isEdit ? 'Sửa sản phẩm #' . (int)$id : 'Thêm sản phẩm mới' ?></h1>
    <p><a href="products.php">&laquo; Quay lại danh sách sản phẩm</a></p>

    <?php if ($error !== ''): ?>
        <div style="background: #fdecea; color: #b71c1c; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="product-form.php<?= $isEdit ? '?id=' . (int)$id : '' ?>" enctype="multipart/form-data">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">
        <?php endif; ?>

        <div style="margin-bottom: 15px;">
            <label><strong>Tên sản phẩm:</strong></label><br>
            <input type="text" name="name" required 
                   value="<?= htmlspecialchars($_POST['name'] ?? $product['name'] ?? '') ?>" 
                   style="width: 100%; padding: 8px;">
        </div>

        <?php if ($hasCategoryCol): ?>
            <div style="margin-bottom: 15px;">
                <label><strong>Danh mục:</strong></label><br>
                <select name="category_id" style="width: 100%; padding: 8px;">
                    <option value="0">-- Chọn danh mục --</option>
                    <?php 
                    $selectedCat = (int)($_POST['category_id'] ?? $product['category_id'] ?? 0);
                    foreach ($categories as $cat): 
                    ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $selectedCat === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 15px;">
            <label><strong>Giá (VNĐ):</strong></label><br>
            <input type="number" name="price" step="0.01" min="0" required 
                   value="<?= htmlspecialchars((string)($_POST['price'] ?? $product['price'] ?? 0)) ?>" 
                   style="width: 100%; padding: 8px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label><strong>Số lượng tồn kho:</strong></label><br>
            <input type="number" name="stock" min="0" required 
                   value="<?= htmlspecialchars((string)($_POST['stock'] ?? $product[$stockCol] ?? 0)) ?>" 
                   style="width: 100%; padding: 8px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label><strong>Hình ảnh sản phẩm:</strong></label><br>
            <?php if (!empty($product['image'])): ?>
                <div style="margin: 8px 0;">
                    <img src="../<?= htmlspecialchars($product['image']) ?>" alt="Ảnh hiện tại" style="max-height: 120px; border: 1px solid #ddd; padding: 4px;">
                    <p style="margin: 4px 0; font-size: 13px; color: #555;">Đường dẫn hiện tại: <code><?= htmlspecialchars($product['image']) ?></code></p>
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*">
        </div>

        <?php if ($hasDescCol): ?>
            <div style="margin-bottom: 15px;">
                <label><strong>Mô tả:</strong></label><br>
                <textarea name="description" rows="4" style="width: 100%; padding: 8px;"><?= htmlspecialchars($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
            </div>
        <?php endif; ?>

        <button type="submit" style="padding: 10px 20px; cursor: pointer;">
            <?= $isEdit ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?>
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>