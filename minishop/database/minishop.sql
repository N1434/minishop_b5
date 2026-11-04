-- MiniShop: cấu trúc bảng và dữ liệu mẫu
-- Chạy trong phpMyAdmin sau khi đã chọn cơ sở dữ liệu minishop

SET NAMES utf8mb4;

DROP TABLE IF EXISTS order_items, orders, products, categories, users;

CREATE TABLE categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    price       INT UNSIGNED NOT NULL,
    description TEXT,
    image       VARCHAR(255) NOT NULL DEFAULT 'images/sp01.jpg',
    stock       INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname      VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(15)  NOT NULL,
    address       VARCHAR(255) NOT NULL DEFAULT '',
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    fullname   VARCHAR(100) NOT NULL,
    phone      VARCHAR(15)  NOT NULL,
    address    VARCHAR(255) NOT NULL,
    note       VARCHAR(500) NOT NULL DEFAULT '',
    total      INT UNSIGNED NOT NULL,
    status     ENUM('pending', 'shipping', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    price        INT UNSIGNED NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (id, name) VALUES
    (1, 'Chuột và bàn phím'),
    (2, 'Âm thanh và hình ảnh'),
    (3, 'Lưu trữ và phụ kiện');

INSERT INTO products (category_id, name, price, description, image, stock) VALUES
    (1, 'Chuột không dây M1', 250000,
     'Chuột không dây kết nối 2.4GHz, thiết kế đối xứng, nút bấm êm, phù hợp cho học tập và làm việc văn phòng.',
     'images/sp01.jpg', 20),
    (1, 'Bàn phím cơ K2', 790000,
     'Bàn phím cơ 87 phím, switch đỏ gõ nhẹ, có đèn nền trắng.',
     'images/sp02.jpg', 10),
    (2, 'Tai nghe Bluetooth T3', 450000,
     'Tai nghe chụp tai Bluetooth 5.3, pin 30 giờ, có micro đàm thoại.',
     'images/sp03.jpg', 15),
    (3, 'USB 64GB U4', 150000,
     'USB 3.2 dung lượng 64GB, vỏ kim loại, có móc treo.',
     'images/sp04.jpg', 50),
    (2, 'Webcam Full HD W5', 520000,
     'Webcam 1080p, tự lấy nét, tích hợp micro kép, kẹp được trên màn hình.',
     'images/sp05.jpg', 8),
    (3, 'Đế tản nhiệt laptop D6', 320000,
     'Đế tản nhiệt hai quạt, điều chỉnh được độ nghiêng, dùng cho laptop đến 15.6 inch.',
     'images/sp06.jpg', 12);
