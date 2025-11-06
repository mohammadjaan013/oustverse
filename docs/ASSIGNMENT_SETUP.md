# Quick Setup Guide - Assignment Feature

## Step 1: Run Database Migration

Open MySQL command line or phpMyAdmin and run:

```bash
# Option 1: From MySQL command line
mysql -u root -p biziverse_erp < database/migrations/005_add_assigned_to_stock_movements.sql

# Option 2: From phpMyAdmin
# - Open phpMyAdmin
# - Select 'biziverse_erp' database
# - Go to SQL tab
# - Copy and paste content from: database/migrations/005_add_assigned_to_stock_movements.sql
# - Click Go
```

## Step 2: Verify Installation

Check if columns were added successfully:

```sql
DESCRIBE stock_movements;
```

You should see these new columns:
- `assigned_to` (int unsigned, NULL)
- `assignment_notes` (text, NULL)
- `assignment_status` (enum: 'pending','in_progress','completed','cancelled')

## Step 3: Test the Feature

1. **Login to the system**
   - Go to: http://localhost/biziverse-clone/
   - Login with: admin@biziverse.com / admin123

2. **Navigate to Inventory**
   - Click "Stock" in sidebar
   - Or go to: http://localhost/biziverse-clone/inventory.php

3. **Test Stock IN with Assignment**
   - Click "In / Receive" button (green)
   - Select any operation (e.g., "Purchase Inward")
   - Select a Store
   - **Select a person** from "Assign To" dropdown
   - Add some notes in "Assignment Notes"
   - Select items and quantities
   - Click "Select"
   - ✅ Success message should show "Stock received successfully and assigned"

4. **Test Stock OUT with Assignment**
   - Click "Out / Issue" button (orange)
   - Select any operation (e.g., "Dispatch")
   - Select a Store
   - Select a person from "Assign To" dropdown
   - Select items and quantities
   - Click "Select"
   - ✅ Success message should show assignment confirmation

5. **Verify in Database**
   ```sql
   SELECT sm.*, u.name as assigned_to_name, sm.assignment_notes, sm.assignment_status
   FROM stock_movements sm
   LEFT JOIN users u ON sm.assigned_to = u.id
   ORDER BY sm.id DESC
   LIMIT 5;
   ```

## Troubleshooting

### Issue: Migration fails with "Duplicate column" error
**Solution:** The columns already exist. Skip migration or drop them first:
```sql
ALTER TABLE stock_movements 
DROP COLUMN assigned_to,
DROP COLUMN assignment_notes,
DROP COLUMN assignment_status;
```

### Issue: "Assign To" dropdown is empty
**Solution:** Check if you have active users:
```sql
SELECT id, name, email, active FROM users;
```

If no users exist, create one:
```sql
INSERT INTO users (name, email, password_hash, role, active) 
VALUES ('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);
```

### Issue: JavaScript errors in console
**Solution:** Clear browser cache and reload:
- Press Ctrl+Shift+R (Windows/Linux)
- Press Cmd+Shift+R (Mac)

### Issue: Assignment not saving
**Solution:** Check PHP error logs:
```bash
# Windows (XAMPP)
type c:\xampp\apache\logs\error.log

# Check if columns exist
mysql -u root -p -e "DESCRIBE biziverse_erp.stock_movements"
```

## Files Modified/Created

### Created:
1. `database/migrations/005_add_assigned_to_stock_movements.sql` - Database migration
2. `docs/ASSIGNMENT_FEATURE.md` - Complete feature documentation
3. `docs/ASSIGNMENT_SETUP.md` - This file

### Modified:
1. `inventory.php` - Updated Select Items modal with assignment fields
2. `assets/js/inventory.js` - Added assignment data capture
3. `controllers/InventoryController.php` - Added assignment handling in stockIn/stockOut
4. `models/Inventory.php` - Updated addStockMovement to save assignment data

## Next Steps

After successful setup, consider implementing:

1. **Dashboard Widget** - Show "My Assignments" on homepage
2. **Notification System** - Email/SMS when assigned
3. **Assignment Management Page** - View all assignments
4. **Status Update API** - Allow users to update assignment status
5. **Reports** - Assignment completion rates, user performance

See `docs/ASSIGNMENT_FEATURE.md` for detailed implementation guides.

## Support

If you encounter issues:
1. Check browser console (F12) for JavaScript errors
2. Check Apache error logs for PHP errors
3. Verify database connection in `includes/config.php`
4. Ensure all users have `active = 1` in database
