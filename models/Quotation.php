<?php
/**
 * Quotation Model
 */

class Quotation {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all quotations with filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT q.*,
                    u.name as issued_by_name,
                    c.name as created_by_name,
                    DATEDIFF(CURDATE(), q.quotation_date) as days_old
                    FROM quotations q
                    LEFT JOIN users u ON q.issued_by = u.id
                    LEFT JOIN users c ON q.created_by = c.id
                    WHERE 1=1";
            
            $params = [];
            
            // Filter by type
            if (!empty($filters['type'])) {
                $sql .= " AND q.type = ?";
                $params[] = $filters['type'];
            }
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND q.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by month
            if (!empty($filters['month'])) {
                $sql .= " AND DATE_FORMAT(q.quotation_date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            // Filter by branch
            if (!empty($filters['branch'])) {
                $sql .= " AND q.branch_id = ?";
                $params[] = $filters['branch'];
            }
            
            // Filter by executive
            if (!empty($filters['executive'])) {
                $sql .= " AND q.executive_id = ?";
                $params[] = $filters['executive'];
            }
            
            // Search
            if (!empty($filters['search'])) {
                $sql .= " AND (q.quote_no LIKE ? OR q.customer_name LIKE ? OR q.reference LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY q.quotation_date DESC, q.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching quotations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get quotation by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM quotations WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get quotation items
     */
    public function getItems($quotationId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_no ASC");
            $stmt->execute([$quotationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotation items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get quotation terms
     */
    public function getTerms($quotationId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM quotation_terms WHERE quotation_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$quotationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotation terms: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate next quote number
     */
    public function generateQuoteNo() {
        try {
            $stmt = $this->db->query("SELECT MAX(CAST(quote_no AS UNSIGNED)) as max_no FROM quotations");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNo = ($result['max_no'] ?? 1000) + 1;
            return (string)$nextNo;
        } catch (PDOException $e) {
            error_log("Error generating quote number: " . $e->getMessage());
            return date('Ymd') . '001';
        }
    }
    
    /**
     * Create quotation
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Insert quotation
            $sql = "INSERT INTO quotations (
                        quote_no, reference, customer_id, customer_name, contact_person, 
                        address, copy_from, branch_id, branch_name, sales_credit, shipping_address, 
                        same_as_billing, quotation_date, valid_till, issued_by, issued_by_name,
                        executive_id, executive_name, type, status, subtotal, discount_amount,
                        tax_amount, extra_charges, total_amount, notes, terms_conditions,
                        bank_details, upload_file, save_as_template, share_by_email,
                        share_by_whatsapp, print_after_saving, alert_on_opening, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['quote_no'],
                $data['reference'] ?? null,
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['contact_person'] ?? null,
                $data['address'] ?? null,
                $data['copy_from'] ?? null,
                $data['branch_id'] ?? null,
                $data['branch_name'] ?? null,
                $data['sales_credit'] ?? 'None',
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 1,
                $data['quotation_date'],
                $data['valid_till'],
                $data['issued_by'] ?? $_SESSION['user_id'],
                $data['issued_by_name'] ?? null,
                $data['executive_id'] ?? null,
                $data['executive_name'] ?? null,
                $data['type'] ?? 'quotation',
                $data['status'] ?? 'draft',
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['notes'] ?? null,
                $data['terms_conditions'] ?? null,
                $data['bank_details'] ?? null,
                $data['upload_file'] ?? null,
                $data['save_as_template'] ?? 0,
                $data['share_by_email'] ?? 0,
                $data['share_by_whatsapp'] ?? 0,
                $data['print_after_saving'] ?? 0,
                $data['alert_on_opening'] ?? 0,
                $_SESSION['user_id']
            ]);
            
            $quotationId = $this->db->lastInsertId();
            
            // Insert items
            if (!empty($data['items'])) {
                $this->saveItems($quotationId, $data['items']);
            }
            
            // Insert terms
            if (!empty($data['terms'])) {
                $this->saveTerms($quotationId, $data['terms']);
            }
            
            $this->db->commit();
            return $quotationId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating quotation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update quotation
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE quotations SET
                        reference = ?, customer_id = ?, customer_name = ?, contact_person = ?,
                        address = ?, copy_from = ?, branch_id = ?, branch_name = ?, 
                        sales_credit = ?, shipping_address = ?, same_as_billing = ?,
                        quotation_date = ?, valid_till = ?, type = ?, status = ?, 
                        subtotal = ?, discount_amount = ?, tax_amount = ?, extra_charges = ?,
                        total_amount = ?, notes = ?, terms_conditions = ?, bank_details = ?,
                        save_as_template = ?, share_by_email = ?, share_by_whatsapp = ?,
                        print_after_saving = ?, alert_on_opening = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['reference'] ?? null,
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['contact_person'] ?? null,
                $data['address'] ?? null,
                $data['copy_from'] ?? null,
                $data['branch_id'] ?? null,
                $data['branch_name'] ?? null,
                $data['sales_credit'] ?? 'None',
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 1,
                $data['quotation_date'],
                $data['valid_till'],
                $data['type'] ?? 'quotation',
                $data['status'] ?? 'draft',
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['notes'] ?? null,
                $data['terms_conditions'] ?? null,
                $data['bank_details'] ?? null,
                $data['save_as_template'] ?? 0,
                $data['share_by_email'] ?? 0,
                $data['share_by_whatsapp'] ?? 0,
                $data['print_after_saving'] ?? 0,
                $data['alert_on_opening'] ?? 0,
                $id
            ]);
            
            // Delete existing items and insert new ones
            if (isset($data['items'])) {
                $this->db->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
                $this->saveItems($id, $data['items']);
            }
            
            // Delete existing terms and insert new ones
            if (isset($data['terms'])) {
                $this->db->prepare("DELETE FROM quotation_terms WHERE quotation_id = ?")->execute([$id]);
                $this->saveTerms($id, $data['terms']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating quotation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save quotation items
     */
    private function saveItems($quotationId, $items) {
        $sql = "INSERT INTO quotation_items (
                    quotation_id, item_no, image, item_description, hsn_sac,
                    quantity, unit, rate, discount_percent, discount_amount,
                    taxable_amount, cgst_percent, cgst_amount, sgst_percent,
                    sgst_amount, igst_percent, igst_amount, amount, lead_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $index => $item) {
            $stmt->execute([
                $quotationId,
                $index + 1,
                $item['image'] ?? null,
                $item['item_description'],
                $item['hsn_sac'] ?? null,
                $item['quantity'] ?? 1,
                $item['unit'] ?? 'Nos',
                $item['rate'] ?? 0,
                $item['discount_percent'] ?? 0,
                $item['discount_amount'] ?? 0,
                $item['taxable_amount'] ?? 0,
                $item['cgst_percent'] ?? 0,
                $item['cgst_amount'] ?? 0,
                $item['sgst_percent'] ?? 0,
                $item['sgst_amount'] ?? 0,
                $item['igst_percent'] ?? 0,
                $item['igst_amount'] ?? 0,
                $item['amount'] ?? 0,
                $item['lead_time'] ?? null
            ]);
        }
    }
    
    /**
     * Save quotation terms
     */
    private function saveTerms($quotationId, $terms) {
        $sql = "INSERT INTO quotation_terms (quotation_id, term_condition, sort_order) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($terms as $index => $term) {
            $stmt->execute([$quotationId, $term, $index + 1]);
        }
    }
    
    /**
     * Delete quotation
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM quotations WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting quotation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get totals summary
     */
    public function getTotals($filters = []) {
        try {
            $sql = "SELECT 
                        COUNT(*) as count,
                        SUM(total_amount) as total,
                        SUM(total_amount - tax_amount) as pre_tax
                    FROM quotations WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['month'])) {
                $sql .= " AND DATE_FORMAT(quotation_date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching totals: " . $e->getMessage());
            return ['count' => 0, 'total' => 0, 'pre_tax' => 0];
        }
    }
}
