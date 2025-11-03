# Inventory Module - Testing Guide

## Quick Start Testing

### Step 1: Setup Database
Make sure you've run the migrations and seed file:
```sql
-- Run migrations
source C:/xampp/htdocs/biziverse-clone/migrations/001_core_tables.sql
source C:/xampp/htdocs/biziverse-clone/migrations/002_accounts_tasks_audit.sql

-- Run seeder
php C:/xampp/htdocs/biziverse-clone/seed.php
```

### Step 2: Access Inventory
1. Login to the system: `http://localhost/biziverse-clone/login.php`
   - Email: `admin@biziverse.com`
   - Password: `admin123`

2. Click "Inventory" in the sidebar (under ERP section)
   - Or go directly to: `http://localhost/biziverse-clone/inventory.php`

---

## Test Cases

### Test 1: View Inventory List ✅
**Expected:** See list of seeded items
- Fire Extinguisher 5KG
- Safety Helmet
- Fire Alarm System
- Safety Shoes
- Fire Hose Pipe

**Check:**
- ✅ Items display in table
- ✅ SKU codes visible
- ✅ Categories shown
- ✅ Quantities displayed
- ✅ Values calculated

---

### Test 2: Add New Item ✅

**Steps:**
1. Click **"Add Item"** button (top right, blue)
2. Fill in the form:
   ```
   Name: Fire Extinguisher 2KG
   Code: SKU006
   Category: Safety Equipment
   Unit: PCS
   Std Cost: 450.00
   Sale Price: 750.00
   Min Stock: 20
   GST: 18
   ```
3. Click **"Save"** (green button)

**Expected:**
- ✅ Success message appears
- ✅ Page reloads
- ✅ New item appears in list
- ✅ Audit log created in database

**Verify in Database:**
```sql
SELECT * FROM items WHERE sku = 'SKU006';
SELECT * FROM audit_logs WHERE table_name = 'items' AND action = 'create' ORDER BY created_at DESC LIMIT 1;
```

---

### Test 3: Edit Existing Item ✅

**Steps:**
1. Find any item in the list
2. Click **Edit** button (blue pencil icon)
3. Modal opens with item data pre-filled
4. Change the name: Add " - Updated" to the end
5. Click **"Save"**

**Expected:**
- ✅ Form loads with existing data
- ✅ Success message after save
- ✅ Updated name shows in list
- ✅ Audit log records change

**Verify in Database:**
```sql
SELECT * FROM audit_logs WHERE table_name = 'items' AND action = 'update' ORDER BY created_at DESC LIMIT 1;
```

---

### Test 4: Stock IN (Receive Stock) ✅

**Steps:**
1. Click **"In / Receive"** button (green, top right)
2. Select **"Receive from Production"**
3. In the modal:
   - Select Store: **"Main Warehouse"**
   - Wait for items to load
4. Check an item (e.g., Fire Extinguisher)
5. Enter quantity: **25**
6. Click **"Select"**

**Expected:**
- ✅ Store dropdown shows locations
- ✅ Items load after selecting store
- ✅ Can check multiple items
- ✅ Success message appears
- ✅ Stock quantity increases

**Verify in Database:**
```sql
-- Check stock movement
SELECT * FROM stock_movements WHERE type = 'in' ORDER BY created_at DESC LIMIT 1;

-- Check updated stock valuation
SELECT i.name, sv.qty_on_hand, sv.total_value, l.name as location
FROM stock_valuations sv
JOIN items i ON sv.item_id = i.id
JOIN locations l ON sv.location_id = l.id
ORDER BY sv.last_updated DESC;
```

---

### Test 5: Stock OUT (Issue Stock) ✅

**Steps:**
1. Click **"Out / Issue"** button (orange, top right)
2. Select **"Issue for Production"**
3. In the modal:
   - Select Store: **"Main Warehouse"**
   - Wait for items to load
4. Check an item that has stock
5. Enter quantity: **5** (less than available)
6. Click **"Select"**

**Expected:**
- ✅ Items with stock show available quantity
- ✅ System validates sufficient stock
- ✅ Success message appears
- ✅ Stock quantity decreases

**Test Negative Stock Prevention:**
1. Try to issue more than available stock
2. Should show error: "Insufficient stock available"

**Verify in Database:**
```sql
-- Check stock movement
SELECT * FROM stock_movements WHERE type = 'out' ORDER BY created_at DESC LIMIT 1;

-- Verify stock reduced
SELECT i.name, sv.qty_on_hand 
FROM stock_valuations sv
JOIN items i ON sv.item_id = i.id
ORDER BY sv.last_updated DESC;
```

---

### Test 6: Filters ✅

**Test Location Filter:**
1. Select "Main Warehouse" from Location dropdown
2. Only items in that location should show

**Test Category Filter:**
1. Select a category from Category dropdown
2. Only items in that category should show

**Test Search:**
1. Type "Fire" in search box
2. Only items with "Fire" in name should show

**Test Type Tabs:**
1. Click "Products" tab
2. Click "Materials" tab
3. Click "Spares" tab
4. Each should filter accordingly (if items are tagged with types)

---

### Test 7: Export CSV ✅

**Steps:**
1. Click **"Export CSV"** button (blue, top right)

