<?php
// Đường dẫn file: project/change-password.php
require_once __DIR__ . '/includes/init.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
} elseif (!isset($pdo) && function_exists('getDB')) {
    $pdo = getDB();
}

// Kiểm tra đăng nhập
$userId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Vui lòng điền đầy đủ tất cả các trường.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Mật khẩu mới phải có độ dài tối thiểu 6 ký tự.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu mới không khớp.';
    } else {
        // Kiểm tra tên cột mật khẩu trong bảng users
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $passCol = in_array('password', $userCols, true) ? 'password' : 'password_hash';

        // Lấy mật khẩu hiện tại của user từ DB
        $stmt = $pdo->prepare("SELECT {$passCol} FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $storedHash = $stmt->fetchColumn();

        $isCurrentValid = $storedHash !== false && (password_verify($currentPassword, (string)$storedHash) || $currentPassword === (string)$storedHash);

        if (!$isCurrentValid) {
            $error = 'Mật khẩu hiện tại không đúng!';
        } else {
            // Mã hóa mật khẩu mới và cập nhật vào DB
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET {$passCol} = :new_pass WHERE id = :id");
            $updateStmt->execute([
                ':new_pass' => $newHash,
                ':id'       => $userId
            ]);

            $success = 'Đổi mật khẩu thành công!';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 480px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 6px;">
    <h1 style="margin-top: 0; text-align: center;">Đổi mật khẩu</h1>

    <?php if ($error !== ''): ?>
        <div style="background: #fdecea; color: #b71c1c; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div style="background: #e8f5e9; color: #1b5e20; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="change-password.php">
        <div style="margin-bottom: 15px;">
            <label><strong>Mật khẩu hiện tại:</strong></label><br>
            <input type="password" name="current_password" required 
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label><strong>Mật khẩu mới (ít nhất 6 ký tự):</strong></label><br>
            <input type="password" name="new_password" required minlength="6" 
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label><strong>Nhập lại mật khẩu mới:</strong></label><br>
            <input type="password" name="confirm_password" required minlength="6" 
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" style="width: 100%; padding: 10px; cursor: pointer;">
            Lưu mật khẩu mới
        </button>
    </form>

    <p style="text-align: center; margin-top: 15px;">
        <a href="index.php">&laquo; Quay về trang chủ</a>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>