<?php
/**
 * Invoice Controller
 * Handles all invoice requests
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Invoice.php';

// Handle AJAX requests
if (isset($_POST['action']) || isset($_GET['action'])) {
    $controller = new InvoiceController();
    $action = $_POST['action'] ?? $_GET['action'];
    
    switch ($action) {
        case 'getInvoicesJson':
            $controller->getInvoicesJson();
            break;
        case 'getSummaryTotals':
            $controller->getSummaryTotals();
            break;
        case 'createInvoice':
            $controller->createInvoice();
            break;
        case 'updateInvoice':
            $controller->updateInvoice();
            break;
        case 'getInvoice':
            $controller->getInvoice();
            break;
        case 'deleteInvoice':
            $controller->deleteInvoice();
            break;
        case 'getNextInvoiceNo':
            $controller->getNextInvoiceNo();
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
    exit;
}

class InvoiceController {
    private $model;
    
    public function __construct() {
        $this->model = new Invoice();
    }
    
    /**
     * Get invoices for DataTables
     */
    public function getInvoicesJson() {
        requireLogin();
        
        $filters = [
            'type' => $_POST['type'] ?? '',
            'status' => $_POST['status'] ?? '',
            'month' => $_POST['month'] ?? '',
            'executive' => $_POST['executive'] ?? '',
            'search' => $_POST['search']['value'] ?? ''
        ];
        
        $invoices = $this->model->getAll($filters);
        
        $data = [];
        foreach ($invoices as $invoice) {
            $data[] = [
                'id' => $invoice['id'],
                'customer' => htmlspecialchars($invoice['customer_name']),
                'invoice_no' => '<strong>' . htmlspecialchars($invoice['invoice_no']) . '</strong>',
                'invoice_date' => date('d-M', strtotime($invoice['invoice_date'])),
                'taxable' => number_format($invoice['taxable_amount'], 2),
                'amount' => number_format($invoice['total_amount'], 2),
                'status' => $this->getStatusBadge($invoice['payment_status']),
                'pending' => number_format($invoice['pending'], 2),
                'actions' => $this->getActionButtons($invoice['id'])
            ];
        }
        
        echo json_encode([
            'draw' => intval($_POST['draw'] ?? 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }
    
    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status) {
        $badges = [
            'unpaid' => '<span class="badge bg-warning">Unpaid</span>',
            'partial' => '<span class="badge bg-info">Partial</span>',
            'paid' => '<span class="badge bg-success">Paid</span>',
            'overdue' => '<span class="badge bg-danger">Overdue</span>'
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
    
    /**
     * Get summary totals
     */
    public function getSummaryTotals() {
        requireLogin();
        
        $filters = [
            'type' => $_POST['type'] ?? '',
            'status' => $_POST['status'] ?? '',
            'month' => $_POST['month'] ?? ''
        ];
        
        $totals = $this->model->getSummaryTotals($filters);
        jsonResponse(true, 'Totals retrieved', $totals);
    }
    
    /**
     * Create invoice
     */
    public function createInvoice() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        if (empty($_POST['customer_name'])) {
            jsonResponse(false, 'Customer name is required');
        }
        
        $invoiceNo = $_POST['invoice_no'] ?? $this->model->generateInvoiceNo();
        
        $data = [
            'invoice_no' => $invoiceNo,
            'reference' => sanitize($_POST['reference'] ?? ''),
            'invoice_type' => sanitize($_POST['invoice_type'] ?? 'party_invoice'),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'shipping_details' => sanitize($_POST['shipping_details'] ?? ''),
            'invoice_date' => $_POST['invoice_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? null,
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'taxable_amount' => floatval($_POST['taxable_amount'] ?? 0),
            'payment_status' => sanitize($_POST['payment_status'] ?? 'unpaid'),
            'pending_amount' => floatval($_POST['pending_amount'] ?? $_POST['total_amount'] ?? 0),
            'recovery_amount' => floatval($_POST['recovery_amount'] ?? 0),
            'bank_details' => sanitize($_POST['bank_details'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'internal_notes' => sanitize($_POST['internal_notes'] ?? ''),
            'is_template' => isset($_POST['is_template']) ? 1 : 0,
            'template_name' => sanitize($_POST['template_name'] ?? ''),
            'share_by_email' => isset($_POST['share_by_email']) ? 1 : 0,
            'share_by_whatsapp' => isset($_POST['share_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        $invoiceId = $this->model->create($data);
        
        if ($invoiceId) {
            logAudit('create', 'invoices', $invoiceId, null, $data);
            jsonResponse(true, 'Invoice created successfully', ['id' => $invoiceId, 'invoice_no' => $invoiceNo]);
        } else {
            jsonResponse(false, 'Failed to create invoice');
        }
    }
    
    /**
     * Update invoice
     */
    public function updateInvoice() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $invoiceId = intval($_POST['invoice_id'] ?? 0);
        if (!$invoiceId) {
            jsonResponse(false, 'Invalid invoice ID');
        }
        
        if (empty($_POST['customer_name'])) {
            jsonResponse(false, 'Customer name is required');
        }
        
        $data = [
            'reference' => sanitize($_POST['reference'] ?? ''),
            'invoice_type' => sanitize($_POST['invoice_type'] ?? 'party_invoice'),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'shipping_details' => sanitize($_POST['shipping_details'] ?? ''),
            'invoice_date' => $_POST['invoice_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? null,
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'taxable_amount' => floatval($_POST['taxable_amount'] ?? 0),
            'payment_status' => sanitize($_POST['payment_status'] ?? 'unpaid'),
            'pending_amount' => floatval($_POST['pending_amount'] ?? $_POST['total_amount'] ?? 0),
            'recovery_amount' => floatval($_POST['recovery_amount'] ?? 0),
            'bank_details' => sanitize($_POST['bank_details'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'internal_notes' => sanitize($_POST['internal_notes'] ?? ''),
            'is_template' => isset($_POST['is_template']) ? 1 : 0,
            'template_name' => sanitize($_POST['template_name'] ?? ''),
            'share_by_email' => isset($_POST['share_by_email']) ? 1 : 0,
            'share_by_whatsapp' => isset($_POST['share_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        $success = $this->model->update($invoiceId, $data);
        
        if ($success) {
            logAudit('update', 'invoices', $invoiceId, null, $data);
            jsonResponse(true, 'Invoice updated successfully', ['id' => $invoiceId]);
        } else {
            jsonResponse(false, 'Failed to update invoice');
        }
    }
    
    /**
     * Get invoice details
     */
    public function getInvoice() {
        requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid invoice ID');
        }
        
        $invoice = $this->model->getById($id);
        if ($invoice) {
            $invoice['items'] = $this->model->getItems($id);
            $invoice['terms'] = $this->model->getTerms($id);
            jsonResponse(true, 'Invoice retrieved', $invoice);
        } else {
            jsonResponse(false, 'Invoice not found');
        }
    }
    
    /**
     * Delete invoice
     */
    public function deleteInvoice() {
        requireLogin();
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid invoice ID');
        }
        
        if ($this->model->delete($id)) {
            logAudit('delete', 'invoices', $id);
            jsonResponse(true, 'Invoice deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete invoice');
        }
    }
    
    /**
     * Get next invoice number
     */
    public function getNextInvoiceNo() {
        requireLogin();
        $invoiceNo = $this->model->generateInvoiceNo();
        jsonResponse(true, 'Invoice number generated', ['invoice_no' => $invoiceNo]);
    }
    
    /**
     * Get action buttons
     */
    private function getActionButtons($id) {
        return '
            <button class="btn btn-sm btn-warning btn-edit" data-id="' . $id . '" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-success btn-print" data-id="' . $id . '" title="Print">
                <i class="fas fa-print"></i>
            </button>
            <button class="btn btn-sm btn-secondary btn-star" data-id="' . $id . '" title="Star">
                <i class="far fa-star"></i>
            </button>
        ';
    }
}
