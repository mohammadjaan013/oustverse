<?php
require_once __DIR__ . '/../includes/db.php';

class SupplierInvoice {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all supplier invoices with optional filters
     */
    public function getAll($filters = []) {
        $query = "SELECT si.*, s.name as supplier_name, s.contact_name, s.mobile, s.phone 
                  FROM supplier_invoices si
                  LEFT JOIN suppliers s ON si.supplier_id = s.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $query .= " AND (si.invoice_no LIKE :search 
                        OR s.name LIKE :search 
                        OR s.contact_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND si.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['invoice_type'])) {
            $query .= " AND si.invoice_type = :invoice_type";
            $params[':invoice_type'] = $filters['invoice_type'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $query .= " AND si.supplier_id = :supplier_id";
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND si.invoice_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND si.invoice_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY si.invoice_date DESC, si.created_at DESC";
        
        if (isset($filters['limit'])) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (isset($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)($filters['offset'] ?? 0), PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total count for pagination
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as total FROM supplier_invoices si
                  LEFT JOIN suppliers s ON si.supplier_id = s.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $query .= " AND (si.invoice_no LIKE :search 
                        OR s.name LIKE :search 
                        OR s.contact_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND si.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['invoice_type'])) {
            $query .= " AND si.invoice_type = :invoice_type";
            $params[':invoice_type'] = $filters['invoice_type'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $query .= " AND si.supplier_id = :supplier_id";
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND si.invoice_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND si.invoice_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
    
    /**
     * Get invoice by ID with all details
     */
    public function getById($id) {
        $query = "SELECT si.*, s.name as supplier_name, s.contact_name, s.mobile, s.phone,
                  s.email, s.address, s.city, s.state, s.country, s.pincode, s.gstin
                  FROM supplier_invoices si
                  LEFT JOIN suppliers s ON si.supplier_id = s.id
                  WHERE si.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get invoice by invoice number
     */
    public function getByInvoiceNo($invoice_no) {
        $query = "SELECT * FROM supplier_invoices WHERE invoice_no = :invoice_no";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_no', $invoice_no);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new supplier invoice
     */
    public function create($data) {
        $query = "INSERT INTO supplier_invoices (
                    invoice_no, supplier_id, invoice_type, invoice_date, due_date, 
                    po_id, reference, status, payment_status, subtotal, tax_amount, 
                    discount_amount, shipping_charges, total_amount, source_branch, 
                    source_address, notes, terms_conditions, created_by
                  ) VALUES (
                    :invoice_no, :supplier_id, :invoice_type, :invoice_date, :due_date, 
                    :po_id, :reference, :status, :payment_status, :subtotal, :tax_amount, 
                    :discount_amount, :shipping_charges, :total_amount, :source_branch, 
                    :source_address, :notes, :terms_conditions, :created_by
                  )";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindValue(':invoice_no', $data['invoice_no']);
        $stmt->bindValue(':supplier_id', $data['supplier_id'], PDO::PARAM_INT);
        $stmt->bindValue(':invoice_type', $data['invoice_type'] ?? 'supplier_invoice');
        $stmt->bindValue(':invoice_date', $data['invoice_date']);
        $stmt->bindValue(':due_date', $data['due_date'] ?? null);
        $stmt->bindValue(':po_id', $data['po_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':reference', $data['reference'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid');
        $stmt->bindValue(':subtotal', $data['subtotal'] ?? 0);
        $stmt->bindValue(':tax_amount', $data['tax_amount'] ?? 0);
        $stmt->bindValue(':discount_amount', $data['discount_amount'] ?? 0);
        $stmt->bindValue(':shipping_charges', $data['shipping_charges'] ?? 0);
        $stmt->bindValue(':total_amount', $data['total_amount'] ?? 0);
        $stmt->bindValue(':source_branch', $data['source_branch'] ?? null);
        $stmt->bindValue(':source_address', $data['source_address'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':terms_conditions', $data['terms_conditions'] ?? null);
        $stmt->bindValue(':created_by', $data['created_by'] ?? null, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update existing supplier invoice
     */
    public function update($id, $data) {
        $query = "UPDATE supplier_invoices SET 
                    supplier_id = :supplier_id,
                    invoice_type = :invoice_type,
                    invoice_date = :invoice_date,
                    due_date = :due_date,
                    po_id = :po_id,
                    reference = :reference,
                    status = :status,
                    payment_status = :payment_status,
                    subtotal = :subtotal,
                    tax_amount = :tax_amount,
                    discount_amount = :discount_amount,
                    shipping_charges = :shipping_charges,
                    total_amount = :total_amount,
                    source_branch = :source_branch,
                    source_address = :source_address,
                    notes = :notes,
                    terms_conditions = :terms_conditions
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':supplier_id', $data['supplier_id'], PDO::PARAM_INT);
        $stmt->bindValue(':invoice_type', $data['invoice_type'] ?? 'supplier_invoice');
        $stmt->bindValue(':invoice_date', $data['invoice_date']);
        $stmt->bindValue(':due_date', $data['due_date'] ?? null);
        $stmt->bindValue(':po_id', $data['po_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':reference', $data['reference'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid');
        $stmt->bindValue(':subtotal', $data['subtotal'] ?? 0);
        $stmt->bindValue(':tax_amount', $data['tax_amount'] ?? 0);
        $stmt->bindValue(':discount_amount', $data['discount_amount'] ?? 0);
        $stmt->bindValue(':shipping_charges', $data['shipping_charges'] ?? 0);
        $stmt->bindValue(':total_amount', $data['total_amount'] ?? 0);
        $stmt->bindValue(':source_branch', $data['source_branch'] ?? null);
        $stmt->bindValue(':source_address', $data['source_address'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':terms_conditions', $data['terms_conditions'] ?? null);
        
        return $stmt->execute();
    }
    
    /**
     * Delete supplier invoice
     */
    public function delete($id) {
        // Delete invoice items first
        $query = "DELETE FROM supplier_invoice_items WHERE invoice_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete invoice payments
        $query = "DELETE FROM supplier_invoice_payments WHERE invoice_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete invoice
        $query = "DELETE FROM supplier_invoices WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Get invoice items
     */
    public function getItems($invoice_id) {
        $query = "SELECT sii.*, i.name as item_name, i.sku, i.unit, i.hsn_code
                  FROM supplier_invoice_items sii
                  LEFT JOIN items i ON sii.item_id = i.id
                  WHERE sii.invoice_id = :invoice_id
                  ORDER BY sii.id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add items to invoice
     */
    public function addItems($invoice_id, $items) {
        $query = "INSERT INTO supplier_invoice_items (
                    invoice_id, item_id, description, hsn_sac, qty, unit, rate, 
                    discount_amount, taxable_amount, cgst_percent, cgst_amount, 
                    sgst_percent, sgst_amount, igst_percent, igst_amount, total_amount
                  ) VALUES (
                    :invoice_id, :item_id, :description, :hsn_sac, :qty, :unit, :rate, 
                    :discount_amount, :taxable_amount, :cgst_percent, :cgst_amount, 
                    :sgst_percent, :sgst_amount, :igst_percent, :igst_amount, :total_amount
                  )";
        
        $stmt = $this->db->prepare($query);
        
        foreach ($items as $item) {
            $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
            $stmt->bindValue(':item_id', $item['item_id'], PDO::PARAM_INT);
            $stmt->bindValue(':description', $item['description'] ?? null);
            $stmt->bindValue(':hsn_sac', $item['hsn_sac'] ?? null);
            $stmt->bindValue(':qty', $item['qty']);
            $stmt->bindValue(':unit', $item['unit'] ?? null);
            $stmt->bindValue(':rate', $item['rate']);
            $stmt->bindValue(':discount_amount', $item['discount_amount'] ?? 0);
            $stmt->bindValue(':taxable_amount', $item['taxable_amount'] ?? 0);
            $stmt->bindValue(':cgst_percent', $item['cgst_percent'] ?? 0);
            $stmt->bindValue(':cgst_amount', $item['cgst_amount'] ?? 0);
            $stmt->bindValue(':sgst_percent', $item['sgst_percent'] ?? 0);
            $stmt->bindValue(':sgst_amount', $item['sgst_amount'] ?? 0);
            $stmt->bindValue(':igst_percent', $item['igst_percent'] ?? 0);
            $stmt->bindValue(':igst_amount', $item['igst_amount'] ?? 0);
            $stmt->bindValue(':total_amount', $item['total_amount'] ?? 0);
            
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Update invoice items (delete old and add new)
     */
    public function updateItems($invoice_id, $items) {
        // Delete existing items
        $query = "DELETE FROM supplier_invoice_items WHERE invoice_id = :invoice_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Add new items
        return $this->addItems($invoice_id, $items);
    }
    
    /**
     * Approve invoice
     */
    public function approve($id, $approved_by) {
        $query = "UPDATE supplier_invoices SET 
                    status = 'approved',
                    approved_by = :approved_by,
                    approved_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approved_by, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Generate next invoice number
     */
    public function generateInvoiceNumber() {
        $prefix = 'INV-';
        $yearMonth = date('Ym'); // Format: 202406
        
        $query = "SELECT invoice_no FROM supplier_invoices 
                  WHERE invoice_no LIKE :pattern 
                  ORDER BY invoice_no DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':pattern', $prefix . $yearMonth . '%');
        $stmt->execute();
        
        $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastInvoice) {
            $lastNumber = (int)substr($lastInvoice['invoice_no'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $yearMonth . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get invoice statistics
     */
    public function getStatistics($filters = []) {
        $query = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_amount,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                    SUM(total_amount) as total_amount
                  FROM supplier_invoices
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $query .= " AND invoice_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND invoice_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add payment to invoice
     */
    public function addPayment($invoice_id, $data) {
        $query = "INSERT INTO supplier_invoice_payments (
                    invoice_id, payment_date, amount, payment_mode, 
                    reference_no, notes, created_by
                  ) VALUES (
                    :invoice_id, :payment_date, :amount, :payment_mode, 
                    :reference_no, :notes, :created_by
                  )";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
        $stmt->bindValue(':payment_date', $data['payment_date']);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':payment_mode', $data['payment_mode'] ?? 'cash');
        $stmt->bindValue(':reference_no', $data['reference_no'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_by', $data['created_by'] ?? null, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Update paid amount and payment status in invoice
            $this->updatePaymentStatus($invoice_id);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get payments for an invoice
     */
    public function getPayments($invoice_id) {
        $query = "SELECT * FROM supplier_invoice_payments 
                  WHERE invoice_id = :invoice_id 
                  ORDER BY payment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update payment status based on paid amount
     */
    public function updatePaymentStatus($invoice_id) {
        // Calculate total paid amount
        $query = "SELECT SUM(amount) as total_paid FROM supplier_invoice_payments 
                  WHERE invoice_id = :invoice_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPaid = $result['total_paid'] ?? 0;
        
        // Get invoice total
        $invoice = $this->getById($invoice_id);
        $totalAmount = $invoice['total_amount'];
        
        // Determine payment status
        if ($totalPaid >= $totalAmount) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'unpaid';
        }
        
        // Update invoice
        $query = "UPDATE supplier_invoices SET 
                    paid_amount = :paid_amount,
                    payment_status = :payment_status
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':paid_amount', $totalPaid);
        $stmt->bindValue(':payment_status', $paymentStatus);
        $stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
