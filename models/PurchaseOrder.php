<?php
/**
 * Purchase Order Model
 * Handles all database operations for purchase orders
 */

class PurchaseOrder {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all purchase orders with filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT po.*, s.name as supplier_name, s.contact_name, 
                    u.name as created_by_name, a.name as approved_by_name
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                    LEFT JOIN users u ON po.created_by = u.id
                    LEFT JOIN users a ON po.approved_by = a.id
                    WHERE 1=1";
            
            $params = [];
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND po.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by date range
            if (!empty($filters['date_from'])) {
                $sql .= " AND po.date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND po.date <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Filter by month
            if (!empty($filters['month'])) {
                $sql .= " AND DATE_FORMAT(po.date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            // Filter by supplier
            if (!empty($filters['supplier_id'])) {
                $sql .= " AND po.supplier_id = ?";
                $params[] = $filters['supplier_id'];
            }
            
            // Filter by created by
            if (!empty($filters['created_by'])) {
                $sql .= " AND po.created_by = ?";
                $params[] = $filters['created_by'];
            }
            
            $sql .= " ORDER BY po.date DESC, po.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching purchase orders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get purchase order by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT po.*, s.name as supplier_name, s.contact_name, s.phone, s.email,
                    s.address, s.city, s.state, s.pincode, s.gstin,
                    u.name as created_by_name, a.name as approved_by_name
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                    LEFT JOIN users u ON po.created_by = u.id
                    LEFT JOIN users a ON po.approved_by = a.id
                    WHERE po.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching purchase order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get purchase order by PO number
     */
    public function getByPoNumber($poNumber) {
        try {
            $sql = "SELECT * FROM purchase_orders WHERE po_no = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$poNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching PO by number: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get purchase order items
     */
    public function getItems($poId) {
        try {
            $sql = "SELECT poi.*, p.name as product_name, p.sku, p.unit as item_unit,
                    poi.qty as quantity, 
                    poi.rate as unit_price, 
                    poi.item_id as product_id,
                    poi.total as total_amount,
                    poi.description,
                    COALESCE(poi.tax_amount, 0) as tax_amount,
                    0 as discount_amount,
                    COALESCE(p.unit, 'PCS') as unit
                    FROM purchase_order_items poi
                    LEFT JOIN items p ON poi.item_id = p.id
                    WHERE poi.purchase_order_id = ?
                    ORDER BY poi.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$poId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching PO items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new purchase order
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate PO number if not provided
            if (empty($data['po_no'])) {
                $data['po_no'] = $this->generatePoNumber();
            }
            
            // Insert purchase order
            $sql = "INSERT INTO purchase_orders (
                        po_no, supplier_id, date, expected_delivery_date, status,
                        taxable_amount, tax_amount, total_amount, payment_terms,
                        notes, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['po_no'],
                $data['supplier_id'],
                $data['date'] ?? date('Y-m-d'),
                $data['expected_delivery_date'] ?? null,
                $data['status'] ?? 'draft',
                $data['taxable_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['payment_terms'] ?? null,
                $data['notes'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $poId = $this->db->lastInsertId();
            
            // Insert items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                $this->addItems($poId, $data['items']);
            }
            
            // Log activity
            $this->logActivity($poId, 'created', null, $data['status'] ?? 'draft');
            
            $this->db->commit();
            return $poId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating purchase order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update purchase order
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Get old status for logging
            $oldPo = $this->getById($id);
            
            $sql = "UPDATE purchase_orders SET
                        supplier_id = ?, date = ?, expected_delivery_date = ?,
                        status = ?, taxable_amount = ?, tax_amount = ?, total_amount = ?,
                        payment_terms = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['supplier_id'],
                $data['date'],
                $data['expected_delivery_date'] ?? null,
                $data['status'] ?? 'draft',
                $data['taxable_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['payment_terms'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);
            
            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $this->deleteItems($id);
                // Add new items
                $this->addItems($id, $data['items']);
            }
            
            // Log activity if status changed
            if ($oldPo['status'] != $data['status']) {
                $this->logActivity($id, 'status_changed', $oldPo['status'], $data['status']);
            }
            
            $this->db->commit();
            return $result;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating purchase order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete purchase order
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM purchase_orders WHERE id = ? AND status IN ('draft', 'cancelled')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting purchase order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add items to purchase order
     */
    private function addItems($poId, $items) {
        try {
            $sql = "INSERT INTO purchase_order_items (
                        purchase_order_id, item_id, description, qty, rate,
                        tax_percent, tax_amount, total, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($items as $item) {
                $stmt->execute([
                    $poId,
                    $item['product_id'] ?: null,  // Use null if empty
                    $item['description'] ?? null,
                    $item['quantity'],
                    $item['unit_price'],
                    $item['tax_rate'] ?? 0,
                    $item['tax_amount'] ?? 0,
                    $item['total_amount'],
                    $item['notes'] ?? null
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error adding PO items: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete all items from purchase order
     */
    private function deleteItems($poId) {
        try {
            $sql = "DELETE FROM purchase_order_items WHERE purchase_order_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$poId]);
        } catch (PDOException $e) {
            error_log("Error deleting PO items: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Approve purchase order
     */
    public function approve($id) {
        try {
            $sql = "UPDATE purchase_orders SET
                        status = 'approved',
                        approved_by = ?,
                        approved_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$_SESSION['user_id'] ?? 1, $id]);
            
            if ($result) {
                $this->logActivity($id, 'approved', 'pending', 'approved');
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error approving purchase order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject purchase order
     */
    public function reject($id, $reason = null) {
        try {
            $sql = "UPDATE purchase_orders SET
                        status = 'rejected',
                        notes = CONCAT(COALESCE(notes, ''), '\nRejection reason: ', ?),
                        updated_at = NOW()
                    WHERE id = ? AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$reason ?? 'No reason provided', $id]);
            
            if ($result) {
                $this->logActivity($id, 'rejected', 'pending', 'rejected', $reason);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error rejecting purchase order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique PO number
     */
    public function generatePoNumber() {
        try {
            $year = date('Y');
            $month = date('m');
            
            // Get last PO number for current month
            $sql = "SELECT po_no FROM purchase_orders 
                    WHERE po_no LIKE ? 
                    ORDER BY po_no DESC LIMIT 1";
            
            $prefix = "PO-{$year}{$month}-";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
            $lastPo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastPo) {
                // Extract number and increment
                $lastNumber = intval(substr($lastPo['po_no'], -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating PO number: " . $e->getMessage());
            return 'PO-' . date('YmdHis');
        }
    }
    
    /**
     * Log purchase order activity
     */
    private function logActivity($poId, $action, $oldStatus = null, $newStatus = null, $notes = null) {
        try {
            $sql = "INSERT INTO purchase_order_activity (
                        po_id, user_id, action, old_status, new_status, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $poId,
                $_SESSION['user_id'] ?? null,
                $action,
                $oldStatus,
                $newStatus,
                $notes
            ]);
        } catch (PDOException $e) {
            error_log("Error logging PO activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get purchase order activity log
     */
    public function getActivity($poId) {
        try {
            $sql = "SELECT poa.*, u.name as user_name
                    FROM purchase_order_activity poa
                    LEFT JOIN users u ON poa.user_id = u.id
                    WHERE poa.po_id = ?
                    ORDER BY poa.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$poId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching PO activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics
     */
    public function getStatistics($filters = []) {
        try {
            $stats = [
                'total_orders' => 0,
                'pending' => 0,
                'approved' => 0,
                'received' => 0,
                'total_amount' => 0,
                'pending_amount' => 0
            ];
            
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received,
                        SUM(total_amount) as total_amount,
                        SUM(CASE WHEN status IN ('pending', 'approved', 'sent') THEN total_amount ELSE 0 END) as pending_amount
                    FROM purchase_orders
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND date <= ?";
                $params[] = $filters['date_to'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $stats = array_merge($stats, $result);
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching PO statistics: " . $e->getMessage());
            return $stats;
        }
    }
}
