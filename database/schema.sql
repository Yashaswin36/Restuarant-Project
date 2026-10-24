-- TableFare — Restaurant Table Reservation & Ordering
-- Import via phpMyAdmin or: mysql -u root -P 3306 < schema.sql
-- Creates database, schema, and demo seed data.

CREATE DATABASE IF NOT EXISTS restaurant_table_reservation_ordering
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE restaurant_table_reservation_ordering;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS session_members;
DROP TABLE IF EXISTS table_sessions;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menu_categories;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Schema
-- ============================================================

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin','staff','kitchen') NOT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- No password: identified/recognized by email across visits (see core/Otp.php).
CREATE TABLE customers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(150) NOT NULL,
    purpose     ENUM('booking','table_session') NOT NULL,
    code        VARCHAR(255) NOT NULL,
    attempts    INT NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_purpose (email, purpose)
) ENGINE=InnoDB;

CREATE TABLE restaurant_tables (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    table_number  VARCHAR(10) NOT NULL UNIQUE,
    capacity      INT NOT NULL,
    zone          VARCHAR(50) NOT NULL,
    table_code    VARCHAR(40) NOT NULL UNIQUE,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE menu_categories (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(60) NOT NULL,
    display_order  INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT NOT NULL,
    name          VARCHAR(100) NOT NULL,
    description   VARCHAR(255) NULL,
    price         DECIMAL(8,2) NOT NULL,
    is_veg        TINYINT(1) NOT NULL DEFAULT 1,
    is_available  TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    customer_id        INT NOT NULL,
    table_id           INT NULL,
    reservation_date    DATE NOT NULL,
    reservation_time    TIME NOT NULL,
    party_size         INT NOT NULL,
    status             ENUM('pending','confirmed','seated','completed','cancelled') NOT NULL DEFAULT 'pending',
    special_requests   VARCHAR(255) NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Only one row per table may have status='active' at a time — enforced in
-- application code (core models), not a DB constraint (MySQL has no partial
-- unique index), see app/models/TableSession.php.
CREATE TABLE table_sessions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    table_id          INT NOT NULL,
    host_customer_id  INT NULL,
    session_code      VARCHAR(10) NOT NULL,
    status            ENUM('active','closed') NOT NULL DEFAULT 'active',
    started_at        DATETIME NOT NULL,
    closed_at         DATETIME NULL,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (host_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_table_status (table_id, status)
) ENGINE=InnoDB;

CREATE TABLE session_members (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    table_session_id  INT NOT NULL,
    customer_id       INT NULL,
    display_name      VARCHAR(100) NOT NULL,
    is_host           TINYINT(1) NOT NULL DEFAULT 0,
    joined_at         DATETIME NOT NULL,
    FOREIGN KEY (table_session_id) REFERENCES table_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE orders (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    table_session_id   INT NOT NULL,
    session_member_id  INT NOT NULL,
    status             ENUM('placed','preparing','ready','served') NOT NULL DEFAULT 'placed',
    placed_at          DATETIME NOT NULL,
    FOREIGN KEY (table_session_id) REFERENCES table_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (session_member_id) REFERENCES session_members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    menu_item_id    INT NOT NULL,
    category_id     INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    price_at_order  DECIMAL(8,2) NOT NULL,
    notes           VARCHAR(255) NULL,
    status          ENUM('placed','preparing','ready') NOT NULL DEFAULT 'placed',
    updated_at      DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE RESTRICT,
    INDEX idx_category_status (category_id, status)
) ENGINE=InnoDB;

-- Combined bill: one row, session_member_id NULL, covers the whole table session.
-- Split bill: one row per member, session_member_id set, covers just their orders.
CREATE TABLE bills (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    table_session_id   INT NOT NULL,
    bill_type          ENUM('combined','split') NOT NULL,
    session_member_id  INT NULL,
    subtotal           DECIMAL(8,2) NOT NULL,
    tax_amount         DECIMAL(8,2) NOT NULL,
    total_amount       DECIMAL(8,2) NOT NULL,
    status             ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    generated_by       INT NOT NULL,
    generated_at       DATETIME NOT NULL,
    paid_at            DATETIME NULL,
    FOREIGN KEY (table_session_id) REFERENCES table_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (session_member_id) REFERENCES session_members(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- Demo passwords (documented in README.md):
--   Super Admin : Admin@123
--   Staff       : Staff@123
--   Kitchen     : Kitchen@123
-- Customers never have passwords — see README for the OTP demo flow.
-- ============================================================

INSERT INTO users (name, email, password, role, status) VALUES
('System Administrator', 'admin@tablefare.test',   '$2y$12$RwCHto44oIe3aZEG2LSsgO42sYOFMcDaqmYKMhRJiv.uPHHzp8nOK', 'super_admin', 'active'),
('Ramesh Kulkarni',       'staff1@tablefare.test',  '$2y$12$u.J766LDd3XShAMWOSj/iuwanBkXD5cHVNWmqAG5rbw0Fc0cASa.2', 'staff',       'active'),
('Ananya Shetty',         'staff2@tablefare.test',  '$2y$12$u.J766LDd3XShAMWOSj/iuwanBkXD5cHVNWmqAG5rbw0Fc0cASa.2', 'staff',       'active'),
('Kitchen Station 1',     'kitchen1@tablefare.test','$2y$12$PtrCSG2ZG8N4LUl03v9/xONC.l.5VxMxC4wozVQuCq4oxHa2xN8XK', 'kitchen',     'active'),
('Kitchen Station 2',     'kitchen2@tablefare.test','$2y$12$PtrCSG2ZG8N4LUl03v9/xONC.l.5VxMxC4wozVQuCq4oxHa2xN8XK', 'kitchen',     'active');

INSERT INTO customers (name, email) VALUES
('Aarav Sharma', 'aarav.sharma@example.com'),
('Diya Patel', 'diya.patel@example.com'),
('Kabir Singh', 'kabir.singh@example.com'),
('Ananya Iyer', 'ananya.iyer@example.com'),
('Rohan Gupta', 'rohan.gupta@example.com'),
('Ishita Reddy', 'ishita.reddy@example.com'),
('Vivaan Nair', 'vivaan.nair@example.com'),
('Sara Khan', 'sara.khan@example.com'),
('Arjun Mehta', 'arjun.mehta@example.com'),
('Priya Verma', 'priya.verma@example.com'),
('Aditya Joshi', 'aditya.joshi@example.com'),
('Neha Kulkarni', 'neha.kulkarni@example.com');

-- table_code values are opaque tokens embedded in each table's QR code.
INSERT INTO restaurant_tables (table_number, capacity, zone, table_code, status) VALUES
('T1',  2, 'Indoor', 'qr-t1-8f3ka9d2e7',  'active'),
('T2',  2, 'Indoor', 'qr-t2-1b6cm4p9x0',  'active'),
('T3',  4, 'Indoor', 'qr-t3-5r2vn8q1w3',  'active'),
('T4',  4, 'Indoor', 'qr-t4-9j0zl3h6y5',  'active'),
('T5',  4, 'Patio',  'qr-t5-2k7bf1t4c8',  'active'),
('T6',  6, 'Patio',  'qr-t6-6m4gd9s2v1',  'active'),
('T7',  6, 'Balcony','qr-t7-3n8xr5a7e0',  'active'),
('T8',  8, 'Balcony','qr-t8-7p1wq6u3i9',  'active'),
('T9',  2, 'Indoor', 'qr-t9-0h5yl8o2z4',  'active'),
('T10', 4, 'Patio',  'qr-t10-4d9km1f6b7', 'inactive');

INSERT INTO menu_categories (name, display_order) VALUES
('Starters', 1),
('Main Course', 2),
('Beverages', 3),
('Desserts', 4),
('Breads', 5);

INSERT INTO menu_items (category_id, name, description, price, is_veg, is_available) VALUES
(1, 'Paneer Tikka', 'Chargrilled cottage cheese, smoky masala marinade', 220.00, 1, 1),
(1, 'Veg Spring Rolls', 'Crisp rolls, stir-fried vegetables', 180.00, 1, 1),
(1, 'Chicken 65', 'Deep-fried spiced chicken, curry leaf tempering', 260.00, 0, 1),
(1, 'Fish Fingers', 'Batter-fried fish strips, tartar sauce', 280.00, 0, 1),
(1, 'Soup of the Day', "Chef's daily seasonal soup", 150.00, 1, 1),
(2, 'Paneer Butter Masala', 'Cottage cheese in a rich tomato-butter gravy', 280.00, 1, 1),
(2, 'Dal Makhani', 'Slow-cooked black lentils, cream, butter', 220.00, 1, 1),
(2, 'Butter Chicken', 'Tandoori chicken in a velvety tomato gravy', 320.00, 0, 1),
(2, 'Mutton Rogan Josh', 'Slow-braised mutton, Kashmiri spices', 380.00, 0, 1),
(2, 'Veg Biryani', 'Layered basmati rice, mixed vegetables, saffron', 260.00, 1, 1),
(2, 'Chicken Biryani', 'Layered basmati rice, marinated chicken, saffron', 300.00, 0, 1),
(3, 'Masala Chai', 'Spiced Indian tea', 60.00, 1, 1),
(3, 'Fresh Lime Soda', 'Sweet, salted, or plain', 80.00, 1, 1),
(3, 'Cold Coffee', 'Blended coffee, ice cream', 120.00, 1, 1),
(3, 'Mango Lassi', 'Yoghurt, mango pulp', 110.00, 1, 1),
(3, 'Mineral Water', '1L bottle', 40.00, 1, 1),
(4, 'Gulab Jamun', 'Milk-solid dumplings, cardamom syrup', 120.00, 1, 1),
(4, 'Rasmalai', 'Cottage cheese discs, saffron milk', 140.00, 1, 1),
(4, 'Chocolate Brownie', 'Warm brownie, vanilla ice cream', 160.00, 1, 1),
(4, 'Ice Cream', 'Two scoops, choice of flavour', 100.00, 1, 0),
(5, 'Butter Naan', 'Leavened bread, butter glaze', 60.00, 1, 1),
(5, 'Tandoori Roti', 'Whole-wheat bread, clay-oven baked', 40.00, 1, 1),
(5, 'Garlic Naan', 'Leavened bread, garlic and coriander', 70.00, 1, 1),
(5, 'Laccha Paratha', 'Multi-layered flaky flatbread', 65.00, 1, 1);

-- ---- Reservations across statuses/dates ----
INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, party_size, status, special_requests) VALUES
(1, 3, '2026-08-14', '19:30:00', 4, 'completed', NULL),
(2, 5, '2026-08-15', '20:00:00', 2, 'completed', 'Window seat please'),
(3, NULL, '2026-08-17', '13:00:00', 2, 'pending', NULL),
(4, NULL, '2026-08-17', '19:00:00', 6, 'confirmed', 'Birthday celebration'),
(5, 1, '2026-08-17', '12:30:00', 2, 'seated', NULL),
(6, NULL, '2026-08-18', '20:30:00', 4, 'confirmed', NULL),
(7, NULL, '2026-08-18', '13:30:00', 2, 'pending', 'Vegetarian only'),
(8, NULL, '2026-08-16', '19:00:00', 4, 'cancelled', NULL),
(9, NULL, '2026-08-19', '20:00:00', 8, 'confirmed', 'Large group, near AC'),
(10, NULL, '2026-08-19', '12:00:00', 2, 'pending', NULL),
(11, NULL, '2026-08-20', '19:30:00', 4, 'confirmed', NULL),
(12, NULL, '2026-08-16', '18:30:00', 2, 'completed', NULL),
(1, NULL, '2026-08-21', '20:00:00', 4, 'pending', NULL),
(3, NULL, '2026-08-15', '19:00:00', 2, 'cancelled', 'Change of plans'),
(5, 3, '2026-08-14', '19:30:00', 4, 'completed', NULL),
(6, 5, '2026-08-15', '20:00:00', 2, 'completed', NULL);

-- ---- Table sessions: two closed (billed) + one active (live demo) ----
INSERT INTO table_sessions (table_id, host_customer_id, session_code, status, started_at, closed_at) VALUES
(3, 1, 'A7K2Q', 'closed', '2026-08-14 19:35:00', '2026-08-14 20:50:00'),
(5, 2, 'B3N9X', 'closed', '2026-08-15 20:05:00', '2026-08-15 21:20:00'),
(1, 5, 'C5M1R', 'active', '2026-08-17 12:35:00', NULL);

INSERT INTO session_members (table_session_id, customer_id, display_name, is_host, joined_at) VALUES
(1, 1, 'Aarav Sharma', 1, '2026-08-14 19:35:00'),
(1, 5, 'Rohan Gupta', 0, '2026-08-14 19:37:00'),
(1, 6, 'Ishita Reddy', 0, '2026-08-14 19:38:00'),
(2, 2, 'Diya Patel', 1, '2026-08-15 20:05:00'),
(2, 6, 'Ishita Reddy', 0, '2026-08-15 20:07:00'),
(3, 5, 'Rohan Gupta', 1, '2026-08-17 12:35:00'),
(3, 6, 'Ishita Reddy (friend)', 0, '2026-08-17 12:41:00');

INSERT INTO orders (table_session_id, session_member_id, status, placed_at) VALUES
(1, 1, 'served', '2026-08-14 19:40:00'),
(1, 2, 'served', '2026-08-14 19:42:00'),
(1, 3, 'served', '2026-08-14 19:45:00'),
(2, 4, 'served', '2026-08-15 20:10:00'),
(2, 5, 'served', '2026-08-15 20:12:00'),
(3, 6, 'preparing', '2026-08-17 12:40:00'),
(3, 7, 'placed', '2026-08-17 12:44:00');

INSERT INTO order_items (order_id, menu_item_id, category_id, quantity, price_at_order, notes, status, updated_at) VALUES
-- order 1 (Aarav, session 1) — Paneer Tikka x2, Butter Chicken, Masala Chai x2
(1, 1, 1, 2, 220.00, NULL, 'ready', '2026-08-14 19:55:00'),
(1, 8, 2, 1, 320.00, NULL, 'ready', '2026-08-14 20:00:00'),
(1, 12, 3, 2, 60.00, NULL, 'ready', '2026-08-14 19:45:00'),
-- order 2 (Rohan, session 1) — Chicken 65, Veg Biryani, Fresh Lime Soda
(2, 3, 1, 1, 260.00, NULL, 'ready', '2026-08-14 19:57:00'),
(2, 10, 2, 1, 260.00, 'Less spicy', 'ready', '2026-08-14 20:02:00'),
(2, 13, 3, 1, 80.00, NULL, 'ready', '2026-08-14 19:47:00'),
-- order 3 (Ishita, session 1) — Gulab Jamun, Butter Naan x2
(3, 17, 4, 1, 120.00, NULL, 'ready', '2026-08-14 20:15:00'),
(3, 21, 5, 2, 60.00, NULL, 'ready', '2026-08-14 19:52:00'),
-- order 4 (Diya, session 2) — Soup of the Day, Dal Makhani, Mango Lassi
(4, 5, 1, 1, 150.00, NULL, 'ready', '2026-08-15 20:20:00'),
(4, 7, 2, 1, 220.00, NULL, 'ready', '2026-08-15 20:25:00'),
(4, 15, 3, 1, 110.00, NULL, 'ready', '2026-08-15 20:14:00'),
-- order 5 (Ishita, session 2) — Fish Fingers, Mutton Rogan Josh, Cold Coffee, Rasmalai
(5, 4, 1, 1, 280.00, NULL, 'ready', '2026-08-15 20:22:00'),
(5, 9, 2, 1, 380.00, NULL, 'ready', '2026-08-15 20:30:00'),
(5, 14, 3, 1, 120.00, NULL, 'ready', '2026-08-15 20:16:00'),
(5, 18, 4, 1, 140.00, NULL, 'ready', '2026-08-15 20:35:00'),
-- order 6 (Rohan, session 3, ACTIVE) — Paneer Tikka preparing, Masala Chai ready
(6, 1, 1, 1, 220.00, 'Extra spicy', 'preparing', '2026-08-17 12:41:00'),
(6, 12, 3, 1, 60.00, NULL, 'ready', '2026-08-17 12:43:00'),
-- order 7 (Ishita's friend, session 3, ACTIVE) — Chicken Biryani, not started
(7, 11, 2, 1, 300.00, NULL, 'placed', '2026-08-17 12:44:00');

-- ---- Bills: session 1 combined (paid), session 2 split (paid) ----
INSERT INTO bills (table_session_id, bill_type, session_member_id, subtotal, tax_amount, total_amount, status, generated_by, generated_at, paid_at) VALUES
(1, 'combined', NULL, 1720.00, 86.00, 1806.00, 'paid', 2, '2026-08-14 20:48:00', '2026-08-14 20:50:00'),
(2, 'split', 4, 480.00, 24.00, 504.00, 'paid', 2, '2026-08-15 21:15:00', '2026-08-15 21:18:00'),
(2, 'split', 5, 920.00, 46.00, 966.00, 'paid', 2, '2026-08-15 21:15:00', '2026-08-15 21:20:00');
