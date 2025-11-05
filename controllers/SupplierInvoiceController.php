<?php
require_once __DIR__ . '/../models/SupplierInvoice.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/Product.php';

class SupplierInvoiceController {
    private $invoiceModel;
    private $supplierModel;
    private $productModel;
    
    public function __construct() {
        $this->invoiceModel = new SupplierInvoice();
        $this->supplierModel = new Supplier();
        $this->productModel = new Product();
    }
    
    /**
     * Handle AJAX requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'getInvoices':
                $this->getInvoicesJson();
                break;
                
            case 'create':
                $this->create();
                break;
                
            case 'update':
                $this->update();
                break;
                
            case 'delete':
                $this->delete();
                break;
                
            case 'getInvoice':
                $this->getInvoice();
                break;
                
            case 'approve':
                $this->approve();
                break;
                
            case 'generateInvoiceNo':
                $this->generateInvoiceNumber();
                break;
                
            case 'getSuppliers':
                $this->getSuppliers();
                break;
                
            case 'getProducts':
                $this->getProducts();
                break;
                
            case 'addPayment':
                $this->addPayment();
                break;
                
            case 'getPayments':
                $this->getPayments();
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    /**
     * Get invoices for DataTables
     */
    public function getInvoicesJson() {
        $draw = $_GET['draw'] ?? 1;
        $start = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 10;
        $search = $_GET['search']['value'] ?? '';
        
        $filters = [
            'search' => $search,
            'limit' => $length,
            'offset' => $start
        ];
        
        // Add additional filters
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['invoice_type'])) {
            $filters['invoice_type'] = $_GET['invoice_type'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        $invoices = $this->invoiceModel->getAll($filters);
        $totalRecords = $this->invoiceModel->getCount([]);
        $filteredRecords = $this->invoiceModel->getCount($filters);
        
        $data = [];
        foreach ($invoices as $invoice) {
            $data[] = [
                'id' => $invoice['id'],
                'supplier' => $this->formatSupplier($invoice),
                'contact' => $this->formatContact($invoice),
                'invoice_no' => $invoice['invoice_no'],
                'invoice_date' => date('d-M-Y', strtotime($invoice['invoice_date'])),
                'taxable' => '₹ ' . number_format($invoice['subtotal'], 2),
                'amount' => '₹ ' . number_format($invoice['total_amount'], 2),
                'credit_month' => $this->calculateCreditMonth($invoice),
                'status' => $this->formatStatus($invoice['status']),
                'payment_status' => $this->formatPaymentStatus($invoice['payment_status']),
                'actions' => $this->getActionButtons($invoice)
            ];
        }
        
        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    /**
     * Create new invoice
     */
    public function create() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($data['supplier_id']) || empty($data['invoice_date'])) {
                throw new Exception('Supplier and invoice date are required');
            }
            
            // Generate invoice number if not provided
            if (empty($data['invoice_no'])) {
                $data['invoice_no'] = $this->invoiceModel->generateInvoiceNumber();
            }
            
            // Set created by
            $data['created_by'] = $_SESSION['user_id'] ?? 1;
            
            // Create invoice
            $invoiceId = $this->invoiceModel->create($data);
            
            if (!$invoiceId) {
                throw new Exception('Failed to create invoice');
            }
            
            // Add items
            if (!empty($data['items'])) {
                $this->invoiceModel->addItems($invoiceId, $data['items']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice_id' => $invoiceId
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update existing invoice
     */
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('Invoice ID is required');
            }
            
            $invoiceId = $data['id'];
            unset($data['id']);
            
            // Update invoice
            if (!$this->invoiceModel->update($invoiceId, $data)) {
                throw new Exception('Failed to update invoice');
            }
            
            // Update items
            if (isset($data['items'])) {
                $this->invoiceModel->updateItems($invoiceId, $data['items']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice updated successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete invoice
     */
    public function delete() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Invoice ID is required');
            }
            
            // Check if invoice can be deleted (only draft or cancelled)
            $invoice = $this->invoiceModel->getById($id);
            if (!in_array($invoice['status'], ['draft', 'cancelled'])) {
                throw new Exception('Only draft or cancelled invoices can be deleted');
            }
            
            if (!$this->invoiceModel->delete($id)) {
                throw new Exception('Failed to delete invoice');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get single invoice with items
     */
    public function getInvoice() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Invoice ID is required');
            }
            
            $invoice = $this->invoiceModel->getById($id);
            
            if (!$invoice) {
                throw new Exception('Invoice not found');
            }
            
            $invoice['items'] = $this->invoiceModel->getItems($id);
            
            echo json_encode([
                'success' => true,
                'invoice' => $invoice
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Approve invoice
     */
    public function approve() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Invoice ID is required');
            }
            
            $approvedBy = $_SESSION['user_id'] ?? 1;
            
            if (!$this->invoiceModel->approve($id, $approvedBy)) {
                throw new Exception('Failed to approve invoice');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice approved successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate new invoice number
     */
    public function generateInvoiceNumber() {
        $invoiceNo = $this->invoiceModel->generateInvoiceNumber();
        echo json_encode([
            'success' => true,
            'invoice_no' => $invoiceNo
        ]);
    }
    
    /**
     * Get suppliers for dropdown
     */
    public function getSuppliers() {
        $search = $_GET['q'] ?? '';
        $suppliers = $this->supplierModel->getSuppliers(['search' => $search]);
        
        $result = [];
        foreach ($suppliers as $supplier) {
            $result[] = [
                'id' => $supplier['id'],
                'text' => $supplier['name'],
                'contact_name' => $supplier['contact_name'],
                'mobile' => $supplier['mobile'],
                'phone' => $supplier['phone'],
                'email' => $supplier['email'],
                'address' => $supplier['address'],
                'city' => $supplier['city'],
                'state' => $supplier['state'],
                'pincode' => $supplier['pincode'],
                'gstin' => $supplier['gstin']
            ];
        }
        
        echo json_encode(['results' => $result]);
    }
    
    /**
     * Get products for dropdown
     */
    public function getProducts() {
        $search = $_GET['q'] ?? '';
        $products = $this->productModel->getAll(['search' => $search]);
        
        $result = [];
        foreach ($products as $product) {
            $result[] = [
                'id' => $product['id'],
                'text' => $product['name'],
                'sku' => $product['sku'] ?? '',
                'hsn_code' => $product['hsn_code'] ?? '',
                'unit' => $product['unit'] ?? 'PCS',
                'price' => $product['retail_price'] ?? $product['standard_cost'] ?? 0,
                'tax_rate' => $product['tax_rate'] ?? 0
            ];
        }
        
        echo json_encode(['results' => $result]);
    }
    
    /**
     * Add payment to invoice
     */
    public function addPayment() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['invoice_id']) || empty($data['amount'])) {
                throw new Exception('Invoice ID and amount are required');
            }
            
            $data['created_by'] = $_SESSION['user_id'] ?? 1;
            
            $paymentId = $this->invoiceModel->addPayment($data['invoice_id'], $data);
            
            if (!$paymentId) {
                throw new Exception('Failed to add payment');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment added successfully',
                'payment_id' => $paymentId
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get payments for an invoice
     */
    public function getPayments() {
        try {
            $invoiceId = $_GET['invoice_id'] ?? null;
            
            if (!$invoiceId) {
                throw new Exception('Invoice ID is required');
            }
            
            $payments = $this->invoiceModel->getPayments($invoiceId);
            
            echo json_encode([
                'success' => true,
                'payments' => $payments
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Format supplier name for display
     */
    private function formatSupplier($invoice) {
        return '<strong>' . htmlspecialchars($invoice['supplier_name']) . '</strong>';
    }
    
    /**
     * Format contact for display
     */
    private function formatContact($invoice) {
        $contact = htmlspecialchars($invoice['contact_name'] ?? '');
        $phone = $invoice['mobile'] ?? $invoice['phone'] ?? '';
        
        if ($phone) {
            return $contact . '<br><small class="text-muted">' . $phone . '</small>';
        }
        
        return $contact;
    }
    
    /**
     * Format status badge
     */
    private function formatStatus($status) {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'paid' => '<span class="badge bg-info">Paid</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
    
    /**
     * Format payment status badge
     */
    private function formatPaymentStatus($status) {
        $badges = [
            'unpaid' => '<span class="badge bg-danger">Unpaid</span>',
            'partial' => '<span class="badge bg-warning">Partial</span>',
            'paid' => '<span class="badge bg-success">Paid</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
    
    /**
     * Calculate credit month from invoice date
     */
    private function calculateCreditMonth($invoice) {
        if (empty($invoice['invoice_date'])) {
            return '-';
        }
        
        $date = new DateTime($invoice['invoice_date']);
        return $date->format('M Y'); // e.g., "Jun 2024"
    }
    
    /**
     * Get action buttons based on status
     */
    private function getActionButtons($invoice) {
        $buttons = '<div class="btn-group btn-group-sm">';
        
        // View button - always available
        $buttons .= '<a href="supplier_invoice_form.php?id=' . $invoice['id'] . '" class="btn btn-outline-primary" title="View">
                        <i class="fas fa-eye"></i>
                    </a>';
        
        // Edit button - only for draft or pending
        if (in_array($invoice['status'], ['draft', 'pending'])) {
            $buttons .= '<a href="supplier_invoice_form.php?id=' . $invoice['id'] . '&edit=1" class="btn btn-outline-secondary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>';
        }
        
        // Approve button - only for pending
        if ($invoice['status'] == 'pending') {
            $buttons .= '<button class="btn btn-outline-success approve-invoice" data-id="' . $invoice['id'] . '" title="Approve">
                            <i class="fas fa-check-circle"></i>
                        </button>';
        }
        
        // Payment button - for approved invoices
        if ($invoice['status'] == 'approved' && $invoice['payment_status'] != 'paid') {
            $buttons .= '<button class="btn btn-outline-info add-payment" data-id="' . $invoice['id'] . '" title="Add Payment">
                            <i class="fas fa-money-bill"></i>
                        </button>';
        }
        
        // Delete button - only for draft or cancelled
        if (in_array($invoice['status'], ['draft', 'cancelled'])) {
            $buttons .= '<button class="btn btn-outline-danger delete-invoice" data-id="' . $invoice['id'] . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>';
        }
        
        $buttons .= '</div>';
        
        return $buttons;
    }
}

// Handle request
if (isset($_GET['action'])) {
    $controller = new SupplierInvoiceController();
    $controller->handleRequest();
}
