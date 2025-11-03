<?php
/**
 * Database Seed File
 * Populate the database with sample data for testing
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    
    echo "Starting database seeding...\n\n";
    
    // Seed Categories
    echo "Seeding categories...\n";
    $categories = [
        ['Raw Materials', null],
        ['Finished Goods', null],
        ['Consumables', null],
        ['Safety Equipment', null],
    ];
    
    $catStmt = $db->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    
    // Seed Items
    echo "Seeding items...\n";
    $items = [
        ['SKU001', 'Fire Extinguisher 5KG', 'ABC Type Fire Extinguisher', 1, 'PCS', 850.00, 1200.00, 10],
        ['SKU002', 'Safety Helmet', 'Industrial Safety Helmet', 4, 'PCS', 120.00, 200.00, 50],
        ['SKU003', 'Fire Alarm System', 'Addressable Fire Alarm Panel', 2, 'SET', 15000.00, 25000.00, 5],
        ['SKU004', 'Safety Shoes', 'Steel Toe Safety Shoes', 4, 'PAIR', 450.00, 800.00, 30],
        ['SKU005', 'Fire Hose Pipe', '10 meter fire hose with nozzle', 1, 'PCS', 1200.00, 2000.00, 15],
    ];
    
    $itemStmt = $db->prepare("INSERT INTO items (sku, name, description, category_id, unit, standard_cost, retail_price, reorder_level, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    foreach ($items as $item) {
        $itemStmt->execute($item);
    }
    
    // Seed Suppliers
    echo "Seeding suppliers...\n";
    $suppliers = [
        ['SUP001', 'ABC Safety Solutions', 'Rajesh Kumar', '9876543210', 'rajesh@abcsafety.com', 'Chennai', 'Tamil Nadu', '600001'],
        ['SUP002', 'FireTech Industries', 'Priya Sharma', '9876543211', 'priya@firetech.com', 'Mumbai', 'Maharashtra', '400001'],
        ['SUP003', 'SafeGuard Equipments', 'Amit Patel', '9876543212', 'amit@safeguard.com', 'Ahmedabad', 'Gujarat', '380001'],
        ['SUP004', 'Industrial Safety Corp', 'Suresh Reddy', '9876543213', 'suresh@indsafety.com', 'Hyderabad', 'Telangana', '500001'],
    ];
    
    $supStmt = $db->prepare("INSERT INTO suppliers (code, name, contact_name, phone, email, city, state, pincode, created_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    foreach ($suppliers as $sup) {
        $supStmt->execute($sup);
    }
    
    // Seed initial stock
    echo "Seeding stock valuations...\n";
    $stock = [
        [1, 1, 100],
        [2, 1, 250],
        [3, 1, 10],
        [4, 1, 150],
        [5, 1, 50],
    ];
    
    $stockStmt = $db->prepare("INSERT INTO stock_valuations (item_id, location_id, qty_on_hand, total_value) VALUES (?, ?, ?, ?)");
    foreach ($stock as $s) {
        $total_value = $s[2] * 100; // Simplified calculation
        $stockStmt->execute([$s[0], $s[1], $s[2], $total_value]);
    }
    
    // Seed Customers
    echo "Seeding customers...\n";
    $customers = [
        ['CUST001', 'Tech Solutions Pvt Ltd', 'Ramesh Kumar', '9988776655', 'ramesh@techsol.com', 'Bangalore', 'Karnataka', '560001'],
        ['CUST002', 'Manufacturing Industries', 'Lakshmi Devi', '9988776656', 'lakshmi@mfg.com', 'Coimbatore', 'Tamil Nadu', '641001'],
        ['CUST003', 'Retail Enterprises', 'Vijay Singh', '9988776657', 'vijay@retail.com', 'Delhi', 'Delhi', '110001'],
    ];
    
    $custStmt = $db->prepare("INSERT INTO customers (code, name, contact_name, phone, email, city, state, pincode, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    foreach ($customers as $cust) {
        $custStmt->execute($cust);
    }
    
    // Seed Sample Purchase Order
    echo "Seeding purchase orders...\n";
    $poStmt = $db->prepare("INSERT INTO purchase_orders (po_no, supplier_id, date, status, taxable_amount, tax_amount, total_amount, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $poStmt->execute(['PO-2024-001', 1, '2024-11-01', 'approved', 10000.00, 1800.00, 11800.00]);
    $poId = $db->lastInsertId();
    
    // PO Items
    $poItemStmt = $db->prepare("INSERT INTO purchase_order_items (purchase_order_id, item_id, qty, rate, tax_percent, tax_amount, total) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
    $poItemStmt->execute([$poId, 1, 10, 850.00, 18, 1530.00, 10030.00]);
    $poItemStmt->execute([$poId, 2, 20, 120.00, 18, 432.00, 2832.00]);
    
    // Seed Sample Sales Order
    echo "Seeding sales orders...\n";
    $soStmt = $db->prepare("INSERT INTO sales_orders (so_no, customer_id, date, status, taxable_amount, tax_amount, total_amount, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $soStmt->execute(['SO-2024-001', 1, '2024-11-02', 'confirmed', 15000.00, 2700.00, 17700.00]);
    $soId = $db->lastInsertId();
    
    // SO Items
    $soItemStmt = $db->prepare("INSERT INTO sales_order_items (sales_order_id, item_id, qty, rate, tax_percent, tax_amount, total) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
    $soItemStmt->execute([$soId, 1, 10, 1200.00, 18, 2160.00, 14160.00]);
    $soItemStmt->execute([$soId, 4, 5, 800.00, 18, 720.00, 4720.00]);
    
    // Seed Leads
    echo "Seeding leads...\n";
    $leads = [
        ['John Doe', 'ABC Corp', '9876543220', 'john@abc.com', 'Website', 'new', 1],
        ['Jane Smith', 'XYZ Industries', '9876543221', 'jane@xyz.com', 'Referral', 'contacted', 1],
    ];
    
    $leadStmt = $db->prepare("INSERT INTO leads (name, company, phone, email, source, status, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($leads as $lead) {
        $leadStmt->execute($lead);
    }
    
    // Seed Tasks
    echo "Seeding tasks...\n";
    $tasks = [
        ['Follow up with ABC Corp', 'Contact John Doe regarding fire safety quotation', 1, '2024-11-15', 'high', 'inbox', 1],
        ['Update inventory records', 'Verify physical stock count', 1, '2024-11-10', 'medium', 'inbox', 1],
        ['Prepare monthly report', 'Generate P&L and stock reports', 2, '2024-11-30', 'low', 'inbox', 1],
    ];
    
    $taskStmt = $db->prepare("INSERT INTO tasks (title, description, assignee_id, due_date, priority, status, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($tasks as $task) {
        $taskStmt->execute($task);
    }
    
    echo "\n✅ Database seeding completed successfully!\n";
    echo "You can now login with:\n";
    echo "Email: admin@biziverse.com\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
