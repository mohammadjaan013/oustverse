-- Supplier Invoices Tables
-- Date: 2025-11-03

-- Main supplier invoices table
CREATE TABLE IF NOT EXISTS `supplier_invoices` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT(10) UNSIGNED NOT NULL,
    `invoice_type` ENUM('supplier_invoice', 'inter_state_transfer') DEFAULT 'supplier_invoice',
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `po_id` INT(10) UNSIGNED NULL,
    `reference` VARCHAR(100) NULL,
    `status` ENUM('draft', 'pending', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `subtotal` DECIMAL(15,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    `shipping_charges` DECIMAL(15,2) DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
    `source_branch` VARCHAR(100) NULL,
    `source_address` TEXT NULL,
    `notes` TEXT NULL,
    `terms_conditions` TEXT NULL,
    `created_by` INT(10) UNSIGNED NULL,
    `approved_by` INT(10) UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invoice_no` (`invoice_no`),
    KEY `idx_supplier_id` (`supplier_id`),
    KEY `idx_status` (`status`),
    KEY `idx_invoice_date` (`invoice_date`),
    KEY `idx_created_by` (`created_by`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier invoice items table
CREATE TABLE IF NOT EXISTS `supplier_invoice_items` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT(10) UNSIGNED NOT NULL,
    `item_id` INT(10) UNSIGNED NOT NULL,
    `description` TEXT NULL,
    `hsn_sac` VARCHAR(20) NULL,
    `qty` DECIMAL(10,2) NOT NULL,
    `unit` VARCHAR(20) DEFAULT 'PCS',
    `rate` DECIMAL(15,2) NOT NULL,
    `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    `taxable_amount` DECIMAL(15,2) NOT NULL,
    `cgst` DECIMAL(15,2) DEFAULT 0.00,
    `sgst` DECIMAL(15,2) DEFAULT 0.00,
    `igst` DECIMAL(15,2) DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invoice_id` (`invoice_id`),
    KEY `idx_item_id` (`item_id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `supplier_invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier invoice payments
CREATE TABLE IF NOT EXISTS `supplier_invoice_payments` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT(10) UNSIGNED NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) NULL,
    `reference_number` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_by` INT(10) UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invoice_id` (`invoice_id`),
    KEY `idx_payment_date` (`payment_date`),
    FOREIGN KEY (`invoice_id`) REFERENCES `supplier_invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO `supplier_invoices` (`invoice_no`, `supplier_id`, `invoice_type`, `invoice_date`, `due_date`, `status`, `subtotal`, `tax_amount`, `total_amount`, `created_by`) 
VALUES 
('Inv422', 1, 'supplier_invoice', '2022-06-10', '2022-07-10', 'approved', 1500.00, 270.00, 1770.00, 1);
