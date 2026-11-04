<?php
require __DIR__ . '/includes/init.php';

$pageTitle = 'Liên hệ';
require __DIR__ . '/includes/header.php';
?>
<h2>Liên hệ</h2>

<form action="contact.php" method="get" class="form">
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname" required>
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" required>
    </p>
    <p>
        <label for="subject">Tiêu đề</label><br>
        <input type="text" id="subject" name="subject" required>
    </p>
    <p>
        <label for="message">Nội dung</label><br>
        <textarea id="message" name="message" rows="5" cols="40" required></textarea>
    </p>
    <p>
        <button type="submit" class="btn">Gửi</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
