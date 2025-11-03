# Biziverse ERP - System Architecture

## System Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         BROWSER                              │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐           │
│  │   Login    │  │ Dashboard  │  │  Modules   │           │
│  └────────────┘  └────────────┘  └────────────┘           │
└────────────────────────┬─────────────────────────────────────┘
                         │ HTTP Request
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    APACHE / NGINX                            │
│                     (.htaccess)                              │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   PHP APPLICATION                            │
│  ┌────────────────────────────────────────────────────┐    │
│  │              includes/header.php                    │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  • Authentication Check                       │  │    │
│  │  │  • Session Validation                         │  │    │
│  │  │  • CSRF Token Generation                      │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │            PAGE / CONTROLLER                        │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  index.php         → Dashboard               │  │    │
│  │  │  inventory.php     → InventoryController     │  │    │
│  │  │  suppliers.php     → SupplierController      │  │    │
│  │  │  purchase_orders.php → PurchaseController    │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │               MODEL LAYER                           │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  models/Inventory.php                        │  │    │
│  │  │  models/Supplier.php                         │  │    │
│  │  │  models/Purchase.php                         │  │    │
│  │  │  • CRUD Operations                            │  │    │
│  │  │  • Data Validation                            │  │    │
│  │  │  • Business Logic                             │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │            DATABASE LAYER (PDO)                     │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  includes/db.php (Singleton)                 │  │    │
│  │  │  • Prepared Statements                        │  │    │
│  │  │  • Transaction Management                     │  │    │
│  │  │  • Connection Pooling                         │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │              VIEW LAYER                             │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  views/inventory/list.php                    │  │    │
│  │  │  views/suppliers/form.php                    │  │    │
│  │  │  • HTML Templates                             │  │    │
│  │  │  • DataTables                                 │  │    │
│  │  │  • Bootstrap Modals                           │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │              includes/footer.php                    │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │  • JavaScript Libraries                       │  │    │
│  │  │  • Custom Scripts                             │  │    │
│  │  │  • Analytics                                  │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    MYSQL DATABASE                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    Users     │  │  Suppliers   │  │   Items      │     │
│  │   Ledgers    │  │  Purchase    │  │   Stock      │     │
│  │    Tasks     │  │   Orders     │  │  Movements   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

## Database Relationships

```
users (1) ──────┬───────► (N) audit_logs
                │
                ├───────► (N) purchase_orders (created_by)
                │
                ├───────► (N) items (created_by)
                │
                └───────► (N) tasks (assignee_id)

suppliers (1) ──────────► (N) purchase_orders
                │
                └───────► (N) supplier_contacts

items (1) ───────┬──────► (N) purchase_order_items
                 │
                 ├──────► (N) sales_order_items
                 │
                 ├──────► (N) stock_movements
                 │
                 ├──────► (N) stock_valuations
                 │
                 └──────► (N) bom (as product or component)

purchase_orders (1) ────► (N) purchase_order_items

locations (1) ───────┬──► (N) stock_movements (from)
                     │
                     ├──► (N) stock_movements (to)
                     │
                     └──► (N) stock_valuations

production_jobs (1) ────► (N) job_stages

ledgers (1) ────────────► (N) voucher_entries

vouchers (1) ───────────► (N) voucher_entries
```

## Module Architecture

```
┌─────────────────────────────────────────────────────┐
│              INVENTORY MODULE                        │
├─────────────────────────────────────────────────────┤
│                                                      │
│  inventory.php (Main Page)                          │
│       │                                              │
│       ├──► models/Inventory.php                     │
│       │        • getItems()                          │
│       │        • getItemById()                       │
│       │        • createItem()                        │
│       │        • updateItem()                        │
│       │        • deleteItem()                        │
│       │        • getStockMovements()                 │
│       │        • addStockMovement()                  │
│       │                                              │
│       ├──► controllers/InventoryController.php      │
│       │        • handleList()                        │
│       │        • handleCreate()                      │
│       │        • handleUpdate()                      │
│       │        • handleDelete()                      │
│       │        • handleStockIn()                     │
│       │        • handleStockOut()                    │
│       │        • handleExport()                      │
│       │                                              │
│       └──► views/inventory/                         │
│                • list.php (DataTable)               │
│                • form.php (Modal)                   │
│                • movements.php                      │
│                                                      │
└─────────────────────────────────────────────────────┘
```

## Request Flow Example

