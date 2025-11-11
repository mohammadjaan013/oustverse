-- Invoices Module Migration
-- Creates tables for invoice management

-- Drop existing tables if they exist
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoice_terms;
DROP TABLE IF EXISTS invoices;

-- Invoices table
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    reference VARCHAR(100),
    invoice_type ENUM('party_invoice', 'cash_memo', 'inter_state_transfer') DEFAULT 'party_invoice',
    
    -- Customer details
    customer_id INT UNSIGNED,
    customer_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    branch_id INT UNSIGNED,
    sales_credit VARCHAR(100) DEFAULT 'None',
    
    -- Address
    billing_address TEXT,
    shipping_address TEXT,
    same_as_billing TINYINT(1) DEFAULT 0,
    shipping_details TEXT,
    
    -- Dates
    invoice_date DATE NOT NULL,
    due_date DATE,
    
    -- Financial
    subtotal DECIMAL(15, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    tax_amount DECIMAL(15, 2) DEFAULT 0,
    extra_charges DECIMAL(15, 2) DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL,
    taxable_amount DECIMAL(15, 2) DEFAULT 0,
    
    -- Payment & Recovery
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid',
    paid_amount DECIMAL(15, 2) DEFAULT 0,
    pending_amount DECIMAL(15, 2) DEFAULT 0,
    recovery_amount DECIMAL(15, 2) DEFAULT 0,
    
    -- Bank & Payment
    bank_details TEXT,
    
    -- Additional
    notes TEXT,
    internal_notes TEXT,
    
    -- Template & Sharing
    is_template TINYINT(1) DEFAULT 0,
    template_name VARCHAR(255),
    share_by_email TINYINT(1) DEFAULT 0,
    share_by_whatsapp TINYINT(1) DEFAULT 0,
    print_after_saving TINYINT(1) DEFAULT 0,
    
    -- Audit
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_no (invoice_no),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_invoice_type (invoice_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoice Items table
CREATE TABLE invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    item_description TEXT NOT NULL,
    hsn_sac VARCHAR(50),
    quantity DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'nos',
    rate DECIMAL(15, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    taxable_amount DECIMAL(15, 2) DEFAULT 0,
    cgst_percent DECIMAL(5, 2) DEFAULT 0,
    cgst_amount DECIMAL(15, 2) DEFAULT 0,
    sgst_percent DECIMAL(5, 2) DEFAULT 0,
    sgst_amount DECIMAL(15, 2) DEFAULT 0,
    amount DECIMAL(15, 2) NOT NULL,
    sort_order INT DEFAULT 0,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoice Terms & Conditions table
CREATE TABLE invoice_terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    term_condition TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO invoices (
    invoice_no, reference, invoice_type, customer_name, contact_person,
    billing_address, shipping_address, invoice_date, due_date,
    subtotal, tax_amount, total_amount, taxable_amount, 
    payment_status, pending_amount, created_by
) VALUES
(
    'INV-001', 'REF-2024-001', 'party_invoice', 'Abhyudaya Co-op. Bank Ltd.', 'Mr. Sharma',
    '123 Bank Street, Mumbai - 400001', '123 Bank Street, Mumbai - 400001',
    '2024-11-04', '2024-11-04',
    2000.00, 360.00, 2638.48, 2236.00,
    'overdue', 2638.48, 1
),
(
    'INV-002', 'REF-2024-002', 'party_invoice', 'Balaji Heights', 'Mr. Patel',
    '456 Heights Road, Pune - 411001', '456 Heights Road, Pune - 411001',
    '2024-11-10', '2024-11-15',
    5000.00, 900.00, 5900.00, 5000.00,
    'unpaid', 5900.00, 1
),
(
    'INV-003', 'REF-2024-003', 'cash_memo', 'Lubrizol India Pvt. Ltd.', 'Ms. Reddy',
    '789 Industrial Area, Bangalore - 560001', '789 Industrial Area, Bangalore - 560001',
    '2024-11-11', '2024-11-11',
    8000.00, 1440.00, 9440.00, 8000.00,
    'paid', 0.00, 1
);

-- Insert sample invoice items
INSERT INTO invoice_items (
    invoice_id, item_description, hsn_sac, quantity, unit, rate,
    discount_percent, taxable_amount, cgst_percent, sgst_percent, amount, sort_order
) VALUES
-- Invoice 1 items
(1, 'Bracket Hooks - Standard Size', '7326', 50, 'nos', 40.00, 0, 2000.00, 9, 9, 2360.00, 1),
-- Invoice 2 items
(2, 'Foot Valve 25mm', '8481', 10, 'nos', 500.00, 0, 5000.00, 9, 9, 5900.00, 1),
-- Invoice 3 items
(3, 'FRP Hose Box Type A', '3925', 20, 'nos', 400.00, 0, 8000.00, 9, 9, 9440.00, 1);

-- Insert sample terms
INSERT INTO invoice_terms (invoice_id, term_condition, sort_order) VALUES
(1, 'Payment due on delivery', 1),
(1, 'Goods once sold will not be taken back', 2),
(2, 'Payment within 7 days of invoice date', 1),
(2, 'Late payment will attract 2% interest per month', 2),
(3, 'Immediate payment required for cash memo', 1);
