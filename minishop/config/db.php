<?php
// Kết nối cơ sở dữ liệu bằng PDO. Sau tệp này, biến $pdo dùng được ở mọi trang.


define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'minishop');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    
    if (APP_DEBUG) {
        exit('Lỗi kết nối CSDL: ' . $e->getMessage());
    } else {
        exit('Hệ thống đang bận, vui lòng thử lại sau.');
    }
}