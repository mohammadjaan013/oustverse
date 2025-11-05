-- =====================================================
-- Create Missing Tables for Production Jobs Module
-- =====================================================

-- Create customers table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    contact_person VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample customers
INSERT IGNORE INTO customers (id, name, email, phone, contact_person) VALUES
(1, 'ABC Corporation', 'contact@abc.com', '123-456-7890', 'John Doe'),
(2, 'XYZ Industries', 'info@xyz.com', '098-765-4321', 'Jane Smith');

-- Create products table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'pcs',
    price DECIMAL(10,2) DEFAULT 0.00,
    cost DECIMAL(10,2) DEFAULT 0.00,
    stock_quantity DECIMAL(10,2) DEFAULT 0.00,
    min_stock DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample products
INSERT IGNORE INTO products (id, name, code, description, category, unit, price, cost, stock_quantity) VALUES
(1, 'Widget A', 'WDG-001', 'Standard widget product', 'Widgets', 'pcs', 100.00, 50.00, 500),
(2, 'Gadget B', 'GDG-002', 'Premium gadget', 'Gadgets', 'pcs', 200.00, 100.00, 300),
(3, 'Component C', 'CMP-003', 'Electronic component', 'Components', 'pcs', 50.00, 25.00, 1000);

-- Create users table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample user (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, full_name, role, status) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', 'active');

-- Done!
-- All required tables for Production Jobs module are now created
