-- ==============================================
-- Production Jobs Module - Database Schema
-- Simple Version (No Foreign Keys Initially)
-- ==============================================

-- Step 1: Create production_jobs table
CREATE TABLE IF NOT EXISTS production_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wip_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT DEFAULT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    target_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    special_instructions TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    INDEX idx_created_by (created_by),
    INDEX idx_status (status),
    INDEX idx_target_date (target_date),
    INDEX idx_wip_no (wip_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 2: Create production_job_items table
CREATE TABLE IF NOT EXISTS production_job_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_job_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_production_job (production_job_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Create production_job_stages table
CREATE TABLE IF NOT EXISTS production_job_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    production_job_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_production_job (production_job_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 4: Insert sample data for testing
-- Note: This will insert only if the WIP number doesn't exist
INSERT IGNORE INTO production_jobs (wip_no, customer_id, product_id, quantity, target_date, status, special_instructions, created_by)
VALUES (
    'WIP-2025-001',
    1,
    1,
    100,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    'pending',
    'Rush order - please prioritize',
    1
);

-- ============================================
-- Optional: Add Foreign Keys (Run if needed)
-- ============================================
-- Only run these if your customers, products, and users tables exist
-- and have the correct structure

-- Uncomment below to add foreign keys:

/*
-- Add foreign keys to production_jobs
ALTER TABLE production_jobs
    ADD CONSTRAINT fk_production_jobs_customer 
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

ALTER TABLE production_jobs
    ADD CONSTRAINT fk_production_jobs_product 
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE production_jobs
    ADD CONSTRAINT fk_production_jobs_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign keys to production_job_items
ALTER TABLE production_job_items
    ADD CONSTRAINT fk_job_items_production_job 
    FOREIGN KEY (production_job_id) REFERENCES production_jobs(id) ON DELETE CASCADE;

ALTER TABLE production_job_items
    ADD CONSTRAINT fk_job_items_product 
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Add foreign keys to production_job_stages
ALTER TABLE production_job_stages
    ADD CONSTRAINT fk_job_stages_production_job 
    FOREIGN KEY (production_job_id) REFERENCES production_jobs(id) ON DELETE CASCADE;
*/
