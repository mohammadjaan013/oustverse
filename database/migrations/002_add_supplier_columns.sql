-- Migration: Add missing columns to suppliers table
-- Date: 2025-11-03
-- Description: Adding additional fields for supplier management

ALTER TABLE `suppliers` 
ADD COLUMN `type` ENUM('vendor', 'manufacturer', 'distributor', 'service_provider') DEFAULT 'vendor' AFTER `code`,
ADD COLUMN `mobile` VARCHAR(20) NULL AFTER `phone`,
ADD COLUMN `whatsapp` VARCHAR(20) NULL AFTER `mobile`,
ADD COLUMN `website` VARCHAR(255) NULL AFTER `email`,
ADD COLUMN `industry` VARCHAR(100) NULL AFTER `website`,
ADD COLUMN `segment` VARCHAR(50) NULL AFTER `industry`,
ADD COLUMN `msme_no` VARCHAR(50) NULL AFTER `gstin`,
ADD COLUMN `pan` VARCHAR(10) NULL AFTER `msme_no`,
ADD COLUMN `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active' AFTER `pan`,
ADD COLUMN `credit_days` INT DEFAULT 30 AFTER `credit_limit`,
ADD COLUMN `opening_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `credit_days`;

-- Add indexes for better performance
CREATE INDEX `idx_suppliers_type` ON `suppliers`(`type`);
CREATE INDEX `idx_suppliers_status` ON `suppliers`(`status`);
CREATE INDEX `idx_suppliers_industry` ON `suppliers`(`industry`);
CREATE INDEX `idx_suppliers_pan` ON `suppliers`(`pan`);
CREATE INDEX `idx_suppliers_msme_no` ON `suppliers`(`msme_no`);

-- Also add missing columns to supplier_contacts table
ALTER TABLE `supplier_contacts`
ADD COLUMN `mobile` VARCHAR(20) NULL AFTER `phone`,
ADD COLUMN `whatsapp` VARCHAR(20) NULL AFTER `mobile`,
ADD COLUMN `notes` TEXT NULL AFTER `is_primary`;
