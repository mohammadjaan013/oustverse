# Quick Setup Guide - Biziverse ERP Clone

## Step 1: Database Setup

1. Open **phpMyAdmin** or MySQL command line
2. Run the migration files in order:

```sql
-- First migration
source C:/xampp/htdocs/biziverse-clone/migrations/001_core_tables.sql

-- Second migration
source C:/xampp/htdocs/biziverse-clone/migrations/002_accounts_tasks_audit.sql
```

Or manually import via phpMyAdmin:
- Go to http://localhost/phpmyadmin
- Create database `biziverse_erp`
- Import `001_core_tables.sql`
- Import `002_accounts_tasks_audit.sql`

## Step 2: Configuration

The default configuration in `includes/config.php` is already set for XAMPP:
```php
DB_HOST: localhost
DB_NAME: biziverse_erp
DB_USER: root
DB_PASS: (empty)
```

If your MySQL has a password, update `includes/config.php`:
```php
define('DB_PASS', 'your_password_here');
```

## Step 3: Seed Sample Data (Optional)

Run from command line or browser:

**Command Line:**
```cmd
cd C:\xampp\htdocs\biziverse-clone
php seed.php
```

**Or via Browser:**
Navigate to: `http://localhost/biziverse-clone/seed.php`

This will create:
- 3 users (admin, manager, accountant)
- Sample items and categories
- Sample suppliers and customers
- Sample purchase and sales orders
- Sample leads and tasks

## Step 4: Access the Application

1. Open browser and go to: `http://localhost/biziverse-clone`
2. You'll be redirected to login page
3. Use these credentials:

**Admin Account:**
- Email: `admin@biziverse.com`
- Password: `admin123`

**Manager Account:**
- Email: `manager@biziverse.com`
- Password: `admin123`

**Accountant Account:**
- Email: `accountant@biziverse.com`
- Password: `admin123`

## Step 5: What's Working Now

✅ **Completed Features:**
- User authentication with role-based access
- Dashboard with module tiles (matching your screenshot)
- Sidebar navigation with Sales and ERP sections
- Database schema for all modules
- Header, footer, and layout templates
- Custom CSS matching Biziverse design
- Helper functions and utilities

## Step 6: Next Steps

The following modules are ready for implementation:

### Priority 1: Inventory Module
- Item list with DataTables
- Add/Edit/Delete items
- Stock In/Out modals
- Stock movement tracking
- Valuation reports

### Priority 2: Suppliers Module
- Supplier list with search/filter
- Add/Edit/Delete suppliers
- CSV import/export
- Contact management

### Priority 3: Purchase Orders
- PO creation with line items
- Approval workflow
- Receive goods functionality
- Auto-update inventory

### Priority 4: Additional Modules
- Accounts (ledgers, vouchers)
- Manufacturing (production jobs)
- Tasks (kanban board)
- Reports and analytics

## Troubleshooting

### Issue: Database connection failed
**Solution:** Check MySQL is running in XAMPP control panel

### Issue: Page not found
**Solution:** Ensure you're accessing via `localhost/biziverse-clone` not `localhost` alone

### Issue: Permission denied on uploads
**Solution:** 
```cmd
# For Windows, right-click uploads folder
# Properties > Security > Edit > Add "Everyone" with Full Control
```

### Issue: Session timeout too quick
**Solution:** Update `includes/config.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 hours
```

## File Permissions (Linux/Mac)

```bash
chmod 755 -R biziverse-clone/
chmod 777 biziverse-clone/uploads/
```

## Development Tips

1. **Enable Error Display** (for development only):
   In `includes/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Check Audit Logs:**
   ```sql
   SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50;
   ```

3. **Reset Admin Password:**
   ```sql
   UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE email = 'admin@biziverse.com';
   -- Password: admin123
   ```

## Current Project Status

📊 **Progress: 40%**

**Completed:**
- ✅ Database schema and migrations
- ✅ Authentication system
- ✅ Dashboard UI
- ✅ Navigation and layout
- ✅ Configuration and helpers
- ✅ Sample data seeder

**Next Up:**
- 🔄 Inventory module implementation
- 🔄 Suppliers module implementation
- 🔄 Purchase orders module
- ⏳ Accounts module
- ⏳ Manufacturing module
- ⏳ Tasks module

## Need Help?

If you encounter any issues:
1. Check the browser console for JavaScript errors
2. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
3. Verify database tables were created: `SHOW TABLES;`
4. Ensure all required extensions are enabled in `php.ini`

---

**Ready to continue with module implementation!** 🚀
