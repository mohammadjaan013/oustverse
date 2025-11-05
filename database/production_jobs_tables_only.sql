-- =====================================================
-- Production Jobs - Create Tables ONLY (No Sample Data)
-- Run this first to create the tables
-- =====================================================

-- Drop tables if they exist (optional - uncomment if you want to start fresh)
-- DROP TABLE IF EXISTS production_job_stages;
-- DROP TABLE IF EXISTS production_job_items;
-- DROP TABLE IF EXISTS production_jobs;

-- Create production_jobs table
CREATE TABLE production_jobs (
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

-- Create production_job_items table
CREATE TABLE production_job_items (
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

-- Create production_job_stages table
CREATE TABLE production_job_stages (
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