**Expected:**
- ✅ CSV file downloads
- ✅ Filename: `inventory_2024-11-03.csv` (today's date)
- ✅ Contains all columns: SKU, Name, Category, Unit, Qty, Rate, Value, Reorder Level
- ✅ All items included

**Open CSV:**
- Should open in Excel/Spreadsheet
- Data should be properly formatted
- Currency symbols may need adjustment

---

### Test 8: Delete Item ✅

**Steps:**
1. Find an item in the list
2. Click **Delete** button (red trash icon)
3. Confirm deletion in popup

**Expected:**
- ✅ Confirmation dialog appears
- ✅ Success message after delete
- ✅ Item disappears from list (soft deleted)
- ✅ Audit log created

**Verify in Database:**
```sql
-- Item should be soft deleted (is_active = 0)
SELECT id, sku, name, is_active FROM items WHERE is_active = 0;

-- Check audit log
SELECT * FROM audit_logs WHERE table_name = 'items' AND action = 'delete' ORDER BY created_at DESC LIMIT 1;
```

---

### Test 9: View Item Movement History ✅

**Steps:**
1. Find an item that has stock movements
2. Click **View** button (eye icon)

**Expected:**
- ✅ Modal/alert shows movement history
- ✅ Displays date, type (IN/OUT), from/to locations
- ✅ Shows quantity moved
- ✅ Shows user who performed action

---

### Test 10: DataTable Features ✅

**Test Sorting:**
1. Click on "Item" column header
2. Should sort alphabetically (A-Z, then Z-A)
3. Try other columns

**Test Pagination:**
1. If more than 25 items, pagination should show
2. Click "Next" button
3. Should show next set of items

**Test Search:**
1. Use the search box on the right
2. Type partial item name
3. Table should filter instantly

---

## Common Issues & Solutions

### Issue 1: Items Not Loading
**Solution:**
```sql
-- Check if items exist
SELECT COUNT(*) FROM items WHERE is_active = 1;

-- If 0, run seed.php again
php C:/xampp/htdocs/biziverse-clone/seed.php
```

### Issue 2: "Table not found" Error
**Solution:**
```sql
-- Verify tables exist
SHOW TABLES LIKE 'items';
SHOW TABLES LIKE 'stock_movements';
SHOW TABLES LIKE 'stock_valuations';

-- If missing, run migrations
source C:/xampp/htdocs/biziverse-clone/migrations/001_core_tables.sql
```

### Issue 3: Modal Not Opening
**Solution:**
- Check browser console for JavaScript errors (F12)
- Verify jQuery and Bootstrap JS are loading
- Clear browser cache (Ctrl+Shift+Delete)

### Issue 4: CSRF Token Error
**Solution:**
- Logout and login again
- Session may have expired
- Check that CSRF token is in form:
```javascript
console.log($('input[name="csrf_token"]').val());
```

### Issue 5: Stock Not Updating
**Solution:**
```sql
-- Check if stock_valuations table has data
SELECT * FROM stock_valuations;

-- If empty, add initial stock:
INSERT INTO stock_valuations (item_id, location_id, qty_on_hand, total_value)
SELECT id, 1, 0, 0 FROM items WHERE is_active = 1;
```

---

## Performance Testing

### Test with Large Dataset

**Add 100 Test Items:**
```sql
DELIMITER $$
CREATE PROCEDURE add_test_items()
BEGIN
    DECLARE i INT DEFAULT 1;
    WHILE i <= 100 DO
        INSERT INTO items (sku, name, category_id, unit, standard_cost, retail_price, created_by)
        VALUES (
            CONCAT('TEST-', LPAD(i, 4, '0')),
            CONCAT('Test Item ', i),
            1,
            'PCS',
            ROUND(RAND() * 1000, 2),
            ROUND(RAND() * 2000, 2),
            1
        );
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL add_test_items();
```

**Expected:**
- ✅ DataTable handles 100+ items smoothly
- ✅ Pagination works correctly
- ✅ Search is fast
- ✅ Sorting works properly

---

## Browser Compatibility

Test in multiple browsers:
- ✅ Chrome/Edge (recommended)
- ✅ Firefox
- ✅ Safari (Mac)
- ⚠️ IE11 (may have issues)

---

## Mobile Responsiveness

Test on mobile device or responsive mode (F12 → Toggle Device Toolbar):
- ✅ Table should be scrollable horizontally
- ✅ Buttons stack vertically on small screens
- ✅ Modals are mobile-friendly
- ✅ Touch interactions work

---

## Security Testing

### Test 1: CSRF Protection
Try to submit form without CSRF token - should fail

### Test 2: SQL Injection
Try entering `' OR '1'='1` in search - should be sanitized

### Test 3: Negative Stock
Try to issue more stock than available - should prevent

### Test 4: Unauthorized Access
Logout and try to access `inventory.php` directly - should redirect to login

---

## All Tests Pass? ✅

If all tests pass, your Inventory module is working perfectly!

**Next Steps:**
1. Add more items through the interface
2. Test with real business data
3. Train users on the system
4. Move to next module (Suppliers or Purchase Orders)

---

**Need Help?**
- Check browser console (F12) for errors
- Check PHP error log: `C:\xampp\php\logs\php_error_log`
- Review `audit_logs` table for system activity
- Check `INVENTORY_MODULE.md` for feature documentation
