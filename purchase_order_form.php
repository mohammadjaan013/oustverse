<?php
/**
 * Purchase Order Form (Create/Edit)
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'models/PurchaseOrder.php';
require_once 'models/Supplier.php';
require_once 'models/Product.php';
require_once 'controllers/PurchaseOrderController.php';

// Check authentication
requireLogin();

$controller = new PurchaseOrderController();
$poId = $_GET['id'] ?? 0;
$po = null;
$poItems = [];

// If editing, load existing PO
if ($poId) {
    $poModel = new PurchaseOrder();
    $po = $poModel->getById($poId);
    $poItems = $poModel->getItems($poId);
}

// Get suppliers for dropdown
$supplierModel = new Supplier();
$suppliers = $supplierModel->getAll();

// Get products for dropdown
$productModel = new Product();
$products = $productModel->getAll();

$pageTitle = $poId ? 'Edit Purchase Order' : 'Create Purchase Order';
require_once 'includes/header.php';
?>

<style>
.item-row {
    border-bottom: 1px solid #dee2e6;
    padding: 10px 0;
}
.item-row:last-child {
    border-bottom: none;
}
.form-label-sm {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}
.summary-box {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
</style>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo $pageTitle; ?></h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Settings
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='purchase_orders.php'">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-success" onclick="savePurchaseOrder()">
                    <i class="fas fa-check"></i> Save
                </button>
            </div>
        </div>

        <form id="poForm">
            <input type="hidden" name="id" id="poId" value="<?php echo $poId; ?>">
            
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" name="supplier_id" id="supplierId" required onchange="loadSupplierDetails()">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                                <?php echo ($po && $po['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" onclick="searchSupplier()" title="Search">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="addNewSupplier()" title="Add New">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" id="branchId">
                                <option value="1" selected>Own - Maharashtra (27AACCO7731K1Z5)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Copy from</label>
                            <select class="form-select" name="copy_from" id="copyFrom" onchange="copyFromPO()">
                                <option value="">None</option>
                                <?php
                                // Get recent POs for copying
                                $recentPOsQuery = "SELECT id, po_no FROM purchase_orders ORDER BY date DESC LIMIT 10";
                                $recentPOs = getDB()->query($recentPOsQuery)->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($recentPOs as $recentPO):
                                ?>
                                    <option value="<?php echo $recentPO['id']; ?>">
                                        <?php echo htmlspecialchars($recentPO['po_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Party Details -->
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Party Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label-sm">Contact Person</label>
                                    <input type="text" class="form-control form-control-sm" name="contact_person" id="contactPerson" 
                                           value="<?php echo htmlspecialchars($po['contact_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-sm">Source Address</label>
                                    <textarea class="form-control form-control-sm" name="source_address" id="sourceAddress" rows="3" 
                                              readonly><?php echo htmlspecialchars($po['address'] ?? ''); ?></textarea>
                                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addAddress()">
                                        <i class="fas fa-plus"></i> Click here to add an address
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-sm">Shipping Details</label>
                                    <textarea class="form-control form-control-sm" name="shipping_details" id="shippingDetails" rows="3"
                                              placeholder="Enter shipping address"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="col-md-5">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Document Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label-sm">PO No.</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="po_no" id="poNo" 
                                               value="<?php echo htmlspecialchars($po['po_no'] ?? ''); ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generatePoNumber()">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label-sm">Reference</label>
                                    <input type="text" class="form-control form-control-sm" name="reference" id="reference">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label-sm">PO Date</label>
                                    <input type="date" class="form-control form-control-sm" name="date" id="poDate" 
                                           value="<?php echo $po['date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label-sm">Due Date</label>
                                    <input type="date" class="form-control form-control-sm" name="expected_delivery_date" 
                                           id="dueDate" value="<?php echo $po['expected_delivery_date'] ?? date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item List -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Item List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No.</th>
                                    <th style="width: 25%;">Item & Description</th>
                                    <th style="width: 12%;">HSN/SAC</th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 8%;">Unit</th>
                                    <th style="width: 10%;">Rate (₹)</th>
                                    <th style="width: 8%;">Discount (₹)</th>
                                    <th style="width: 8%;">Taxable (₹)</th>
                                    <th style="width: 8%;">CGST (₹)</th>
                                    <th style="width: 8%;">SGST (₹)</th>
                                    <th style="width: 10%;">Amt (₹)</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <?php if (empty($poItems)): ?>
                                <tr class="item-row" data-row="1">
                                    <td>1</td>
                                    <td>
                                        <select class="form-select form-select-sm product-select" name="items[0][product_id]" onchange="loadProductDetails(this, 0)">
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['id']; ?>" 
                                                        data-hsn="<?php echo htmlspecialchars($product['hsn_code'] ?? ''); ?>"
                                                        data-unit="<?php echo htmlspecialchars($product['unit'] ?? 'PCS'); ?>"
                                                        data-price="<?php echo $product['purchase_price'] ?? 0; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1" name="items[0][description]" placeholder="Description">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[0][hsn]" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][quantity]" value="1" step="0.01" onchange="calculateRow(0)"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[0][unit]" value="PCS" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][unit_price]" value="0" step="0.01" onchange="calculateRow(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][discount_amount]" value="0" step="0.01" onchange="calculateRow(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][taxable]" value="0" step="0.01" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][cgst]" value="0" step="0.01" onchange="calculateRow(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][sgst]" value="0" step="0.01" onchange="calculateRow(0)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[0][total_amount]" value="0" step="0.01" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php else: 
                                    foreach ($poItems as $index => $item): ?>
                                <tr class="item-row" data-row="<?php echo $index + 1; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <select class="form-select form-select-sm product-select" name="items[<?php echo $index; ?>][product_id]">
                                            <option value="<?php echo $item['product_id']; ?>" selected>
                                                <?php echo htmlspecialchars($item['product_name'] ?? ''); ?>
                                            </option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1" name="items[<?php echo $index; ?>][description]" 
                                               value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[<?php echo $index; ?>][hsn]" 
                                               value="<?php echo htmlspecialchars($item['sku'] ?? ''); ?>" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][quantity]" 
                                               value="<?php echo $item['quantity']; ?>" step="0.01" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="items[<?php echo $index; ?>][unit]" 
                                               value="<?php echo htmlspecialchars($item['unit'] ?? 'PCS'); ?>" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][unit_price]" 
                                               value="<?php echo $item['unit_price']; ?>" step="0.01" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][discount_amount]" 
                                               value="<?php echo $item['discount_amount']; ?>" step="0.01" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][taxable]" 
                                               value="<?php echo $item['unit_price'] * $item['quantity'] - $item['discount_amount']; ?>" step="0.01" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][cgst]" 
                                               value="<?php echo $item['tax_amount'] / 2; ?>" step="0.01" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][sgst]" 
                                               value="<?php echo $item['tax_amount'] / 2; ?>" step="0.01" onchange="calculateRow(<?php echo $index; ?>)"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="items[<?php echo $index; ?>][total_amount]" 
                                               value="<?php echo $item['total_amount']; ?>" step="0.01" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                    
                    <!-- Summary -->
                    <div class="row mt-4">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Total:</strong>
                                    <span id="summaryTotal">₹ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Grand Total:</strong>
                                    <strong id="summaryGrandTotal">₹ 0.00</strong>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-success me-2" onclick="addExtraCharge()">
                                    <i class="fas fa-plus"></i> Add Extra Charge
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="addDiscount()">
                                    <i class="fas fa-plus"></i> Add Discount
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Terms & Conditions</h6>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="terms_conditions" id="termsConditions" rows="3" 
                              placeholder="Enter terms and conditions"><?php echo htmlspecialchars($po['notes'] ?? ''); ?></textarea>
                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addTermCondition()">
                        <i class="fas fa-plus"></i> Add Term / Condition
                    </button>
                </div>
            </div>

            <!-- Notes -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Notes</h6>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="notes" id="notes" rows="3" 
                              placeholder="Add any internal notes"></textarea>
                </div>
            </div>

            <!-- Next Actions -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Next Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="save_as_template" id="saveAsTemplate">
                                <label class="form-check-label" for="saveAsTemplate">
                                    Save as Template
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="share_email" id="shareEmail">
                                <label class="form-check-label" for="shareEmail">
                                    Share by Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="share_whatsapp" id="shareWhatsapp">
                                <label class="form-check-label" for="shareWhatsapp">
                                    Share by Whatsapp
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="print_after_save" id="printAfterSave">
                                <label class="form-check-label" for="printAfterSave">
                                    Print Document after Saving
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <button type="button" class="btn btn-success" onclick="savePurchaseOrder()">
                        <i class="fas fa-check"></i> Save
                    </button>
                    <button type="button" class="btn btn-primary" onclick="savePurchaseOrder(true)">
                        <i class="fas fa-save"></i> Save & Enter Another
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='purchase_orders.php'">
                    Cancel
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="assets/js/purchase_order_form.js"></script>

<script>
// Load PO number on page load if creating new
$(document).ready(function() {
    <?php if (!$poId): ?>
    generatePoNumber();
    <?php endif; ?>
    
    // Calculate totals if editing
    <?php if ($poId): ?>
    calculateTotal();
    <?php endif; ?>
});
</script>
