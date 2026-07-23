-- GlowSync database schema + sample data
CREATE DATABASE IF NOT EXISTS glowsync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE glowsync;

-- ---------------------------------------------------------------
-- users (admin/staff who log in)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100) NOT NULL,
    email    VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role     ENUM('Admin','Staff') NOT NULL DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- NOTE: no row is inserted here on purpose.
-- Run setup_admin.php after import to create your admin login with a bcrypt hash.

-- ---------------------------------------------------------------
-- customers
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    email       VARCHAR(150),
    phone       VARCHAR(30),
    address     VARCHAR(255),
    membership  ENUM('Bronze','Silver','Gold') DEFAULT 'Bronze',
    notes       TEXT,
    joined_date DATE DEFAULT (CURRENT_DATE)
);

INSERT INTO customers (name, email, phone, address, membership, notes, joined_date) VALUES
('Lynn Alvarado', 'lynn.alvarado@example.com', '0917-555-0142', '12 Rizal St, Quezon City', 'Gold', 'Prefers vitamin C serum line.', '2024-02-10'),
('Erich Domingo', 'erich.domingo@example.com', '0918-555-0987', '45 Mabini Ave, Makati', 'Silver', '', '2024-05-22'),
('Mary Santiago', 'mary.santiago@example.com', '0919-555-0311', '9 Bonifacio Rd, Pasig', 'Bronze', 'Sensitive skin, avoid fragrance.', '2025-01-08');

-- ---------------------------------------------------------------
-- products
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(150) NOT NULL,
    category             VARCHAR(100),
    price                DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock                INT NOT NULL DEFAULT 0,
    low_stock_threshold  INT NOT NULL DEFAULT 10,
    image                VARCHAR(255)
);

INSERT INTO products (name, category, price, stock, low_stock_threshold, image) VALUES
('GlowSync Vitamin C Serum', 'Serum', 899.00, 42, 10, NULL),
('Hydra Boost Moisturizer', 'Moisturizer', 649.00, 30, 10, NULL),
('Gentle Foam Cleanser', 'Cleanser', 399.00, 60, 15, NULL),
('SPF 50 Sunscreen', 'Sun Care', 549.00, 25, 10, NULL),
('Matte Liquid Lipstick', 'Lipstick', 349.00, 8, 10, NULL);

-- ---------------------------------------------------------------
-- sales
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    price       DECIMAL(10,2) NOT NULL,
    status      ENUM('Pending','Processing','Completed','Cancelled') DEFAULT 'Pending',
    order_date  DATE NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE CASCADE
);

INSERT INTO sales (customer_id, product_id, quantity, price, status, order_date) VALUES
(1, 1, 2, 899.00, 'Completed', CURDATE() - INTERVAL 6 DAY),
(2, 2, 1, 649.00, 'Pending',   CURDATE() - INTERVAL 3 DAY),
(3, 3, 3, 399.00, 'Processing',CURDATE() - INTERVAL 1 DAY),
(1, 4, 1, 549.00, 'Completed', CURDATE());

-- ---------------------------------------------------------------
-- tickets
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tickets (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT NOT NULL,
    issue        VARCHAR(255) NOT NULL,
    description  TEXT,
    priority     ENUM('Low','Medium','High') DEFAULT 'Medium',
    status       ENUM('Open','In Progress','Closed') DEFAULT 'Open',
    assigned_to  VARCHAR(100),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

INSERT INTO tickets (customer_id, issue, description, priority, status, assigned_to) VALUES
(1, 'Wrong item delivered', 'Received the moisturizer instead of the serum.', 'High', 'Open', 'Jasmine'),
(2, 'Delayed shipment', 'Order has not moved in 4 days.', 'Medium', 'In Progress', 'Carlo');

-- ---------------------------------------------------------------
-- ticket_messages
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT NOT NULL,
    sender_type ENUM('customer','agent') NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message) VALUES
(1, 'customer', 'Lynn Alvarado', 'Hi, I received the wrong product in my order.'),
(1, 'agent', 'Jasmine', 'So sorry about that! We are shipping the correct item today.'),
(2, 'customer', 'Erich Domingo', 'Any update on my shipment?');

-- ---------------------------------------------------------------
-- inventory_log (Stock In / Stock Out history)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    type        ENUM('IN','OUT') NOT NULL,
    quantity    INT NOT NULL,
    reason      VARCHAR(255),
    created_by  VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO inventory_log (product_id, type, quantity, reason, created_by, created_at) VALUES
(1, 'IN', 50, 'Initial stock delivery', 'Admin', NOW() - INTERVAL 20 DAY),
(1, 'OUT', 8, 'Sold in-store', 'Admin', NOW() - INTERVAL 10 DAY),
(5, 'IN', 20, 'Initial stock delivery', 'Admin', NOW() - INTERVAL 15 DAY),
(5, 'OUT', 12, 'Sold in-store', 'Admin', NOW() - INTERVAL 2 DAY);

-- ---------------------------------------------------------------
-- feedback (Customer Feedback / ratings)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    name        VARCHAR(150) NOT NULL,
    rating      TINYINT NOT NULL DEFAULT 5,
    comment     TEXT,
    created_at  DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

INSERT INTO feedback (customer_id, name, rating, comment, created_at) VALUES
(1, 'Lynn Alvarado', 5, 'The serum cleared up my skin in two weeks. Staff was super helpful too!', CURDATE() - INTERVAL 5 DAY),
(2, 'Erich Domingo', 4, 'Good products but delivery took a bit longer than expected.', CURDATE() - INTERVAL 2 DAY),
(3, 'Mary Santiago', 5, 'Loved that they double-checked for fragrance-free options for my sensitive skin.', CURDATE());

-- ---------------------------------------------------------------
-- customers_staff_view: what Staff accounts see on the Customers
-- pages. Email and address are left out entirely — Staff never
-- receives those columns from the database, Admin still queries
-- the real `customers` table and sees everything.
-- ---------------------------------------------------------------
CREATE OR REPLACE VIEW customers_staff_view AS
SELECT id, name, phone, membership, notes, joined_date
FROM customers;
