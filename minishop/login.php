<?php
// Đường dẫn file: project/login.php
require_once __DIR__ . '/includes/init.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
} elseif (!isset($pdo) && function_exists('getDB')) {
    $pdo = getDB();
}

// Nếu đã đăng nhập rồi thì chuyển về trang chủ
if (!empty($_SESSION['user']) || !empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Khởi tạo biến đếm số lần sai và thời gian khóa trong Session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_until'])) {
    $_SESSION['lockout_until'] = 0;
}

$error = '';
$isLocked = false;

// 1. KIỂM TRA TRẠNG THÁI KHÓA FORM 5 PHÚT
if ($_SESSION['lockout_until'] > time()) {
    $isLocked = true;
    $remaining = $_SESSION['lockout_until'] - time();
    $mins = floor($remaining / 60);
    $secs = $remaining % 60;
    $error = "Bạn đã đăng nhập sai 5 lần. Form đang bị khóa, vui lòng thử lại sau {$mins} phút {$secs} giây.";
} elseif ($_SESSION['lockout_until'] > 0 && $_SESSION['lockout_until'] <= time()) {
    // Đã hết 5 phút khóa -> Reset lại bộ đếm
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_until'] = 0;
}

// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM ĐĂNG NHẬP (Chỉ xử lý khi không bị khóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $loginInput = trim($_POST['email'] ?? $_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($loginInput === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập.';
    } else {
        // Kiểm tra cột mật khẩu trong bảng users (password hoặc password_hash)
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $passCol = in_array('password', $userCols, true) ? 'password' : 'password_hash';
        $hasUsername = in_array('username', $userCols, true);

        if ($hasUsername) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :login OR username = :login LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :login LIMIT 1");
        }
        $stmt->execute([':login' => $loginInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra mật khẩu (hỗ trợ cả password_verify và plain text nếu dữ liệu mẫu chưa hash)
        $storedHash = $user[$passCol] ?? '';
        $isValidPassword = $user && (password_verify($password, $storedHash) || $password === $storedHash);

        if ($isValidPassword) {
            // Đăng nhập thành công -> Reset bộ đếm sai
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_until'] = 0;

            // Lưu thông tin vào Session (tương thích mọi kiểu gọi trong project)
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'] ?? $user['fullname'] ?? $user['username'] ?? $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'customer';

            header('Location: index.php');
            exit;
        } else {
            // Đăng nhập sai -> Tăng biến đếm trong Session
            $_SESSION['login_attempts']++;

            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_until'] = time() + 300; // Khóa 5 phút = 300 giây
                $isLocked = true;
                $error = 'Bạn đã nhập sai 5 lần liên tiếp! Form đăng nhập đã bị khóa trong 5 phút.';
            } else {
                $left = 5 - $_SESSION['login_attempts'];
                $error = "Thông tin đăng nhập không đúng! Bạn đã sai {$_SESSION['login_attempts']}/5 lần (còn {$left} lần thử).";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 450px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 6px;">
    <h1 style="margin-top: 0; text-align: center;">Đăng nhập</h1>

    <?php if ($error !== ''): ?>
        <div style="background: #fdecea; color: #b71c1c; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <fieldset <?= $isLocked ? 'disabled' : '' ?> style="border: none; padding: 0; margin: 0;">
            <div style="margin-bottom: 15px;">
                <label><strong>Email:</strong></label><br>
                <input type="text" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                       placeholder="Nhập email của bạn..." 
                       style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label><strong>Mật khẩu:</strong></label><br>
                <input type="password" name="password" required 
                       placeholder="Nhập mật khẩu..." 
                       style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>

            <button type="submit" style="width: 100%; padding: 10px; cursor: <?= $isLocked ? 'not-allowed' : 'pointer' ?>;">
                <?= $isLocked ? 'Đang bị khóa (5 phút)' : 'Đăng nhập' ?>
            </button>
        </fieldset>
    </form>

    <p style="text-align: center; margin-top: 15px;">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>