<?php
/**
 * Purchase Order Controller
 * Handles all purchase order related actions
 */

require_once 'models/PurchaseOrder.php';
require_once 'models/Supplier.php';
require_once 'models/Product.php';

class PurchaseOrderController {
    private $model;
    private $supplierModel;
    private $productModel;
    
    public function __construct() {
        $this->model = new PurchaseOrder();
        $this->supplierModel = new Supplier();
        $this->productModel = new Product();
    }
    
    /**
     * Index page data
     */
    public function index() {
        $data = [
            'title' => 'Purchase Orders',
            'stats' => $this->model->getStatistics()
        ];
        return $data;
    }
    
    /**
     * Get purchase orders as JSON for DataTable
     */
    public function getPurchaseOrdersJson() {
        try {
            // Get filters
            $filters = [
                'status' => $_GET['status'] ?? '',
                'month' => $_GET['month'] ?? date('Y-m'),
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'created_by' => $_GET['created_by'] ?? ''
            ];
            
            $purchaseOrders = $this->model->getAll($filters);
            
            $data = [];
            foreach ($purchaseOrders as $po) {
                $data[] = [
                    'supplier' => $this->formatSupplier($po),
                    'contact' => $this->formatContact($po),
                    'order_no' => htmlspecialchars($po['po_no']),
                    'order_date' => date('d-M', strtotime($po['date'])),
                    'taxable' => number_format($po['taxable_amount'], 2),
                    'amount' => number_format($po['total_amount'], 2),
                    'status' => $this->formatStatus($po['status']),
                    'actions' => $this->getActionButtons($po['id'], $po)
                ];
            }
            
            jsonResponse(true, 'Success', [
                'draw' => intval($_GET['draw'] ?? 1),
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data),
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPurchaseOrdersJson: " . $e->getMessage());
            jsonResponse(false, 'Error fetching purchase orders');
        }
    }
    
    /**
     * Create new purchase order
     */
    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(false, 'Invalid request method');
                return;
            }
            
            // Validate required fields
            if (empty($_POST['supplier_id'])) {
                jsonResponse(false, 'Supplier is required');
                return;
            }
            
