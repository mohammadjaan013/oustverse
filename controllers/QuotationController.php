<?php
/**
 * Quotation Controller
 * Handles all quotation requests
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Quotation.php';

class QuotationController {
    private $model;
    
    public function __construct() {
        $this->model = new Quotation();
    }
    
    /**
     * Get quotations for DataTables
     */
    public function getQuotationsJson() {
        requireLogin();
        
        $filters = [
            'type' => $_POST['type'] ?? '',
            'status' => $_POST['status'] ?? '',
            'month' => $_POST['month'] ?? '',
            'branch' => $_POST['branch'] ?? '',
            'executive' => $_POST['executive'] ?? '',
            'search' => $_POST['search']['value'] ?? ''
        ];
        
        $quotations = $this->model->getAll($filters);
        
        $data = [];
        foreach ($quotations as $quote) {
            $data[] = [
                'id' => $quote['id'],
                'quote_no' => '<strong>' . htmlspecialchars($quote['quote_no']) . '</strong>',
                'customer' => htmlspecialchars($quote['customer_name']),
                'amount' => number_format($quote['total_amount'], 3),
                'valid_till' => date('d-M', strtotime($quote['valid_till'])),
                'issued_on' => date('d-M', strtotime($quote['quotation_date'])),
                'issued_by' => htmlspecialchars($quote['issued_by_name'] ?? '-'),
                'type' => ucfirst(str_replace('_', ' ', $quote['type'])),
                'executive' => htmlspecialchars($quote['executive_name'] ?? '-'),
                'response' => '-',
                'actions' => $this->getActionButtons($quote['id'])
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
     * Get totals
     */
    public function getTotals() {
        requireLogin();
        
        $filters = [
            'type' => $_GET['type'] ?? '',
            'month' => $_GET['month'] ?? ''
        ];
        
        $totals = $this->model->getTotals($filters);
        jsonResponse(true, 'Totals retrieved', $totals);
    }
    
    /**
     * Create quotation
     */
    public function create() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        if (empty($_POST['customer_name'])) {
            jsonResponse(false, 'Customer name is required');
        }
        
        // Generate quote number if not provided
        $quoteNo = $_POST['quote_no'] ?? $this->model->generateQuoteNo();
        
        $data = [
            'quote_no' => $quoteNo,
            'reference' => sanitize($_POST['reference'] ?? ''),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'copy_from' => !empty($_POST['copy_from']) ? intval($_POST['copy_from']) : null,
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'branch_name' => sanitize($_POST['branch_name'] ?? ''),
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'quotation_date' => $_POST['quotation_date'] ?? date('Y-m-d'),
            'valid_till' => $_POST['valid_till'] ?? date('Y-m-d'),
            'type' => sanitize($_POST['type'] ?? 'quotation'),
            'status' => 'draft',
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'terms_conditions' => $_POST['terms_conditions'] ?? null,
            'bank_details' => sanitize($_POST['bank_details'] ?? ''),
            'save_as_template' => isset($_POST['save_as_template']) ? 1 : 0,
            'share_by_email' => isset($_POST['share_by_email']) ? 1 : 0,
            'share_by_whatsapp' => isset($_POST['share_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'alert_on_opening' => isset($_POST['alert_on_opening']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        $quotationId = $this->model->create($data);
        
        if ($quotationId) {
            logAudit('create', 'quotations', $quotationId, null, $data);
            jsonResponse(true, 'Quotation created successfully', ['id' => $quotationId, 'quote_no' => $quoteNo]);
        } else {
            jsonResponse(false, 'Failed to create quotation');
        }
    }
    
    /**
     * Update quotation
     */
    public function update() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid quotation ID');
        }
        
        $oldData = $this->model->getById($id);
        if (!$oldData) {
            jsonResponse(false, 'Quotation not found');
        }
        
        $data = [
            'reference' => sanitize($_POST['reference'] ?? ''),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'copy_from' => !empty($_POST['copy_from']) ? intval($_POST['copy_from']) : null,
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'branch_name' => sanitize($_POST['branch_name'] ?? ''),
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'quotation_date' => $_POST['quotation_date'],
            'valid_till' => $_POST['valid_till'],
            'type' => sanitize($_POST['type'] ?? 'quotation'),
            'status' => sanitize($_POST['status'] ?? 'draft'),
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'terms_conditions' => $_POST['terms_conditions'] ?? null,
            'bank_details' => sanitize($_POST['bank_details'] ?? ''),
            'save_as_template' => isset($_POST['save_as_template']) ? 1 : 0,
            'share_by_email' => isset($_POST['share_by_email']) ? 1 : 0,
            'share_by_whatsapp' => isset($_POST['share_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'alert_on_opening' => isset($_POST['alert_on_opening']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        if ($this->model->update($id, $data)) {
            logAudit('update', 'quotations', $id, $oldData, $data);
            jsonResponse(true, 'Quotation updated successfully');
        } else {
            jsonResponse(false, 'Failed to update quotation');
        }
    }
    
    /**
     * Delete quotation
     */
    public function delete() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid quotation ID');
        }
        
        if ($this->model->delete($id)) {
            logAudit('delete', 'quotations', $id, null, null);
            jsonResponse(true, 'Quotation deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete quotation');
        }
    }
    
    /**
     * Get quotation details
     */
    public function getQuotation() {
        requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid quotation ID');
        }
        
        $quotation = $this->model->getById($id);
        if ($quotation) {
            $quotation['items'] = $this->model->getItems($id);
            $quotation['terms'] = $this->model->getTerms($id);
            jsonResponse(true, 'Quotation retrieved', $quotation);
        } else {
            jsonResponse(false, 'Quotation not found');
        }
    }
    
    /**
     * Get next quote number
     */
    public function getNextQuoteNo() {
        requireLogin();
        $quoteNo = $this->model->generateQuoteNo();
        jsonResponse(true, 'Quote number generated', ['quote_no' => $quoteNo]);
    }
    
    /**
     * Get action buttons
     */
    private function getActionButtons($id) {
        return '
            <button class="btn btn-sm btn-warning btn-view" data-id="' . $id . '" title="View">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-primary btn-edit" data-id="' . $id . '" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
        ';
    }
}
