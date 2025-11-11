-- ============================================
-- Migration: Orders Module
-- ============================================

USE biziverse_erp;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `customer_id` int(11) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_po_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `sales_credit` varchar(100) DEFAULT 'None',
  `billing_address` text,
  `shipping_address` text,
  `same_as_billing` tinyint(1) DEFAULT 1,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `executive_id` int(11) UNSIGNED DEFAULT NULL,
  `executive_name` varchar(255) DEFAULT NULL,
  `responsible_id` int(11) UNSIGNED DEFAULT NULL,
  `responsible_name` varchar(255) DEFAULT NULL,
  `order_type` enum('sales','service') DEFAULT 'sales',
  `status` enum('pending','confirmed','processing','delivered','cancelled') DEFAULT 'pending',
  `commitment_status` enum('overdue','today','tomorrow','future') DEFAULT 'future',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `extra_charges` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `terms_conditions` text,
  `bank_details` text,
  `update_by_email` tinyint(1) DEFAULT 0,
  `update_by_whatsapp` tinyint(1) DEFAULT 0,
  `print_after_saving` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_order_date` (`order_date`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_commitment_status` (`commitment_status`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`executive_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`responsible_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_no` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `hsn_sac` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `quantity_pending` decimal(10,2) DEFAULT '0.00',
  `quantity_done` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) DEFAULT 'no.s',
  `rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `taxable_amount` decimal(15,2) DEFAULT '0.00',
  `cgst_percent` decimal(5,2) DEFAULT '0.00',
  `cgst_amount` decimal(15,2) DEFAULT '0.00',
  `sgst_percent` decimal(5,2) DEFAULT '0.00',
  `sgst_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order terms table
CREATE TABLE IF NOT EXISTS `order_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `term_condition` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deliveries table
CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_no` varchar(50) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `delivery_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `sales_executive_id` int(11) UNSIGNED DEFAULT NULL,
  `responsible_executive_id` int(11) UNSIGNED DEFAULT NULL,
  `billing_address` text,
  `shipping_address` text,
  `same_as_billing` tinyint(1) DEFAULT 1,
  `delivery_details` text,
  `recovery_amount` decimal(15,2) DEFAULT '0.00',
  `add_recovery` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `invoice_file` varchar(255) DEFAULT NULL,
  `update_by_email` tinyint(1) DEFAULT 0,
  `update_by_whatsapp` tinyint(1) DEFAULT 0,
  `status` enum('pending','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_no` (`delivery_no`),
  KEY `idx_order` (`order_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_delivery_date` (`delivery_date`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delivery items table
CREATE TABLE IF NOT EXISTS `delivery_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) DEFAULT 'no.s',
  `rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_delivery` (`delivery_id`),
  FOREIGN KEY (`delivery_id`) REFERENCES `deliveries`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample orders
INSERT INTO orders (order_no, customer_name, contact_person, order_date, due_date, total_amount, status, commitment_status, created_by, created_at) VALUES
('3', 'Balaji Heights', 'Mr. Sagar Gawande', '2024-03-29', '2024-03-29', 3097.50, 'delivered', 'overdue', 1, '2024-03-29'),
('4', 'Lubrizol India Pvt Ltd', 'Ram Tupe', '2024-03-31', '2024-03-31', 9086.00, 'delivered', 'overdue', 1, '2024-03-31'),
('5', 'AKSHAY EYE CLINIC', 'Mr. Deshmukh Sir', '2024-04-01', '2024-04-01', 33.04, 'delivered', 'today', 1, '2024-04-01'),
('6', 'Aircare System Pvt Ltd', 'Mr. Purchase Team', '2024-04-01', '2024-04-01', 6844.00, 'delivered', 'today', 1, '2024-04-01'),
('7', 'ACC LTD', 'Mr. Ram Prakash Singh', '2024-04-01', '2024-04-01', 708000.00, 'delivered', 'today', 1, '2024-04-01');

-- Insert sample order items
INSERT INTO order_items (order_id, item_no, item_description, hsn_sac, quantity, quantity_pending, quantity_done, unit, rate, amount) VALUES
(1, 1, '1/2 kg - Bracket Hooks', '73181500', 500, 500, 0, 'no.s', 6.195, 3097.50),
(1, 2, '100mm Foot Valve', '84813000', 2, 2, 0, 'no.s', 6602.10, 13204.20),
(2, 1, 'FRP Hose Box', '39269099', 2, 2, 0, 'no.s', 4543.00, 9086.00),
(2, 2, 'Hose Box Glass', '70052100', 4, 4, 0, 'no.s', 295.00, 1180.00),
(2, 3, 'Eye Shower 1/2" Ball Valve', '84818090', 6, 6, 0, 'no.s', 1652.00, 9912.00);
