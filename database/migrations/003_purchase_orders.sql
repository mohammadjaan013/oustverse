-- Purchase Orders Tables
-- Date: 2025-11-03

-- Main purchase orders table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT(10) UNSIGNED NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_delivery_date` DATE NULL,
    `delivery_date` DATE NULL,
    `status` ENUM('draft', 'pending', 'approved', 'rejected', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft',
    `payment_terms` VARCHAR(100) NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    `shipping_charges` DECIMAL(15,2) DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
    `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `billing_address` TEXT NULL,
    `shipping_address` TEXT NULL,
    `notes` TEXT NULL,
    `terms_conditions` TEXT NULL,
    `created_by` INT(10) UNSIGNED NULL,
    `approved_by` INT(10) UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_po_number` (`po_number`),
    KEY `idx_supplier_id` (`supplier_id`),
    KEY `idx_status` (`status`),
    KEY `idx_order_date` (`order_date`),
    KEY `idx_created_by` (`created_by`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase order items table
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_id` INT(10) UNSIGNED NOT NULL,
    `product_id` INT(10) UNSIGNED NOT NULL,
    `description` TEXT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `received_quantity` DECIMAL(10,2) DEFAULT 0.00,
    `unit_price` DECIMAL(15,2) NOT NULL,
    `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
    `discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_product_id` (`product_id`),
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase order history/activity log
CREATE TABLE IF NOT EXISTS `purchase_order_activity` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_id` INT(10) UNSIGNED NOT NULL,
    `user_id` INT(10) UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_user_id` (`user_id`),
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase order payments
CREATE TABLE IF NOT EXISTS `purchase_order_payments` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `po_id` INT(10) UNSIGNED NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) NULL,
    `reference_number` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_by` INT(10) UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_payment_date` (`payment_date`),
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample supplier if not exists
INSERT IGNORE INTO `suppliers` (`id`, `code`, `name`, `contact_name`, `phone`, `email`, `city`, `state`, `country`, `created_by`) 
VALUES (1, 'SUP-001', 'Sample Supplier Ltd', 'John Doe', '9876543210', 'supplier@example.com', 'Mumbai', 'Maharashtra', 'India', 1);

-- Insert sample data
INSERT INTO `purchase_orders` (`po_number`, `supplier_id`, `order_date`, `expected_delivery_date`, `status`, `subtotal`, `tax_amount`, `total_amount`, `created_by`) 
VALUES 
('PO-2025-001', 1, '2025-11-01', '2025-11-15', 'pending', 10000.00, 1800.00, 11800.00, 1),
('PO-2025-002', 1, '2025-11-02', '2025-11-16', 'approved', 15000.00, 2700.00, 17700.00, 1);
