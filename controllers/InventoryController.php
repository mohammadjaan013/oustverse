<?php
/**
 * Inventory Controller
 * Handles all inventory-related requests
 */

require_once __DIR__ . '/../models/Inventory.php';

class InventoryController {
    private $model;

    public function __construct() {
        $this->model = new Inventory();
    }

    /**
     * List all items
     */
    public function index() {
        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'location_id' => $_GET['location_id'] ?? '',
            'search' => $_GET['search'] ?? '',
            'is_active' => 1
        ];

        $items = $this->model->getItems($filters);
        $categories = $this->model->getCategories();
        $locations = $this->model->getLocations();

        return [
            'items' => $items,
            'categories' => $categories,
            'locations' => $locations,
            'filters' => $filters
        ];
    }

    /**
     * Get item data for AJAX
     */
    public function getItemsJson() {
        header('Content-Type: application/json');
        
        $filters = [
            'search' => $_GET['search']['value'] ?? '',
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'location_id' => $_GET['location_id'] ?? ''
        ];

        $items = $this->model->getItems($filters);
        
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item['id'],
                'sku' => $item['sku'],
                'name' => $item['name'],
                'category' => $item['category_name'] ?? '-',
                'qty' => $item['total_qty'],
                'unit' => $item['unit'],
                'rate' => $item['standard_cost'],
                'value' => $item['total_value'],
                'reorder_level' => $item['reorder_level'],
                'actions' => $this->getActionButtons($item['id'])
            ];
        }

        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
        exit;
    }

    /**
     * Create new item
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        // Validate CSRF
        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['sku'])) {
            jsonResponse(false, 'Name and SKU are required');
        }

        // Check if SKU already exists
        if ($this->model->getItemBySKU($_POST['sku'])) {
            jsonResponse(false, 'SKU already exists');
        }

        try {
            $data = [
                'sku' => sanitize($_POST['sku']),
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description'] ?? ''),
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'unit' => sanitize($_POST['unit'] ?? 'PCS'),
                'standard_cost' => floatval($_POST['standard_cost'] ?? 0),
                'retail_price' => floatval($_POST['retail_price'] ?? 0),
                'reorder_level' => intval($_POST['reorder_level'] ?? 0),
                'hsn_code' => sanitize($_POST['hsn_code'] ?? ''),
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
                'is_active' => 1
            ];

            $itemId = $this->model->createItem($data);

            // Log audit
            logAudit('create', 'items', $itemId, null, $data);

            jsonResponse(true, 'Item created successfully', ['id' => $itemId]);

        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, 'Failed to create item: ' . $e->getMessage());
        }
    }

    /**
     * Update item
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid item ID');
        }

        // Get current item for audit
        $oldData = $this->model->getItemById($id);
        if (!$oldData) {
            jsonResponse(false, 'Item not found');
        }

        try {
            $data = [
                'sku' => sanitize($_POST['sku']),
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description'] ?? ''),
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'unit' => sanitize($_POST['unit'] ?? 'PCS'),
                'standard_cost' => floatval($_POST['standard_cost'] ?? 0),
                'retail_price' => floatval($_POST['retail_price'] ?? 0),
                'reorder_level' => intval($_POST['reorder_level'] ?? 0),
                'hsn_code' => sanitize($_POST['hsn_code'] ?? ''),
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
                'is_active' => intval($_POST['is_active'] ?? 1)
            ];

            $this->model->updateItem($id, $data);

            // Log audit
            logAudit('update', 'items', $id, $oldData, $data);

            jsonResponse(true, 'Item updated successfully');

        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, 'Failed to update item: ' . $e->getMessage());
        }
    }

    /**
     * Delete item
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid item ID');
        }

        try {
            $this->model->deleteItem($id);
            logAudit('delete', 'items', $id, null, null);
            jsonResponse(true, 'Item deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, 'Failed to delete item: ' . $e->getMessage());
        }
    }

    /**
     * Stock IN - Receive items
     */
    public function stockIn() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        try {
            $data = [
                'item_id' => intval($_POST['item_id']),
                'location_to' => intval($_POST['location_id']),
                'qty' => intval($_POST['qty']),
                'rate' => floatval($_POST['rate'] ?? 0),
                'type' => 'in',
                'ref_type' => sanitize($_POST['ref_type'] ?? 'manual'),
                'ref_id' => intval($_POST['ref_id'] ?? 0),
                'notes' => sanitize($_POST['notes'] ?? ''),
                'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
                'assignment_notes' => sanitize($_POST['assignment_notes'] ?? ''),
                'assignment_status' => !empty($_POST['assigned_to']) ? 'pending' : 'completed'
            ];

            if ($data['qty'] <= 0) {
                jsonResponse(false, 'Quantity must be greater than 0');
            }

            $movementId = $this->model->addStockMovement($data);

            logAudit('stock_in', 'stock_movements', $movementId, null, $data);

            // Build success message
            $message = 'Stock received successfully';
            if ($data['assigned_to']) {
                $message .= ' and assigned';
            }

            jsonResponse(true, $message, ['id' => $movementId]);

        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, 'Failed to receive stock: ' . $e->getMessage());
        }
    }

    /**
     * Stock OUT - Issue items
     */
    public function stockOut() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        try {
            $data = [
                'item_id' => intval($_POST['item_id']),
                'location_from' => intval($_POST['location_id']),
                'qty' => intval($_POST['qty']),
                'rate' => floatval($_POST['rate'] ?? 0),
                'type' => 'out',
                'ref_type' => sanitize($_POST['ref_type'] ?? 'manual'),
                'ref_id' => intval($_POST['ref_id'] ?? 0),
                'notes' => sanitize($_POST['notes'] ?? ''),
                'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
                'assignment_notes' => sanitize($_POST['assignment_notes'] ?? ''),
                'assignment_status' => !empty($_POST['assigned_to']) ? 'pending' : 'completed'
            ];

            if ($data['qty'] <= 0) {
                jsonResponse(false, 'Quantity must be greater than 0');
            }

            // Check if sufficient stock exists
            $stock = $this->model->getStockByLocation($data['item_id'], $data['location_from']);
            if (!$stock || $stock['qty_on_hand'] < $data['qty']) {
                jsonResponse(false, 'Insufficient stock available');
            }

            $movementId = $this->model->addStockMovement($data);

            logAudit('stock_out', 'stock_movements', $movementId, null, $data);

            jsonResponse(true, 'Stock issued successfully', ['id' => $movementId]);

        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, 'Failed to issue stock: ' . $e->getMessage());
        }
    }

    /**
     * Stock Transfer
     */
    public function stockTransfer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method');
        }

        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }

        try {
            $data = [
                'item_id' => intval($_POST['item_id']),
                'location_from' => intval($_POST['location_from']),
                'location_to' => intval($_POST['location_to']),
                'qty' => intval($_POST['qty']),
                'rate' => floatval($_POST['rate'] ?? 0),
                'type' => 'transfer',
                'notes' => sanitize($_POST['notes'] ?? '')
            ];

            if ($data['qty'] <= 0) {
                jsonResponse(false, 'Quantity must be greater than 0');
            }

            if ($data['location_from'] === $data['location_to']) {
                jsonResponse(false, 'Source and destination locations cannot be same');
            }

            // Check if sufficient stock exists
            $stock = $this->model->getStockByLocation($data['item_id'], $data['location_from']);
            if (!$stock || $stock['qty_on_hand'] < $data['qty']) {
                jsonResponse(false, 'Insufficient stock in source location');
            }

            $movementId = $this->model->addStockMovement($data);

            logAudit('stock_transfer', 'stock_movements', $movementId, null, $data);

            jsonResponse(true, 'Stock transferred successfully', ['id' => $movementId]);

        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, 'Failed to transfer stock: ' . $e->getMessage());
        }
    }

    /**
     * Get stock movements
     */
    public function getMovements() {
        $itemId = intval($_GET['item_id'] ?? 0);
        $movements = $this->model->getStockMovements($itemId);
        jsonResponse(true, 'Movements retrieved', $movements);
    }

    /**
     * Export to CSV
     */
    public function exportCSV() {
        $items = $this->model->getItems();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['SKU', 'Name', 'Category', 'Unit', 'Qty on Hand', 'Rate', 'Value', 'Reorder Level']);

        // Data
        foreach ($items as $item) {
            fputcsv($output, [
                $item['sku'],
                $item['name'],
                $item['category_name'] ?? '',
                $item['unit'],
                $item['total_qty'],
                $item['standard_cost'],
                $item['total_value'],
                $item['reorder_level']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Get action buttons for item
     */
    private function getActionButtons($id) {
        return '
            <div class="btn-group btn-group-sm">
                <button class="btn btn-primary btn-edit" data-id="' . $id . '" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-success btn-stock-in" data-id="' . $id . '" title="Stock In">
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button class="btn btn-warning btn-stock-out" data-id="' . $id . '" title="Stock Out">
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button class="btn btn-danger btn-delete" data-id="' . $id . '" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
    }
}
