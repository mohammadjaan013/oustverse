# Biziverse ERP Clone

A production-quality ERP (Enterprise Resource Planning) system clone built with plain PHP, MySQL, Bootstrap 5, and jQuery. This system includes comprehensive modules for managing suppliers, inventory, purchase orders, and supplier invoices (purchases).

## 🚀 Features

### ✅ Completed Modules

#### 1. **Inventory Management**
- Product/Item management with SKU generation
- Multi-location stock tracking
- Stock movements (in/out/transfer)
- Low stock alerts
- Categories and units management
- Real-time stock updates

#### 2. **Suppliers Management**
- Complete supplier CRUD operations
- 30+ fields including contact details, GST, PAN, MSME
- Multiple contact persons per supplier
- Address and GST management
- Supplier categorization (vendor, manufacturer, distributor, service provider)
- Industry and segment tracking

#### 3. **Purchase Orders**
- Create and manage purchase orders
- Dynamic item selection with auto-calculation
- Tax calculations (CGST, SGST)
- PO approval workflow
- Status tracking (draft, pending, approved, received, cancelled)
- Auto-generated PO numbers (PO-YYYYMM-0001)
- Copy from existing PO
- Terms & conditions management

#### 4. **Purchases (Supplier Invoices)**
- Supplier invoice management
- Inter-state transfer support
- Dynamic item list with automatic tax calculations
- Payment tracking
- Multiple payment modes
- Invoice approval workflow
- Auto-generated invoice numbers (INV-YYYYMM-0001)
- Credit month tracking
- Payment status (unpaid, partial, paid)

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ (No frameworks, pure PHP)
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3.0
- **JavaScript**: jQuery 3.7.0
- **DataTables**: 1.13.6 (for advanced table features)
- **Select2**: 4.1.0 (for enhanced dropdowns)
- **Icons**: FontAwesome 6.4.0

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## 📦 Installation

### Local Development (XAMPP/WAMP)
  - **Inventory Management** - Stock in/out/transfer, valuations, multiple locations
  - **Purchase Orders** - Full approval workflow
  - **Suppliers** - CRUD with CSV import/export
  - **Accounts** - Ledgers, vouchers, P&L reports
  - **Manufacturing** - Production jobs, BOM, job stages
  - **Tasks** - To-do board with assignment tracking
- **Audit Logging** - Complete activity tracking
- **Responsive UI** - Bootstrap 5 with DataTables, Select2, Chart.js

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP Extension

## 🛠️ Installation

1. **Clone or download** this repository to your web server directory:
   ```bash
   cd c:\xampp\htdocs
   ```

2. **Create Database** and import schema:
   ```bash
   # Access MySQL
   mysql -u root -p
   
   # Run migrations
   source migrations/001_core_tables.sql
   source migrations/002_accounts_tasks_audit.sql
   ```

3. **Configure Database Connection**:
   - Edit `includes/config.php`
   - Update `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

4. **Seed Sample Data** (Optional):
   ```bash
   php seed.php
   ```

5. **Set Permissions**:
   ```bash
   chmod 755 -R biziverse-clone
   chmod 777 uploads/
   ```

6. **Access Application**:
   - Open browser: `http://localhost/biziverse-clone`
   - Login with:
     - Email: `admin@biziverse.com`
     - Password: `admin123`

## 📁 Project Structure

```
biziverse-clone/
├── assets/
│   ├── css/style.css          # Custom styles
│   ├── js/script.js           # Custom JavaScript
│   └── images/                # Images and icons
├── controllers/               # Business logic controllers
│   ├── InventoryController.php
│   ├── PurchaseController.php
│   ├── SupplierController.php
│   └── ...
├── models/                    # Database models
│   ├── Inventory.php
│   ├── Purchase.php
│   └── ...
├── views/                     # Page templates
│   ├── inventory/
│   ├── purchases/
│   └── ...
├── includes/
│   ├── config.php            # Configuration
│   ├── db.php                # Database connection
│   ├── auth.php              # Authentication
│   ├── functions.php         # Helper functions
│   ├── header.php            # Common header
│   └── footer.php            # Common footer
├── migrations/               # SQL schema files
├── index.php                 # Dashboard
├── login.php                 # Login page
├── logout.php                # Logout handler
└── seed.php                  # Sample data seeder
```

## 👥 User Roles

- **Admin** - Full access to all modules
- **Manager** - Access to most modules, no settings
- **Accountant** - Access to accounts and reports
- **Storekeeper** - Access to inventory and purchases
- **User** - Limited read-only access

## 🔒 Security Features

- Password hashing with bcrypt
- CSRF token protection
- Prepared statements (PDO)
- Input sanitization
- Session management
- Role-based access control
- Audit logging

## 📊 Core Modules

### Inventory Management
- Item master with categories
- Stock movements (in/out/transfer)
- Multiple warehouse support
- Valuation reports
- Low stock alerts

### Purchase Orders
- Create/Edit/Approve workflow
- Link to suppliers
- Receive goods
- Auto-update inventory

### Suppliers
- Complete CRUD operations
- CSV import/export
- Contact management
- Payment terms tracking

### Accounts
- Chart of accounts
- Voucher entries
- P&L and Balance Sheet
- Debit/Credit validation

## 🎨 UI Components

- Collapsible sidebar navigation
- DataTables for lists
- Bootstrap modals for forms
- Select2 for dropdowns
- FontAwesome icons
- Responsive design

## 🧪 Testing

Run the seed script to populate test data:
```bash
php seed.php
```

## 📝 License

This project is open-source and available for educational purposes.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📧 Support

For support, email: support@biziverse.com

---

**Built with ❤️ using Plain PHP**
