-- ============================================
-- Migration: Add assigned_to field to stock_movements
-- Purpose: Track which user is assigned/responsible for each stock movement
-- ============================================

USE biziverse_erp;

-- Add assigned_to column if not exists
ALTER TABLE stock_movements 
ADD COLUMN IF NOT EXISTS assigned_to INT UNSIGNED NULL AFTER created_by,
ADD COLUMN IF NOT EXISTS assignment_notes TEXT NULL AFTER notes,
ADD COLUMN IF NOT EXISTS assignment_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending' AFTER assigned_to,
ADD INDEX idx_assigned_to (assigned_to),
ADD FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing records to set assignment_status to completed (as they're already done)
UPDATE stock_movements SET assignment_status = 'completed' WHERE assigned_to IS NULL;
