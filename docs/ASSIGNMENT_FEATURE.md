# Stock Movement Assignment Feature

## Overview
This feature allows you to assign stock IN/OUT operations to specific users, enabling better task management and accountability in inventory operations.

## What Has Been Added

### 1. Database Changes
**File:** `database/migrations/005_add_assigned_to_stock_movements.sql`

Added three new columns to `stock_movements` table:
- `assigned_to` (INT) - Foreign key to users table
- `assignment_notes` (TEXT) - Special instructions for the assigned person
- `assignment_status` (ENUM) - Status tracking: pending, in_progress, completed, cancelled

### 2. UI Changes
**File:** `inventory.php`

Enhanced the "Select Items" modal with:
- **Assign To** dropdown - Shows all active users with their roles
- **Assignment Notes** textarea - For special instructions
- Better visual design with orange header
- Improved layout and spacing

### 3. JavaScript Changes
**File:** `assets/js/inventory.js`

Modified `processStockMovement()` function to:
- Capture assigned person selection
- Capture assignment notes
- Send these values to the server
- Show success/fail counts
- Better error handling

### 4. Controller Changes
**File:** `controllers/InventoryController.php`

Updated both `stockIn()` and `stockOut()` methods to:
- Accept `assigned_to` and `assignment_notes` parameters
- Set `assignment_status` to "pending" when assigned, "completed" when not assigned
- Enhanced success message to indicate assignment

### 5. Model Changes
**File:** `models/Inventory.php`

Updated `addStockMovement()` method to:
- Handle the three new fields
- Store assignment data in database

## How to Use

1. **Run the Migration:**
   ```sql
   source database/migrations/005_add_assigned_to_stock_movements.sql
   ```

2. **Create Stock Movement:**
   - Click "In / Receive" or "Out / Issue" button
   - Select operation type (e.g., "Purchase Inward", "Dispatch")
   - Select Store
   - **Optional:** Choose person to assign from dropdown
   - **Optional:** Add notes for the assigned person
   - Select items and quantities
   - Click "Select" to process

3. **Assignment:**
   - If you select a person, the movement status is set to "pending"
   - If no person is selected, status is "completed" (immediate action)

## Future Enhancements

### Phase 1: Notifications & Dashboard (Recommended Next)
1. **My Assignments Widget** on dashboard
   - Show pending assignments for logged-in user
   - Count of pending/in-progress tasks
   - Quick status update buttons

2. **Email Notifications**
   - Send email when task is assigned
   - Daily summary of pending tasks
   - Overdue task reminders

3. **Assignment Details Page**
   - View full details of assigned movement
   - Update status (pending → in-progress → completed)
   - Add comments/updates
   - Upload photos/documents

### Phase 2: Advanced Features
1. **Assignment Workflow**
   - Multi-level approval
   - Reassignment capability
   - Deadline tracking
   - Escalation rules

2. **Mobile Notifications**
   - Push notifications for assignments
   - Mobile app for field workers
   - QR code scanning for items

3. **Analytics & Reports**
   - Assignment completion rate
   - Average completion time
   - User performance metrics
   - Bottleneck identification

### Phase 3: Advanced Tracking
1. **Real-time Updates**
   - WebSocket integration
   - Live status updates
   - Chat/messaging between users

2. **Location Tracking**
   - GPS tracking for field assignments
   - Geofencing alerts
   - Route optimization

3. **Integration**
   - WhatsApp notifications
   - Slack integration
   - SMS alerts for urgent tasks

## Sample Queries for Reports

### 1. Get My Pending Assignments
```sql
SELECT sm.*, i.name as item_name, i.sku, 
       l.name as location_name,
       u.name as created_by_name
FROM stock_movements sm
JOIN items i ON sm.item_id = i.id
LEFT JOIN locations l ON sm.location_from = l.id OR sm.location_to = l.id
JOIN users u ON sm.created_by = u.id
WHERE sm.assigned_to = :user_id
AND sm.assignment_status = 'pending'
ORDER BY sm.created_at DESC;
```

### 2. Get Assignment Statistics
```sql
SELECT 
    u.name as user_name,
    COUNT(*) as total_assignments,
    SUM(CASE WHEN assignment_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN assignment_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN assignment_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
FROM stock_movements sm
JOIN users u ON sm.assigned_to = u.id
WHERE sm.assigned_to IS NOT NULL
GROUP BY u.id, u.name;
```