            // Prepare data
            $data = [
                'po_no' => $_POST['po_no'] ?? '',
                'supplier_id' => $_POST['supplier_id'],
                'date' => $_POST['date'] ?? date('Y-m-d'),
                'expected_delivery_date' => $_POST['expected_delivery_date'] ?? null,
                'status' => $_POST['status'] ?? 'draft',
                'payment_terms' => $_POST['payment_terms'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'taxable_amount' => floatval($_POST['taxable_amount'] ?? 0),
                'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'items' => []
            ];
            
            // Parse items
            if (!empty($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $data['items'][] = [
                        'product_id' => $item['product_id'],
                        'description' => $item['description'] ?? '',
                        'quantity' => floatval($item['quantity']),
                        'unit_price' => floatval($item['unit_price']),
                        'tax_rate' => floatval($item['tax_rate'] ?? 0),
                        'tax_amount' => floatval($item['tax_amount'] ?? 0),
                        'discount_percent' => floatval($item['discount_percent'] ?? 0),
                        'discount_amount' => floatval($item['discount_amount'] ?? 0),
                        'total_amount' => floatval($item['total_amount']),
                        'notes' => $item['notes'] ?? ''
                    ];
                }
            }
            
            $poId = $this->model->create($data);
            
            if ($poId) {
                jsonResponse(true, 'Purchase order created successfully', ['id' => $poId]);
            } else {
                jsonResponse(false, 'Failed to create purchase order');
            }
            
        } catch (Exception $e) {
            error_log("Error in create: " . $e->getMessage());
            jsonResponse(false, 'Error creating purchase order: ' . $e->getMessage());
        }
    }
    
    /**
     * Update purchase order
     */
    public function update() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(false, 'Invalid request method');
                return;
            }
            
            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                jsonResponse(false, 'Purchase order ID is required');
                return;
            }
            
            // Prepare data
            $data = [
                'supplier_id' => $_POST['supplier_id'],
                'date' => $_POST['date'],
                'expected_delivery_date' => $_POST['expected_delivery_date'] ?? null,
                'status' => $_POST['status'] ?? 'draft',
                'payment_terms' => $_POST['payment_terms'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'taxable_amount' => floatval($_POST['taxable_amount'] ?? 0),
                'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'items' => []
            ];
            
            // Parse items
            if (!empty($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $data['items'][] = [
                        'product_id' => $item['product_id'],
                        'description' => $item['description'] ?? '',
                        'quantity' => floatval($item['quantity']),
                        'unit_price' => floatval($item['unit_price']),
                        'tax_rate' => floatval($item['tax_rate'] ?? 0),
                        'tax_amount' => floatval($item['tax_amount'] ?? 0),
                        'discount_percent' => floatval($item['discount_percent'] ?? 0),
                        'discount_amount' => floatval($item['discount_amount'] ?? 0),
                        'total_amount' => floatval($item['total_amount']),
                        'notes' => $item['notes'] ?? ''
                    ];
                }
            }
            
            $result = $this->model->update($id, $data);
            
            if ($result) {
                jsonResponse(true, 'Purchase order updated successfully');
            } else {
                jsonResponse(false, 'Failed to update purchase order');
            }
            
        } catch (Exception $e) {
            error_log("Error in update: " . $e->getMessage());
            jsonResponse(false, 'Error updating purchase order: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete purchase order
     */
    public function delete() {
        try {
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                jsonResponse(false, 'Purchase order ID is required');
                return;
            }
            
            $result = $this->model->delete($id);
            
            if ($result) {
                jsonResponse(true, 'Purchase order deleted successfully');
            } else {
                jsonResponse(false, 'Failed to delete purchase order. Only draft or cancelled orders can be deleted.');
            }
            
        } catch (Exception $e) {
            error_log("Error in delete: " . $e->getMessage());
            jsonResponse(false, 'Error deleting purchase order');
        }
    }
    
    /**
     * Get single purchase order
     */
    public function getPurchaseOrder() {
        try {
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                jsonResponse(false, 'Purchase order ID is required');
                return;
            }
            
            $po = $this->model->getById($id);
            
            if ($po) {
                // Get items
                $po['items'] = $this->model->getItems($id);
                jsonResponse(true, 'Success', $po);
            } else {
                jsonResponse(false, 'Purchase order not found');
            }
            
        } catch (Exception $e) {
            error_log("Error in getPurchaseOrder: " . $e->getMessage());
            jsonResponse(false, 'Error fetching purchase order');
        }
    }
    
    /**
     * Approve purchase order
     */
    public function approve() {
        try {
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                jsonResponse(false, 'Purchase order ID is required');
                return;
            }
            
            $result = $this->model->approve($id);
            
            if ($result) {
                jsonResponse(true, 'Purchase order approved successfully');
            } else {
                jsonResponse(false, 'Failed to approve purchase order');
            }
            
        } catch (Exception $e) {
            error_log("Error in approve: " . $e->getMessage());
            jsonResponse(false, 'Error approving purchase order');
        }
    }
    
    /**
     * Reject purchase order
     */
    public function reject() {
        try {
            $id = $_POST['id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            if (empty($id)) {
                jsonResponse(false, 'Purchase order ID is required');
                return;
            }
            
            $result = $this->model->reject($id, $reason);
            
            if ($result) {
                jsonResponse(true, 'Purchase order rejected');
            } else {
                jsonResponse(false, 'Failed to reject purchase order');
            }
            
        } catch (Exception $e) {
            error_log("Error in reject: " . $e->getMessage());
            jsonResponse(false, 'Error rejecting purchase order');
        }
    }
    
    /**
     * Generate PO number
     */
    public function generatePoNumber() {
        try {
            $poNumber = $this->model->generatePoNumber();
            jsonResponse(true, 'Success', ['po_number' => $poNumber]);
        } catch (Exception $e) {
            error_log("Error in generatePoNumber: " . $e->getMessage());
            jsonResponse(false, 'Error generating PO number');
        }
    }
    
    /**
     * Get suppliers for dropdown
     */
    public function getSuppliers() {
        try {
            $suppliers = $this->supplierModel->getAll();
            jsonResponse(true, 'Success', $suppliers);
        } catch (Exception $e) {
            error_log("Error fetching suppliers: " . $e->getMessage());
            jsonResponse(false, 'Error fetching suppliers');
        }
    }
    
    /**
     * Get products for dropdown
     */
    public function getProducts() {
        try {
            $products = $this->productModel->getAll();
            jsonResponse(true, 'Success', $products);
        } catch (Exception $e) {
            error_log("Error fetching products: " . $e->getMessage());
            jsonResponse(false, 'Error fetching products');
        }
    }
    
    /**
     * Format supplier column
     */
    private function formatSupplier($po) {
        $html = '<strong>' . htmlspecialchars($po['supplier_name'] ?? 'N/A') . '</strong>';
        return $html;
    }
    
    /**
     * Format contact column
     */
    private function formatContact($po) {
        $contact = htmlspecialchars($po['contact_name'] ?? '-');
        return $contact;
    }
    
    /**
     * Format status badge
     */
    private function formatStatus($status) {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'sent' => '<span class="badge bg-info">Sent</span>',
            'partial' => '<span class="badge bg-primary">Partial</span>',
            'received' => '<span class="badge bg-success">Received</span>',
            'cancelled' => '<span class="badge bg-dark">Cancelled</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
    
    /**
     * Get action buttons for PO row
     */
    private function getActionButtons($id, $po) {
        $buttons = '';
        
        // Edit button
        $buttons .= '<button class="btn btn-warning btn-sm me-1" onclick="editPurchaseOrder(' . $id . ')" title="Edit">
            <i class="fas fa-edit"></i>
        </button>';
        
        // View/Print button
        $buttons .= '<button class="btn btn-primary btn-sm me-1" onclick="viewPurchaseOrder(' . $id . ')" title="View">
            <i class="fas fa-eye"></i>
        </button>';
        
        // Approve button (if pending)
        if ($po['status'] == 'pending') {
            $buttons .= '<button class="btn btn-success btn-sm me-1" onclick="approvePurchaseOrder(' . $id . ')" title="Approve">
                <i class="fas fa-check"></i>
            </button>';
        }
        
        // Delete button (if draft or cancelled)
        if (in_array($po['status'], ['draft', 'cancelled'])) {
            $buttons .= '<button class="btn btn-danger btn-sm" onclick="deletePurchaseOrder(' . $id . ')" title="Delete">
                <i class="fas fa-trash"></i>
            </button>';
        }
        
        return $buttons;
    }
}
