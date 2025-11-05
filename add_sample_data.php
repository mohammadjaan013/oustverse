<?php
/**
 * Add Sample Data Automatically
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

$database = Database::getInstance();
$db = $database->getConnection();

echo "<h1>Adding Sample Data</h1>";

// Add items
try {
    $sql = "INSERT INTO items (sku, name, description, unit, standard_cost, retail_price, reorder_level, is_active, created_at) VALUES
    ('PROD-001', 'Widget A', 'Standard widget for production', 'PCS', 50.00, 100.00, 10, 1, NOW()),
    ('PROD-002', 'Gadget B', 'Premium gadget assembly', 'PCS', 100.00, 200.00, 5, 1, NOW()),
    ('PROD-003', 'Component C', 'Electronic component', 'PCS', 25.00, 50.00, 20, 1, NOW())
    ON DUPLICATE KEY UPDATE name=name";
    
    $db->exec($sql);
    echo "<p>✅ Added 3 sample items</p>";
} catch (Exception $e) {
    echo "<p>❌ Items error: " . $e->getMessage() . "</p>";
}

// Add customers
try {
    $sql = "INSERT INTO customers (name, email, phone, address, created_at) VALUES
    ('ABC Corporation', 'contact@abc.com', '123-456-7890', '123 Business St, City', NOW()),
    ('XYZ Industries', 'info@xyz.com', '098-765-4321', '456 Industrial Ave, Town', NOW())
    ON DUPLICATE KEY UPDATE name=name";
    
    $db->exec($sql);
    echo "<p>✅ Added 2 sample customers</p>";
} catch (Exception $e) {
    echo "<p>❌ Customers error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>✅ Sample Data Added Successfully!</h2>";
echo '<p><a href="production_jobs.php">Go to Production Jobs</a></p>';
?>
