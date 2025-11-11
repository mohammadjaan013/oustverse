<?php
/**
 * Invoice Model
 */

class Invoice {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all invoices with filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT i.*,
                    COALESCE(i.total_amount - i.paid_amount, i.total_amount) as pending
                    FROM invoices i
                    WHERE 1=1";
            
            $params = [];
            
            // Filter by invoice type
            if (!empty($filters['type'])) {
                $sql .= " AND i.invoice_type = ?";
                $params[] = $filters['type'];
            }
            
            // Filter by payment status
            if (!empty($filters['status'])) {
                $sql .= " AND i.payment_status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by month
            if (!empty($filters['month'])) {
                $sql .= " AND DATE_FORMAT(i.invoice_date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            // Filter by date range
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $sql .= " AND i.invoice_date BETWEEN ? AND ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }
            
            // Filter by executive
            if (!empty($filters['executive'])) {
                $sql .= " AND i.created_by = ?";
                $params[] = $filters['executive'];
            }
            
            // Search
            if (!empty($filters['search'])) {
                $sql .= " AND (i.invoice_no LIKE ? OR i.customer_name LIKE ? OR i.reference LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY i.invoice_date DESC, i.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching invoices: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get invoice by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching invoice: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get invoice items
     */
    public function getItems($invoiceId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order");
            $stmt->execute([$invoiceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching invoice items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get invoice terms
     */
    public function getTerms($invoiceId) {
        try {
            $stmt = $this->db->prepare("SELECT term_condition FROM invoice_terms WHERE invoice_id = ? ORDER BY sort_order");
            $stmt->execute([$invoiceId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'term_condition');
        } catch (PDOException $e) {
            error_log("Error fetching invoice terms: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate next invoice number
     */
    public function generateInvoiceNo() {
        try {
            $stmt = $this->db->query("SELECT invoice_no FROM invoices ORDER BY id DESC LIMIT 1");
            $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastInvoice) {
                // Extract number from INV-001 format
                preg_match('/INV-(\d+)/', $lastInvoice['invoice_no'], $matches);
                $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            } else {
                $nextNum = 1;
            }
            
            return 'INV-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            error_log("Error generating invoice number: " . $e->getMessage());
            return 'INV-001';
        }
    }
    
    /**
     * Create invoice
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO invoices (
                invoice_no, reference, invoice_type, customer_id, customer_name,
                contact_person, branch_id, sales_credit, billing_address, shipping_address,
                same_as_billing, shipping_details, invoice_date, due_date,
                subtotal, discount_amount, tax_amount, extra_charges, total_amount,
                taxable_amount, payment_status, pending_amount, recovery_amount,
                bank_details, notes, internal_notes, is_template, template_name,
                share_by_email, share_by_whatsapp, print_after_saving, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['invoice_no'],
                $data['reference'] ?? null,
                $data['invoice_type'] ?? 'party_invoice',
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['contact_person'] ?? null,
                $data['branch_id'] ?? null,
                $data['sales_credit'] ?? 'None',
                $data['billing_address'] ?? null,
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 0,
                $data['shipping_details'] ?? null,
                $data['invoice_date'],
                $data['due_date'] ?? null,
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'],
                $data['taxable_amount'] ?? 0,
                $data['payment_status'] ?? 'unpaid',
                $data['pending_amount'] ?? $data['total_amount'],
                $data['recovery_amount'] ?? 0,
                $data['bank_details'] ?? null,
                $data['notes'] ?? null,
                $data['internal_notes'] ?? null,
                $data['is_template'] ?? 0,
                $data['template_name'] ?? null,
                $data['share_by_email'] ?? 0,
                $data['share_by_whatsapp'] ?? 0,
                $data['print_after_saving'] ?? 0,
                $_SESSION['user_id'] ?? null
            ]);
            
            $invoiceId = $this->db->lastInsertId();
            
            // Save items
            if (!empty($data['items'])) {
                $this->saveItems($invoiceId, $data['items']);
            }
            
            // Save terms
            if (!empty($data['terms'])) {
                $this->saveTerms($invoiceId, $data['terms']);
            }
            
            $this->db->commit();
            return $invoiceId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating invoice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update invoice
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE invoices SET
                reference = ?, invoice_type = ?, customer_id = ?, customer_name = ?,
                contact_person = ?, branch_id = ?, sales_credit = ?, billing_address = ?,
                shipping_address = ?, same_as_billing = ?, shipping_details = ?,
                invoice_date = ?, due_date = ?, subtotal = ?, discount_amount = ?,
                tax_amount = ?, extra_charges = ?, total_amount = ?, taxable_amount = ?,
                payment_status = ?, pending_amount = ?, recovery_amount = ?,
                bank_details = ?, notes = ?, internal_notes = ?, is_template = ?,
                template_name = ?, share_by_email = ?, share_by_whatsapp = ?, print_after_saving = ?
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['reference'] ?? null,
                $data['invoice_type'] ?? 'party_invoice',
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['contact_person'] ?? null,
                $data['branch_id'] ?? null,
                $data['sales_credit'] ?? 'None',
                $data['billing_address'] ?? null,
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 0,
                $data['shipping_details'] ?? null,
                $data['invoice_date'],
                $data['due_date'] ?? null,
                $data['subtotal'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['extra_charges'] ?? 0,
                $data['total_amount'],
                $data['taxable_amount'] ?? 0,
                $data['payment_status'] ?? 'unpaid',
                $data['pending_amount'] ?? $data['total_amount'],
                $data['recovery_amount'] ?? 0,
                $data['bank_details'] ?? null,
                $data['notes'] ?? null,
                $data['internal_notes'] ?? null,
                $data['is_template'] ?? 0,
                $data['template_name'] ?? null,
                $data['share_by_email'] ?? 0,
                $data['share_by_whatsapp'] ?? 0,
                $data['print_after_saving'] ?? 0,
                $id
            ]);
            
            // Delete existing items and terms
            $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM invoice_terms WHERE invoice_id = ?")->execute([$id]);
            
            // Save new items and terms
            if (!empty($data['items'])) {
                $this->saveItems($id, $data['items']);
            }
            
            if (!empty($data['terms'])) {
                $this->saveTerms($id, $data['terms']);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating invoice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save invoice items
     */
    private function saveItems($invoiceId, $items) {
        $sql = "INSERT INTO invoice_items (
            invoice_id, item_description, hsn_sac, quantity, unit, rate,
            discount_percent, discount_amount, taxable_amount, cgst_percent,
            cgst_amount, sgst_percent, sgst_amount, amount, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $index => $item) {
            if (empty($item['description'])) continue;
            
            $quantity = floatval($item['quantity'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $discountPercent = floatval($item['discount_percent'] ?? 0);
            $cgstPercent = floatval($item['cgst_percent'] ?? 0);
            $sgstPercent = floatval($item['sgst_percent'] ?? 0);
            
            // Calculate amounts
            $baseAmount = $quantity * $rate;
            $discountAmount = ($baseAmount * $discountPercent) / 100;
            $taxableAmount = $baseAmount - $discountAmount;
            $cgstAmount = ($taxableAmount * $cgstPercent) / 100;
            $sgstAmount = ($taxableAmount * $sgstPercent) / 100;
            $amount = $taxableAmount + $cgstAmount + $sgstAmount;
            
            $stmt->execute([
                $invoiceId,
                $item['description'],
                $item['hsn_sac'] ?? null,
                $quantity,
                $item['unit'] ?? 'nos',
                $rate,
                $discountPercent,
                $discountAmount,
                $taxableAmount,
                $cgstPercent,
                $cgstAmount,
                $sgstPercent,
                $sgstAmount,
                $amount,
                $index + 1
            ]);
        }
    }
    
    /**
     * Save invoice terms
     */
    private function saveTerms($invoiceId, $terms) {
        $sql = "INSERT INTO invoice_terms (invoice_id, term_condition, sort_order) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($terms as $index => $term) {
            if (empty($term)) continue;
            $stmt->execute([$invoiceId, $term, $index + 1]);
        }
    }
    
    /**
     * Delete invoice
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM invoices WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting invoice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get summary totals
     */
    public function getSummaryTotals($filters = []) {
        try {
            $sql = "SELECT 
                        COUNT(*) as count,
                        SUM(taxable_amount) as pre_tax_total,
                        SUM(total_amount) as total,
                        SUM(pending_amount) as pending
                    FROM invoices WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['type'])) {
                $sql .= " AND invoice_type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND payment_status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['month'])) {
                $sql .= " AND DATE_FORMAT(invoice_date, '%Y-%m') = ?";
                $params[] = $filters['month'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching summary totals: " . $e->getMessage());
            return ['count' => 0, 'pre_tax_total' => 0, 'total' => 0, 'pending' => 0];
        }
    }
}
