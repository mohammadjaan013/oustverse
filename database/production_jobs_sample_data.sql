-- =====================================================
-- Production Jobs - Insert Sample Data
-- Run this AFTER creating the tables
-- =====================================================

-- Insert sample production job
INSERT INTO production_jobs 
    (wip_no, customer_id, product_id, quantity, target_date, status, special_instructions, created_by)
VALUES 
    ('WIP-2025-001', 1, 1, 100, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'pending', 'Rush order - please prioritize', 1);

-- You can add more sample jobs here:
-- INSERT INTO production_jobs 
--     (wip_no, product_id, quantity, target_date, status, created_by)
-- VALUES 
--     ('WIP-2025-002', 2, 50, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'pending', 1);