### 3. Get Overdue Assignments (if you add due_date field)
```sql
SELECT sm.*, i.name as item_name, u.name as assigned_to_name
FROM stock_movements sm
JOIN items i ON sm.item_id = i.id
JOIN users u ON sm.assigned_to = u.id
WHERE sm.assignment_status != 'completed'
AND sm.due_date < CURDATE()
ORDER BY sm.due_date ASC;
```

## Recommended Dashboard Widget Code

Create `includes/widgets/my_assignments.php`:

```php
<?php
// Get pending assignments for current user
$userId = $_SESSION['user_id'];
$stmt = getDB()->prepare("
    SELECT sm.*, i.name as item_name, i.sku,
           CASE 
               WHEN sm.location_from IS NOT NULL THEN lf.name
               WHEN sm.location_to IS NOT NULL THEN lt.name
           END as location_name,
           DATEDIFF(CURDATE(), sm.created_at) as days_old
    FROM stock_movements sm
    JOIN items i ON sm.item_id = i.id
    LEFT JOIN locations lf ON sm.location_from = lf.id
    LEFT JOIN locations lt ON sm.location_to = lt.id
    WHERE sm.assigned_to = :user_id
    AND sm.assignment_status IN ('pending', 'in_progress')
    ORDER BY sm.created_at DESC
    LIMIT 5
");
$stmt->execute(['user_id' => $userId]);
$assignments = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header bg-warning">
        <h5 class="mb-0">
            <i class="fas fa-tasks"></i> My Assignments
            <span class="badge bg-dark"><?php echo count($assignments); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($assignments)): ?>
            <p class="text-muted text-center">No pending assignments</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($assignments as $task): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($task['item_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo ucfirst($task['type']); ?> • 
                                    <?php echo $task['qty']; ?> units • 
                                    <?php echo htmlspecialchars($task['location_name']); ?>
                                </small>
                                <?php if ($task['assignment_notes']): ?>
                                    <br>
                                    <small class="text-info">
                                        <i class="fas fa-info-circle"></i> 
                                        <?php echo htmlspecialchars($task['assignment_notes']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $task['assignment_status'] === 'in_progress' ? 'primary' : 'warning'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['assignment_status'])); ?>
                                </span>
                                <br>
                                <small class="text-muted"><?php echo $task['days_old']; ?> days ago</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-center">
        <a href="<?php echo BASE_URL; ?>/my_assignments.php" class="btn btn-sm btn-warning">
            View All Assignments
        </a>
    </div>
</div>
```

## API Endpoint for Status Update

Create `controllers/AssignmentController.php`:

```php
<?php
class AssignmentController {
    
    /**
     * Update assignment status
     */
    public function updateStatus() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request');
        }
        
        $movementId = intval($_POST['movement_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        
        if (!in_array($newStatus, ['pending', 'in_progress', 'completed', 'cancelled'])) {
            jsonResponse(false, 'Invalid status');
        }
        
        try {
            $db = getDB();
            $stmt = $db->prepare("
                UPDATE stock_movements 
                SET assignment_status = :status 
                WHERE id = :id 
                AND assigned_to = :user_id
            ");
            
            $result = $stmt->execute([
                'status' => $newStatus,
                'id' => $movementId,
                'user_id' => $_SESSION['user_id']
            ]);
            
            if ($result) {
                logAudit('update_assignment_status', 'stock_movements', $movementId, null, ['status' => $newStatus]);
                jsonResponse(true, 'Status updated successfully');
            } else {
                jsonResponse(false, 'Failed to update status');
            }
            
        } catch (Exception $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage());
        }
    }
}
```

## Security Considerations

1. **Authorization:** Ensure only assigned user can update their assignment
2. **Validation:** Validate all inputs before database operations
3. **Audit Trail:** Log all status changes for accountability
4. **CSRF Protection:** Already implemented in the system

## Testing Checklist

- [ ] Migration runs successfully
- [ ] Assignment dropdown shows all active users
- [ ] Stock IN with assignment works
- [ ] Stock OUT with assignment works
- [ ] Stock IN without assignment works (no errors)
- [ ] Assignment notes are saved correctly
- [ ] Status is set correctly (pending vs completed)
- [ ] Multiple items can be processed with same assignment
- [ ] Proper error messages show for validation failures
- [ ] Audit logs capture assignment data

## Support

For questions or issues, check:
1. Browser console for JavaScript errors
2. PHP error logs in `error_log` file
3. Database logs for SQL errors
4. Audit logs table for operation history
