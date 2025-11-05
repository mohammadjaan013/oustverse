-- Add sample items for testing Production Jobs
INSERT INTO items (sku, name, description, unit, standard_cost, retail_price, reorder_level, is_active, created_at) VALUES
('PROD-001', 'Widget A', 'Standard widget for production', 'PCS', 50.00, 100.00, 10, 1, NOW()),
('PROD-002', 'Gadget B', 'Premium gadget assembly', 'PCS', 100.00, 200.00, 5, 1, NOW()),
('PROD-003', 'Component C', 'Electronic component', 'PCS', 25.00, 50.00, 20, 1, NOW())
ON DUPLICATE KEY UPDATE name=name;

-- Add sample customers for testing
INSERT INTO customers (name, email, phone, address, created_at) VALUES
('ABC Corporation', 'contact@abc.com', '123-456-7890', '123 Business St, City', NOW()),
('XYZ Industries', 'info@xyz.com', '098-765-4321', '456 Industrial Ave, Town', NOW())
ON DUPLICATE KEY UPDATE name=name;