```
User clicks "Inventory" in sidebar
           │
           ▼
1. Browser sends GET /inventory.php
           │
           ▼
2. includes/auth.php checks authentication
   • Validates session
   • Checks role permissions
           │
           ▼
3. includes/header.php renders
   • Top navbar
   • Sidebar with active highlight
           │
           ▼
4. InventoryController::handleList()
   • Gets filter parameters
   • Calls model methods
           │
           ▼
5. Inventory::getItems($filters)
   • Builds SQL query
   • Executes via PDO
   • Returns data array
           │
           ▼
6. views/inventory/list.php renders
   • Passes data to DataTable
   • Renders action buttons
   • Includes modals
           │
           ▼
7. includes/footer.php renders
   • JavaScript includes
   • DataTable initialization
   • Event handlers
           │
           ▼
8. HTML sent to browser
```

## Security Layers

```
┌───────────────────────────────────────────────┐
│         SECURITY ARCHITECTURE                  │
├───────────────────────────────────────────────┤
│                                                │
│  1. Apache Level (.htaccess)                  │
│     • Disable directory browsing              │
│     • Block sensitive files                   │
│     • Security headers                        │
│                                                │
│  2. Authentication (includes/auth.php)        │
│     • Session validation                      │
│     • Role-based access                       │
│     • Session timeout                         │
│     • CSRF token generation                   │
│                                                │
│  3. Input Validation                          │
│     • Sanitize all inputs                     │
│     • Type checking                           │
│     • Format validation                       │
│                                                │
│  4. Database (includes/db.php)                │
│     • Prepared statements                     │
│     • Parameter binding                       │
│     • No direct SQL concatenation             │
│                                                │
│  5. Output Escaping                           │
│     • htmlspecialchars() on all outputs       │
│     • JSON encoding for AJAX                  │
│                                                │
│  6. Audit Logging                             │
│     • Log all critical actions                │
│     • Track user activities                   │
│     • IP address logging                      │
│                                                │
└───────────────────────────────────────────────┘
```

## File Organization Pattern

```
Each Module Follows:

module_name/
├── {module}.php           → Entry point (includes controller)
├── controllers/
│   └── {Module}Controller.php
├── models/
│   └── {Module}.php
├── views/{module}/
│   ├── list.php          → Main list view
│   ├── form.php          → Create/Edit form
│   ├── detail.php        → View details
│   └── {custom}.php      → Custom views

Example: Inventory Module
├── inventory.php
├── controllers/InventoryController.php
├── models/Inventory.php
├── views/inventory/
│   ├── list.php
│   ├── form.php
│   ├── movements.php
│   └── valuation.php
```

## API Response Pattern

```javascript
// Success Response
{
  "success": true,
  "message": "Item created successfully",
  "data": {
    "id": 123,
    "sku": "SKU001",
    "name": "Fire Extinguisher"
  }
}

// Error Response
{
  "success": false,
  "message": "SKU already exists",
  "data": null
}
```

## Technology Stack Visualization

```
┌─────────────────────────────────────────┐
│          FRONTEND                        │
├─────────────────────────────────────────┤
│ • Bootstrap 5.3.0 (Layout & Components) │
│ • jQuery 3.7.0 (DOM Manipulation)       │
│ • DataTables 1.13.6 (Tables)            │
│ • Select2 4.1.0 (Dropdowns)             │
│ • FontAwesome 6.4.0 (Icons)             │
│ • Chart.js 4.3.0 (Charts)               │
└─────────────────────────────────────────┘
                    ▲
                    │
                    ▼
┌─────────────────────────────────────────┐
│          BACKEND                         │
├─────────────────────────────────────────┤
│ • PHP 7.4+ (Business Logic)             │
│ • PDO (Database Access)                 │
│ • Session Management                    │
│ • MVC Pattern                           │
└─────────────────────────────────────────┘
                    ▲
                    │
                    ▼
┌─────────────────────────────────────────┐
│          DATABASE                        │
├─────────────────────────────────────────┤
│ • MySQL 5.7+ / MariaDB                  │
│ • 27 Tables                             │
│ • Foreign Key Constraints               │
│ • Indexes for Performance               │
└─────────────────────────────────────────┘
```

---

This architecture ensures:
✅ **Separation of Concerns** - MVC pattern
✅ **Security** - Multiple layers of protection
✅ **Scalability** - Modular design
✅ **Maintainability** - Clean code structure
✅ **Performance** - Optimized queries & indexing
