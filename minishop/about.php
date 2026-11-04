<?php
require __DIR__ . '/includes/init.php';

$pageTitle = 'Giới thiệu';
require __DIR__ . '/includes/header.php';
?>
<h2>Giới thiệu MiniShop</h2>
<img src="<?= url('images/sp01.jpg') ?>" alt="Chuột không dây M1" width="300">

<h3>Chúng tôi là ai</h3>
<p>
    MiniShop là cửa hàng trực tuyến chuyên <strong>phụ kiện công nghệ</strong>
    dành cho sinh viên. Chúng tôi chọn sản phẩm theo tiêu chí
    <em>bền, dễ dùng và đúng giá</em>.
</p>
<p>
    Địa chỉ: 123 Đường Số 1, TP. Biên Hòa, Đồng Nai.<br>
    Giờ mở cửa: 8:00 - 21:00, tất cả các ngày trong tuần.
</p>

<h3>Vì sao chọn MiniShop</h3>
<ul>
    <li>Sản phẩm chính hãng, bảo hành 12 tháng</li>
    <li>Giao hàng toàn quốc</li>
    <li>Đổi trả trong 7 ngày</li>
</ul>

<h3>Quy trình mua hàng</h3>
<ol>
    <li>Chọn sản phẩm và thêm vào giỏ hàng</li>
    <li>Đăng nhập và xác nhận đơn hàng</li>
    <li>Nhận hàng và thanh toán</li>
</ol>

<hr>

<h3>Liên hệ</h3>
<p>
    Email: <a href="mailto:hotro@minishop.example">hotro@minishop.example</a><br>
    Điện thoại: <a href="tel:0900000000">0900 000 000</a><br>
    Tài liệu tham khảo HTML:
    <a href="https://developer.mozilla.org/vi/docs/Web/HTML"
       target="_blank">MDN Web Docs</a>
</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
