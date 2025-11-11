-- ============================================
-- Migration: Quotes/Quotations Module
-- ============================================

USE biziverse_erp;

-- Quotations table
CREATE TABLE IF NOT EXISTS `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `customer_id` int(11) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` text,
  `copy_from` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `sales_credit` varchar(100) DEFAULT 'None',
  `shipping_address` text,
  `same_as_billing` tinyint(1) DEFAULT 1,
  `quotation_date` date NOT NULL,
  `valid_till` date NOT NULL,
  `issued_by` int(11) UNSIGNED DEFAULT NULL,
  `issued_by_name` varchar(255) DEFAULT NULL,
  `executive_id` int(11) UNSIGNED DEFAULT NULL,
  `executive_name` varchar(255) DEFAULT NULL,
  `type` enum('quotation','proforma_invoice') DEFAULT 'quotation',
  `status` enum('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `extra_charges` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `terms_conditions` text,
  `bank_details` text,
  `upload_file` varchar(255) DEFAULT NULL,
  `save_as_template` tinyint(1) DEFAULT 0,
  `share_by_email` tinyint(1) DEFAULT 0,
  `share_by_whatsapp` tinyint(1) DEFAULT 0,
  `print_after_saving` tinyint(1) DEFAULT 0,
  `alert_on_opening` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_no` (`quote_no`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_quotation_date` (`quotation_date`),
  KEY `idx_valid_till` (`valid_till`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`executive_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quotation items table
CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `item_no` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `item_description` text NOT NULL,
  `hsn_sac` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) DEFAULT 'Nos',
  `rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `taxable_amount` decimal(15,2) DEFAULT '0.00',
  `cgst_percent` decimal(5,2) DEFAULT '0.00',
  `cgst_amount` decimal(15,2) DEFAULT '0.00',
  `sgst_percent` decimal(5,2) DEFAULT '0.00',
  `sgst_amount` decimal(15,2) DEFAULT '0.00',
  `igst_percent` decimal(5,2) DEFAULT '0.00',
  `igst_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `lead_time` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quotation` (`quotation_id`),
  FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quotation terms table
CREATE TABLE IF NOT EXISTS `quotation_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `term_condition` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_quotation` (`quotation_id`),
  FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO quotations (quote_no, customer_name, contact_person, quotation_date, valid_till, total_amount, type, status, issued_by, created_by, created_at) VALUES
('1390', 'RAISING SUN IMPEX PRIVATE LIMITED', 'Kazim Bhatkar', '2025-11-01', '2025-11-01', 88913.00, 'quotation', 'sent', 1, 1, '2025-11-01'),
('1391', 'GREENS APEX CO-OPERATIVE HOUSING ASSOCIATION LTD', 'Poonam More', '2025-11-03', '2025-12-03', 153400.00, 'quotation', 'sent', 1, 1, '2025-11-03'),
('1392', 'Abhyudaya Co-op. Bank Ltd.', 'Kazim Bhatkar', '2025-11-04', '2025-11-04', 263848.00, 'quotation', 'sent', 1, 1, '2025-11-04'),
('1376', 'Infraaxis Propserve Private Limited', 'Poonam More', '2025-11-10', '2025-12-10', 45312.00, 'quotation', 'sent', 1, 1, '2025-11-09'),
('1393', 'Afcons Infrastructure Limited', 'Poonam More', '2025-11-10', '2025-12-10', 32332.00, 'quotation', 'sent', 1, 1, '2025-11-09');

-- Insert sample quotation items
INSERT INTO quotation_items (quotation_id, item_no, item_description, hsn_sac, quantity, unit, rate, amount) VALUES
(1, 1, 'Fire Extinguisher ABC 4KG', '84243100', 10, 'Nos', 800.00, 8000.00),
(2, 1, 'Fire Extinguisher CO2 5KG', '84243100', 20, 'Nos', 1500.00, 30000.00);
