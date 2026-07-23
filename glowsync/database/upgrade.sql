-- ============================================================
-- GlowSync upgrade script
-- Run this ONCE against an existing glowsync database to add
-- Inventory Management, User Management, Customer Feedback,
-- and Sales Invoice support.
--
-- If you are installing GlowSync fresh, just import
-- database/glowsync.sql instead — it already includes everything
-- in this file.
-- ============================================================
USE glowsync;

-- ---------------------------------------------------------------
-- products: low stock threshold used by Inventory alerts
-- ---------------------------------------------------------------
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS low_stock_threshold INT NOT NULL DEFAULT 10 AFTER stock;

-- ---------------------------------------------------------------
-- users: make sure role only ever holds Admin / Staff
-- ---------------------------------------------------------------
UPDATE users SET role = 'Admin' WHERE role NOT IN ('Admin','Staff') OR role IS NULL;

-- ---------------------------------------------------------------
-- inventory_log: every Stock In / Stock Out movement
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

-- ---------------------------------------------------------------
-- feedback: customer ratings / comments
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

-- ---------------------------------------------------------------
-- customers_staff_view: what Staff accounts see on the Customers
-- pages. Email and address are left out entirely — Staff never
-- receives those columns from the database, Admin still queries
-- the real `customers` table and sees everything.
-- ---------------------------------------------------------------
CREATE OR REPLACE VIEW customers_staff_view AS
SELECT id, name, phone, membership, notes, joined_date
FROM customers;
