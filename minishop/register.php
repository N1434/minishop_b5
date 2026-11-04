<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$fullname = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu và cắt khoảng trắng thừa
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // 2. Kiểm tra từng trường; lỗi của trường nào ghi vào khóa của trường đó
    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên phải có từ 2 đến 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    }
    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $errors['phone'] = 'Số điện thoại gồm 10 chữ số, bắt đầu bằng số 0.';
    }
    if (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($password !== $password2) {
        $errors['password2'] = 'Hai mật khẩu không khớp nhau.';
    }
    if (empty($_POST['agree'])) {
        $errors['agree'] = 'Bạn cần đồng ý với điều khoản sử dụng.';
    }

    // 3. Email đã có người dùng chưa?
    if (!isset($errors['email'])) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email này đã được đăng ký.';
        }
    }

    // 4. Không có lỗi: lưu tài khoản rồi chuyển sang trang đăng nhập
    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (fullname, email, password_hash, phone, address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fullname, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $address]);

        set_flash('success', 'Đăng ký thành công. Mời bạn đăng nhập.');
        redirect('login.php');
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<h2>Đăng ký tài khoản</h2>

<form action="<?= url('register.php') ?>" method="post" class="form" id="register-form">
    <?= csrf_field() ?>
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>"
               required minlength="2" maxlength="100">
        <span class="error"><?= e($errors['fullname'] ?? '') ?></span>
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        <span class="error"><?= e($errors['email'] ?? '') ?></span>
    </p>
    <p>
        <label for="password">Mật khẩu</label><br>
        <input type="password" id="password" name="password" required minlength="6">
        <span class="error"><?= e($errors['password'] ?? '') ?></span>
    </p>
    <p>
        <label for="password2">Nhập lại mật khẩu</label><br>
        <input type="password" id="password2" name="password2" required minlength="6">
        <span class="error" id="password-error"><?= e($errors['password2'] ?? '') ?></span>
    </p>
    <p>
        <label for="phone">Số điện thoại</label><br>
        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>"
               required pattern="0[0-9]{9}" title="Số điện thoại gồm 10 chữ số, bắt đầu bằng số 0">
        <span class="error"><?= e($errors['phone'] ?? '') ?></span>
    </p>
    <p>
        <label for="address">Địa chỉ nhận hàng</label><br>
        <textarea id="address" name="address" rows="3"><?= e($address) ?></textarea>
    </p>
    <p>
        <input type="checkbox" id="agree" name="agree" value="1" required>
        <label for="agree">Tôi đồng ý với điều khoản sử dụng</label>
        <span class="error"><?= e($errors['agree'] ?? '') ?></span>
    </p>
    <p>
        <button type="submit" class="btn">Đăng ký</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
