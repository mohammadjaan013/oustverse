<?php
/**
 * Order Model
 */

class Order {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all orders with filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT o.*,
                    COALESCE(SUM(oi.quantity), 0) as total_qty,
                    COALESCE(SUM(oi.quantity_pending), 0) as total_pending,
                    COALESCE(SUM(oi.quantity_done), 0) as total_done
                    FROM orders o
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE 1=1";
            
            $params = [];
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND o.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by commitment
            if (!empty($filters['commitment'])) {
                $sql .= " AND o.commitment_status = ?";
                $params[] = $filters['commitment'];
            }
            
            // Filter by order type
            if (!empty($filters['order_type'])) {
                $sql .= " AND o.order_type = ?";
                $params[] = $filters['order_type'];
            }
            
            // Search
            if (!empty($filters['search'])) {
                $sql .= " AND (o.order_no LIKE ? OR o.customer_name LIKE ? OR o.customer_po_no LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " GROUP BY o.id ORDER BY o.order_date DESC, o.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching orders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order items
     */
    public function getItems($orderId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY item_no ASC");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order terms
     */
    public function getTerms($orderId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM order_terms WHERE order_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order terms: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate next order number
     */
    public function generateOrderNo() {
        try {
            $stmt = $this->db->query("SELECT MAX(CAST(order_no AS UNSIGNED)) as max_no FROM orders");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNo = ($result['max_no'] ?? 1000) + 1;
            return (string)$nextNo;
        } catch (PDOException $e) {
            error_log("Error generating order number: " . $e->getMessage());
            return date('Ymd') . '001';
        }
    }
    
    /**
     * Create order
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO orders (
                        order_no, reference, customer_id, customer_name, customer_po_no,
                        contact_person, branch_id, branch_name, sales_credit,
                        billing_address, shipping_address, same_as_billing, order_date,
                        due_date, executive_id, executive_name, responsible_id,
                        responsible_name, order_type, status, commitment_status,
                        subtotal, discount_amount, tax_amount, extra_charges,
                        total_amount, notes, terms_conditions, bank_details,
                        update_by_email, update_by_whatsapp, print_after_saving, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['order_no'],
                $data['reference'] ?? null,
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['customer_po_no'] ?? null,
                $data['contact_person'] ?? null,
                $data['branch_id'] ?? null,
                $data['branch_name'] ?? null,
                $data['sales_credit'] ?? 'None',
                $data['billing_address'] ?? null,
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 1,
                $data['order_date'],
                $data['due_date'] ?? null,
                $data['executive_id'] ?? null,
                $data['executive_name'] ?? null,
                $data['responsible_id'] ?? null,
                $data['responsible_name'] ?? null,
                $data['order_type'] ?? 'sales',
                $data['status'] ?? 'pending',
                $this->calculateCommitmentStatus($data['due_date'] ?? null),
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['notes'] ?? null,
                $data['terms_conditions'] ?? null,
                $data['bank_details'] ?? null,
                $data['update_by_email'] ?? 0,
                $data['update_by_whatsapp'] ?? 0,
                $data['print_after_saving'] ?? 0,
                $_SESSION['user_id']
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Insert items
            if (!empty($data['items'])) {
                $this->saveItems($orderId, $data['items']);
            }
            
            // Insert terms
            if (!empty($data['terms'])) {
                $this->saveTerms($orderId, $data['terms']);
            }
            
            $this->db->commit();
            return $orderId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update order
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE orders SET
                        customer_name = ?, contact_person = ?, customer_po_no = ?,
                        billing_address = ?, shipping_address = ?, order_date = ?,
                        due_date = ?, status = ?, commitment_status = ?,
                        subtotal = ?, discount_amount = ?, tax_amount = ?,
                        extra_charges = ?, total_amount = ?, notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_name'],
                $data['contact_person'] ?? null,
                $data['customer_po_no'] ?? null,
                $data['billing_address'] ?? null,
                $data['shipping_address'] ?? null,
                $data['order_date'],
                $data['due_date'] ?? null,
                $data['status'] ?? 'pending',
                $this->calculateCommitmentStatus($data['due_date'] ?? null),
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['notes'] ?? null,
                $id
            ]);
            
            // Update items if provided
            if (isset($data['items'])) {
                $this->db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
                $this->saveItems($id, $data['items']);
            }
            
            // Update terms if provided
            if (isset($data['terms'])) {
                $this->db->prepare("DELETE FROM order_terms WHERE order_id = ?")->execute([$id]);
                $this->saveTerms($id, $data['terms']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save order items
     */
    private function saveItems($orderId, $items) {
        $sql = "INSERT INTO order_items (
                    order_id, item_no, item_description, hsn_sac, quantity,
                    quantity_pending, quantity_done, unit, rate, discount_percent,
                    discount_amount, taxable_amount, cgst_percent, cgst_amount,
                    sgst_percent, sgst_amount, amount, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $index => $item) {
            $stmt->execute([
                $orderId,
                $index + 1,
                $item['item_description'],
                $item['hsn_sac'] ?? null,
                $item['quantity'] ?? 1,
                $item['quantity_pending'] ?? ($item['quantity'] ?? 1),
                $item['quantity_done'] ?? 0,
                $item['unit'] ?? 'no.s',
                $item['rate'] ?? 0,
                $item['discount_percent'] ?? 0,
                $item['discount_amount'] ?? 0,
                $item['taxable_amount'] ?? 0,
                $item['cgst_percent'] ?? 0,
                $item['cgst_amount'] ?? 0,
                $item['sgst_percent'] ?? 0,
                $item['sgst_amount'] ?? 0,
                $item['amount'] ?? 0,
                $item['notes'] ?? null
            ]);
        }
    }
    
    /**
     * Save order terms
     */
    private function saveTerms($orderId, $terms) {
        $sql = "INSERT INTO order_terms (order_id, term_condition, sort_order) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($terms as $index => $term) {
            $stmt->execute([$orderId, $term, $index + 1]);
        }
    }
    
    /**
     * Calculate commitment status based on due date
     */
    private function calculateCommitmentStatus($dueDate) {
        if (!$dueDate) return 'future';
        
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        if ($dueDate < $today) return 'overdue';
        if ($dueDate == $today) return 'today';
        if ($dueDate == $tomorrow) return 'tomorrow';
        
        return 'future';
    }
    
    /**
     * Delete order
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM orders WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get commitment counts
     */
    public function getCommitmentCounts() {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN commitment_status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                        SUM(CASE WHEN commitment_status = 'today' THEN 1 ELSE 0 END) as today,
                        SUM(CASE WHEN commitment_status = 'tomorrow' THEN 1 ELSE 0 END) as tomorrow,
                        COUNT(*) as total
                    FROM orders WHERE status != 'cancelled'";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching commitment counts: " . $e->getMessage());
            return ['overdue' => 0, 'today' => 0, 'tomorrow' => 0, 'total' => 0];
        }
    }
}
