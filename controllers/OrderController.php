<?php
/**
 * Order Controller
 * Handles all order and delivery requests
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Delivery.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    $controller = new OrderController();
    $action = $_POST['action'];
    
    switch ($action) {
        case 'getOrdersJson':
            $controller->getOrdersJson();
            break;
        case 'getCommitmentCounts':
            $controller->getCommitmentCounts();
            break;
        case 'createOrder':
            $controller->createOrder();
            break;
        case 'updateOrder':
            $controller->updateOrder();
            break;
        case 'createQuickOrder':
            $controller->createQuickOrder();
            break;
        case 'createDelivery':
            $controller->createDelivery();
            break;
        case 'getOrder':
            $controller->getOrder();
            break;
        case 'getNextOrderNo':
            $controller->getNextOrderNo();
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
    exit;
}

class OrderController {
    private $model;
    private $deliveryModel;
    
    public function __construct() {
        $this->model = new Order();
        $this->deliveryModel = new Delivery();
    }
    
    /**
     * Get orders for DataTables
     */
    public function getOrdersJson() {
        requireLogin();
        
        $filters = [
            'status' => $_POST['status'] ?? '',
            'commitment' => $_POST['commitment'] ?? '',
            'order_type' => $_POST['order_type'] ?? '',
            'search' => $_POST['search']['value'] ?? ''
        ];
        
        $orders = $this->model->getAll($filters);
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order['id'],
                'customer' => htmlspecialchars($order['customer_name']),
                'contact' => htmlspecialchars($order['contact_person'] ?? '-'),
                'order_no' => '<strong>' . htmlspecialchars($order['order_no']) . '</strong>',
                'cust_po' => htmlspecialchars($order['customer_po_no'] ?? '-'),
                'item' => $this->getFirstItem($order['id']),
                'due_date' => date('d-M-y', strtotime($order['due_date'])),
                'qty' => $order['total_qty'],
                'pndg' => $order['total_pending'],
                'done' => $order['total_done'],
                'unit' => 'no.s',
                'total' => number_format($order['total_amount'], 2),
                'status' => ucfirst($order['status']),
                'actions' => $this->getActionButtons($order['id'])
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
     * Get first item description
     */
    private function getFirstItem($orderId) {
        $items = $this->model->getItems($orderId);
        return !empty($items) ? htmlspecialchars($items[0]['item_description']) : '-';
    }
    
    /**
     * Get commitment counts
     */
    public function getCommitmentCounts() {
        requireLogin();
        $counts = $this->model->getCommitmentCounts();
        jsonResponse(true, 'Counts retrieved', $counts);
    }
    
    /**
     * Create order
     */
    public function createOrder() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        if (empty($_POST['customer_name'])) {
            jsonResponse(false, 'Customer name is required');
        }
        
        $orderNo = $_POST['order_no'] ?? $this->model->generateOrderNo();
        
        $data = [
            'order_no' => $orderNo,
            'reference' => sanitize($_POST['reference'] ?? ''),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'customer_po_no' => sanitize($_POST['customer_po_no'] ?? ''),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'executive_id' => !empty($_POST['executive_id']) ? intval($_POST['executive_id']) : null,
            'responsible_id' => !empty($_POST['responsible_id']) ? intval($_POST['responsible_id']) : null,
            'order_type' => sanitize($_POST['order_type'] ?? 'sales'),
            'status' => 'pending',
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'update_by_email' => isset($_POST['update_by_email']) ? 1 : 0,
            'update_by_whatsapp' => isset($_POST['update_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        $orderId = $this->model->create($data);
        
        if ($orderId) {
            logAudit('create', 'orders', $orderId, null, $data);
            jsonResponse(true, 'Order created successfully', ['id' => $orderId, 'order_no' => $orderNo]);
        } else {
            jsonResponse(false, 'Failed to create order');
        }
    }
    
    /**
     * Update order
     */
    public function updateOrder() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $orderId = intval($_POST['order_id'] ?? 0);
        if (!$orderId) {
            jsonResponse(false, 'Invalid order ID');
        }
        
        if (empty($_POST['customer_name'])) {
            jsonResponse(false, 'Customer name is required');
        }
        
        $data = [
            'order_no' => sanitize($_POST['order_no']),
            'reference' => sanitize($_POST['reference'] ?? ''),
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'customer_name' => sanitize($_POST['customer_name']),
            'customer_po_no' => sanitize($_POST['customer_po_no'] ?? ''),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'branch_id' => !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null,
            'sales_credit' => sanitize($_POST['sales_credit'] ?? 'None'),
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'executive_id' => !empty($_POST['executive_id']) ? intval($_POST['executive_id']) : null,
            'responsible_id' => !empty($_POST['responsible_id']) ? intval($_POST['responsible_id']) : null,
            'order_type' => sanitize($_POST['order_type'] ?? 'sales'),
            'status' => sanitize($_POST['status'] ?? 'pending'),
            'subtotal' => floatval($_POST['subtotal'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'extra_charges' => floatval($_POST['extra_charges'] ?? 0),
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'update_by_email' => isset($_POST['update_by_email']) ? 1 : 0,
            'update_by_whatsapp' => isset($_POST['update_by_whatsapp']) ? 1 : 0,
            'print_after_saving' => isset($_POST['print_after_saving']) ? 1 : 0,
            'items' => $_POST['items'] ?? [],
            'terms' => $_POST['terms'] ?? []
        ];
        
        $success = $this->model->update($orderId, $data);
        
        if ($success) {
            logAudit('update', 'orders', $orderId, null, $data);
            jsonResponse(true, 'Order updated successfully', ['id' => $orderId]);
        } else {
            jsonResponse(false, 'Failed to update order');
        }
    }
    
    /**
     * Create quick order
     */
    public function createQuickOrder() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $orderNo = $this->model->generateOrderNo();
        
        $data = [
            'order_no' => $orderNo,
            'customer_name' => sanitize($_POST['customer_name']),
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? null,
            'executive_id' => !empty($_POST['executive_id']) ? intval($_POST['executive_id']) : null,
            'responsible_id' => !empty($_POST['responsible_id']) ? intval($_POST['responsible_id']) : null,
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'order_type' => sanitize($_POST['order_type'] ?? 'sales'),
            'status' => 'pending',
            'total_amount' => 0,
            'update_by_email' => isset($_POST['update_by_email']) ? 1 : 0,
            'update_by_whatsapp' => isset($_POST['update_by_whatsapp']) ? 1 : 0,
            'items' => $_POST['items'] ?? []
        ];
        
        $orderId = $this->model->create($data);
        
        if ($orderId) {
            logAudit('create', 'orders', $orderId, null, $data);
            jsonResponse(true, 'Quick order created successfully', ['id' => $orderId, 'order_no' => $orderNo]);
        } else {
            jsonResponse(false, 'Failed to create quick order');
        }
    }
    
    /**
     * Create delivery
     */
    public function createDelivery() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $deliveryNo = $this->deliveryModel->generateDeliveryNo();
        
        $data = [
            'delivery_no' => $deliveryNo,
            'customer_name' => sanitize($_POST['customer_name']),
            'delivery_date' => $_POST['delivery_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? null,
            'sales_executive_id' => !empty($_POST['sales_executive_id']) ? intval($_POST['sales_executive_id']) : null,
            'responsible_executive_id' => !empty($_POST['responsible_executive_id']) ? intval($_POST['responsible_executive_id']) : null,
            'billing_address' => sanitize($_POST['billing_address'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'same_as_billing' => isset($_POST['same_as_billing']) ? 1 : 0,
            'delivery_details' => sanitize($_POST['delivery_details'] ?? ''),
            'recovery_amount' => floatval($_POST['recovery_amount'] ?? 0),
            'add_recovery' => floatval($_POST['add_recovery'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'update_by_email' => isset($_POST['update_by_email']) ? 1 : 0,
            'update_by_whatsapp' => isset($_POST['update_by_whatsapp']) ? 1 : 0,
            'status' => 'pending',
            'items' => $_POST['items'] ?? []
        ];
        
        $deliveryId = $this->deliveryModel->create($data);
        
        if ($deliveryId) {
            logAudit('create', 'deliveries', $deliveryId, null, $data);
            jsonResponse(true, 'Delivery created successfully', ['id' => $deliveryId, 'delivery_no' => $deliveryNo]);
        } else {
            jsonResponse(false, 'Failed to create delivery');
        }
    }
    
    /**
     * Get order details
     */
    public function getOrder() {
        requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid order ID');
        }
        
        $order = $this->model->getById($id);
        if ($order) {
            $order['items'] = $this->model->getItems($id);
            $order['terms'] = $this->model->getTerms($id);
            jsonResponse(true, 'Order retrieved', $order);
        } else {
            jsonResponse(false, 'Order not found');
        }
    }
    
    /**
     * Get next order number
     */
    public function getNextOrderNo() {
        requireLogin();
        $orderNo = $this->model->generateOrderNo();
        jsonResponse(true, 'Order number generated', ['order_no' => $orderNo]);
    }
    
    /**
     * Get action buttons
     */
    private function getActionButtons($id) {
        return '
            <button class="btn btn-sm btn-success btn-view" data-id="' . $id . '" title="View">
                <i class="fas fa-redo"></i>
            </button>
            <button class="btn btn-sm btn-warning btn-edit" data-id="' . $id . '" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
        ';
    }
}
