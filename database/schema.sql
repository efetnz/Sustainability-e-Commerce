-- Drop database if exists
DROP DATABASE IF EXISTS sustainability_ecommerce;

-- Create database
CREATE DATABASE sustainability_ecommerce;

-- Use database
USE sustainability_ecommerce;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('market', 'consumer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE
);


INSERT INTO `users` (`id`, `email`, `password`, `user_type`, `created_at`, `is_verified`) VALUES
(5, 'ibrahim.dogan@ug.bilkent.edu.tr', '$2y$10$u9cjfI1mqFYe1oEuv.0DLeXU80FVT8mMqmtU7HQrgEsNLYa34ox.C', 'consumer', '2025-05-12 19:31:28', 1),
(6, 'ecommerce.project256@gmail.com', '$2y$10$LSPGvtxtN3zsBl7uVgCr0eTXQ0oMRDyYZEVFVdHbS2VjHU2Nw1QDm', 'market', '2025-05-12 19:51:29', 1),
(7, 'ibrahimcanbee@gmail.com', '$2y$10$Ckaw3lcUoxnh2k77fXH42ucjA9VlTXq7JWX7gvJtprThgjuipl4jK', 'market', '2025-05-18 18:54:48', 1),
(8, 'baran.durmaz@ug.bilkent.edu.tr', '$2y$10$vafxFQtI5cktrOSfP6zNJ.RG9bhhtpxjMllz1Up40mQeDAnAwgvW2', 'consumer', '2025-05-18 19:07:08', 1);
COMMIT;

-- Create market_profiles table
CREATE TABLE market_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    market_name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create consumer_profiles table
CREATE TABLE consumer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create verification_codes table
CREATE TABLE verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    market_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    stock INT NOT NULL,
    normal_price DECIMAL(10, 2) NOT NULL,
    discounted_price DECIMAL(10, 2) NOT NULL,
    expiration_date DATE NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES market_profiles(id) ON DELETE CASCADE
);

INSERT INTO `products` (`id`, `market_id`, `title`, `stock`, `normal_price`, `discounted_price`, `expiration_date`, `image_path`, `created_at`) VALUES
(8, 2, 'Yogurt 500g', 40, 35.49, 23.99, '2025-05-22', 'img_682a31ea8f2421.25852513.png', '2025-05-18 18:59:00'),
(9, 1, 'Organic Milk 1L', 30, 3.49, 2.29, '2025-05-25', 'img_682a3141640341.34849550.jpg', '2025-05-18 19:01:07'),
(10, 2, 'Free-Range Eggs (12-pack)', 20, 4.99, 3.49, '2025-05-23', 'img_682a30bf1edd50.18843564.jpg', '2025-05-18 19:01:07'),
(7, 1, 'Bread', 13, 10.00, 8.00, '2027-05-11', 'img_682a320fde16d2.83801076.png', '2025-05-13 19:40:05'),
(11, 1, 'Cherry Tomatoes 500g', 25, 3.29, 2.09, '2025-05-21', 'img_682a313b61a849.57389901.jpg', '2025-05-18 19:01:07'),
(12, 2, 'Fresh Spinach 250g', 60, 2.79, 1.49, '2025-05-19', 'img_682a312a45f4b3.85759225.jpg', '2025-05-18 19:01:07');
COMMIT;

-- Create cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES consumer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create purchases table for purchase history
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    purchase_price DECIMAL(10, 2) NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES consumer_profiles(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);


INSERT INTO consumer_profiles (id, user_id, fullname, city, district) VALUES
(6, 8, 'Efe Baran', 'İstanbul', 'Beşiktaş'),
(5, 5, 'İbrahim Can Doğan', 'Ankara', 'Çankaya');
COMMIT;

INSERT INTO market_profiles (id, user_id, market_name, city, district) VALUES
(1, 6, 'A101', 'Ankara', 'Çankaya'),
(2, 7, 'ŞOK', 'İstanbul', 'Beşiktaş');
COMMIT;

INSERT INTO verification_codes (id, user_id, code, created_at, expires_at) VALUES
(1, 1, '297493', '2025-05-06 17:04:07', '2025-05-07 16:28:56'),
(2, 2, '570654', '2025-05-06 19:31:39', '2025-05-07 16:31:39'),
(3, 3, '994337', '2025-05-06 19:40:17', '2025-05-07 16:49:04'),
(4, 4, '491223', '2025-05-12 10:56:02', '2025-05-13 07:56:02');
COMMIT;

-- Create index for product search
CREATE INDEX idx_product_title ON products(title);

-- Create index for expiration date searches
CREATE INDEX idx_expiration_date ON products(expiration_date);

-- Create index for location-based searches
CREATE INDEX idx_market_location ON market_profiles(city, district); 