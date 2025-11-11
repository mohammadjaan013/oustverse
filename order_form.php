<?php
/**
 * Order Form Page
 * Create/Edit Sale Orders
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'models/Order.php';

requireLogin();

$orderModel = new Order();
$orderId = $_GET['id'] ?? null;
$mode = $_GET['mode'] ?? 'edit'; // edit or view
$order = null;
$orderItems = [];
$orderTerms = [];

if ($orderId) {
    $order = $orderModel->getById($orderId);
    if ($order) {
        $orderItems = $orderModel->getItems($orderId);
        $orderTerms = $orderModel->getTerms($orderId);
        $pageTitle = $mode == 'view' ? 'View Sale Order' : 'Edit Sale Order';
    } else {
        header('Location: orders.php');
        exit;
    }
} else {
    $pageTitle = 'Create Sale Order';
    // Generate new order number
    $order = ['order_no' => $orderModel->generateOrderNo()];
}

$isViewMode = $mode == 'view';

include 'includes/header.php';
?>

<style>
.item-row {
    background: #f8f9fa;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 3px solid #0d6efd;
}
.item-row:hover {
    background: #e9ecef;
}
.remove-item-btn {
    cursor: pointer;
}
.table-items th {
    background: #0d6efd;
    color: white;
    font-size: 0.85rem;
    padding: 8px 5px;
}
.table-items td {
    padding: 5px;
}
.table-items input, .table-items select, .table-items textarea {
    font-size: 0.85rem;
    padding: 4px 8px;
}
.form-label {
    font-weight: 600;
    font-size: 0.9rem;
}
.card-header {
    background: #0d6efd;
    color: white;
    font-weight: 600;
}
</style>

<div class="container-fluid">
    <!-- Back Button -->
    <div class="row mb-3">
        <div class="col">
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        <div class="col-auto">
            <?php if (!$isViewMode): ?>
                <button type="button" class="btn btn-primary" id="saveOrderBtn">
                    <i class="fas fa-save"></i> Save Order
                </button>
                <button type="button" class="btn btn-success" id="saveAndPrintBtn">
                    <i class="fas fa-print"></i> Save & Print
                </button>
            <?php else: ?>
                <a href="order_form.php?id=<?php echo $orderId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Order
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form id="orderForm" <?php echo $isViewMode ? 'disabled' : ''; ?>>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="order_id" value="<?php echo $orderId ?? ''; ?>">
        <input type="hidden" name="print_after_saving" id="printAfterSaving" value="0">

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Order Details Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-file-alt"></i> Order Details
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Order No. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="order_no" 
                                       value="<?php echo htmlspecialchars($order['order_no'] ?? ''); ?>" 
                                       readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reference</label>
                                <input type="text" class="form-control" name="reference" 
                                       value="<?php echo htmlspecialchars($order['reference'] ?? ''); ?>"
                                       <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Order Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="order_type" required <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                    <option value="sales" <?php echo ($order['order_type'] ?? 'sales') == 'sales' ? 'selected' : ''; ?>>Sales</option>
                                    <option value="service" <?php echo ($order['order_type'] ?? '') == 'service' ? 'selected' : ''; ?>>Service</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_name" 
                                       value="<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>" 
                                       required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer P.O. No.</label>
                                <input type="text" class="form-control" name="customer_po_no" 
                                       value="<?php echo htmlspecialchars($order['customer_po_no'] ?? ''); ?>"
                                       <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person" 
                                       value="<?php echo htmlspecialchars($order['contact_person'] ?? ''); ?>"
                                       <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sales Credit</label>
                                <input type="text" class="form-control" name="sales_credit" 
                                       value="<?php echo htmlspecialchars($order['sales_credit'] ?? 'None'); ?>"
                                       <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="order_date" 
                                       value="<?php echo $order['order_date'] ?? date('Y-m-d'); ?>" 
                                       required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="due_date" 
                                       value="<?php echo $order['due_date'] ?? ''; ?>" 
                                       required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Sales Executive</label>
                                <select class="form-select" name="executive_id" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                    <option value="">Select Executive</option>
                                    <!-- Will be populated via AJAX or PHP -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Responsible Executive</label>
                                <select class="form-select" name="responsible_id" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                    <option value="">Select Executive</option>
                                    <!-- Will be populated via AJAX or PHP -->
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-map-marker-alt"></i> Address Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Billing Address</label>
                                <textarea class="form-control" name="billing_address" rows="3" 
                                          <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($order['billing_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shipping Address</label>
                                <textarea class="form-control" name="shipping_address" rows="3" 
                                          <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?></textarea>
                                <?php if (!$isViewMode): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="sameAsBilling" 
                                           <?php echo ($order['same_as_billing'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sameAsBilling">
                                        Same as Billing Address
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Card -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list"></i> Order Items</span>
                        <?php if (!$isViewMode): ?>
                        <button type="button" class="btn btn-sm btn-light" id="addItemBtn">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-items mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 25%;">Item Description</th>
                                        <th style="width: 10%;">HSN/SAC</th>
                                        <th style="width: 8%;">Qty</th>
                                        <th style="width: 8%;">Unit</th>
                                        <th style="width: 10%;">Rate</th>
                                        <th style="width: 8%;">Disc %</th>
                                        <th style="width: 8%;">CGST %</th>
                                        <th style="width: 8%;">SGST %</th>
                                        <th style="width: 10%;">Amount</th>
                                        <?php if (!$isViewMode): ?>
                                        <th style="width: 5%;"></th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php if (!empty($orderItems)): ?>
                                        <?php foreach ($orderItems as $index => $item): ?>
                                            <tr class="item-row" data-index="<?php echo $index; ?>">
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <textarea class="form-control item-description" name="items[<?php echo $index; ?>][description]" rows="1" required <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($item['item_description']); ?></textarea>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="items[<?php echo $index; ?>][hsn_sac]" value="<?php echo htmlspecialchars($item['hsn_sac'] ?? ''); ?>" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-quantity" name="items[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>" step="0.01" required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="items[<?php echo $index; ?>][unit]" value="<?php echo htmlspecialchars($item['unit'] ?? 'nos'); ?>" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-rate" name="items[<?php echo $index; ?>][rate]" value="<?php echo $item['rate']; ?>" step="0.01" required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-discount" name="items[<?php echo $index; ?>][discount_percent]" value="<?php echo $item['discount_percent'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-cgst" name="items[<?php echo $index; ?>][cgst_percent]" value="<?php echo $item['cgst_percent'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-sgst" name="items[<?php echo $index; ?>][sgst_percent]" value="<?php echo $item['sgst_percent'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-amount" name="items[<?php echo $index; ?>][amount]" value="<?php echo $item['amount']; ?>" readonly>
                                                </td>
                                                <?php if (!$isViewMode): ?>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions Card -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-contract"></i> Terms & Conditions</span>
                        <?php if (!$isViewMode): ?>
                        <button type="button" class="btn btn-sm btn-light" id="addTermBtn">
                            <i class="fas fa-plus"></i> Add Term
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div id="termsContainer">
                            <?php if (!empty($orderTerms)): ?>
                                <?php foreach ($orderTerms as $index => $term): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><?php echo $index + 1; ?>.</span>
                                        <input type="text" class="form-control" name="terms[]" value="<?php echo htmlspecialchars($term); ?>" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                        <?php if (!$isViewMode): ?>
                                        <button type="button" class="btn btn-danger remove-term-btn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-sticky-note"></i> Notes
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" 
                                  <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary -->
            <div class="col-md-4">
                <!-- Totals Card -->
                <div class="card mb-3 sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <i class="fas fa-calculator"></i> Order Summary
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-end">
                                    <strong id="displaySubtotal">₹0.00</strong>
                                    <input type="hidden" name="subtotal" id="subtotal" value="0">
                                </td>
                            </tr>
                            <tr>
                                <td>Discount:</td>
                                <td class="text-end">
                                    <strong id="displayDiscount">₹0.00</strong>
                                    <input type="hidden" name="discount_amount" id="discountAmount" value="0">
                                </td>
                            </tr>
                            <tr>
                                <td>Tax (CGST + SGST):</td>
                                <td class="text-end">
                                    <strong id="displayTax">₹0.00</strong>
                                    <input type="hidden" name="tax_amount" id="taxAmount" value="0">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="extraCharges">Extra Charges:</label>
                                </td>
                                <td class="text-end">
                                    <input type="number" class="form-control form-control-sm text-end" 
                                           name="extra_charges" id="extraCharges" 
                                           value="<?php echo $order['extra_charges'] ?? 0; ?>" 
                                           step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                </td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Total Amount:</strong></td>
                                <td class="text-end">
                                    <strong id="displayTotal">₹0.00</strong>
                                    <input type="hidden" name="total_amount" id="totalAmount" value="0">
                                </td>
                            </tr>
                        </table>

                        <?php if (!$isViewMode): ?>
                        <hr>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="update_by_email" id="updateByEmail" 
                                   <?php echo ($order['update_by_email'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="updateByEmail">
                                Update by Email
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="update_by_whatsapp" id="updateByWhatsapp" 
                                   <?php echo ($order['update_by_whatsapp'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="updateByWhatsapp">
                                Update by WhatsApp
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Card (if editing) -->
                <?php if ($orderId): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Order Status
                    </div>
                    <div class="card-body">
                        <?php if (!$isViewMode): ?>
                        <select class="form-select mb-2" name="status">
                            <option value="pending" <?php echo ($order['status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo ($order['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo ($order['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo ($order['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($order['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <?php else: ?>
                        <p class="mb-0">
                            <span class="badge bg-<?php 
                                echo match($order['status'] ?? 'pending') {
                                    'confirmed' => 'info',
                                    'processing' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'warning'
                                };
                            ?>"><?php echo ucfirst($order['status'] ?? 'Pending'); ?></span>
                        </p>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <small class="text-muted">
                            <strong>Commitment:</strong> 
                            <span class="badge bg-<?php 
                                echo match($order['commitment_status'] ?? 'future') {
                                    'overdue' => 'danger',
                                    'today' => 'warning',
                                    'tomorrow' => 'info',
                                    default => 'secondary'
                                };
                            ?>"><?php echo ucfirst($order['commitment_status'] ?? 'Future'); ?></span>
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/order_form.js"></script>
