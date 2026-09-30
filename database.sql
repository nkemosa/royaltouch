CREATE DATABASE IF NOT EXISTS shopping_system;
USE shopping_system;

CREATE TABLE IF NOT EXISTS admins (
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 image VARCHAR(255) DEFAULT NULL,
 description TEXT,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_reference VARCHAR(50) NOT NULL UNIQUE,
 customer_name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 address TEXT NOT NULL,
 payment_method ENUM('cod','online') NOT NULL,
 payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
 order_status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
 total DECIMAL(10,2) NOT NULL,
 receipt_token VARCHAR(64) NOT NULL UNIQUE,
 paystack_reference VARCHAR(100) DEFAULT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 product_id INT NOT NULL,
 product_name VARCHAR(100) NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 quantity INT NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Admin login: admin / admin123
-- This hash is specifically generated for the password "admin123".
INSERT INTO admins (username,password)
VALUES ('admin', '$2y$12$/3wjiIVHgu7OOxj1rTaGSuLIisZTbjcJnQMSCQRtNS.bWr5l39pfy')
ON DUPLICATE KEY UPDATE password=VALUES(password);

INSERT INTO products (name,price,description) VALUES
('Nike Shoe',25000,'Comfortable everyday shoe'),
('Classic T-Shirt',10000,'Simple cotton T-shirt'),
('Laptop',350000,'Laptop for work and study');
