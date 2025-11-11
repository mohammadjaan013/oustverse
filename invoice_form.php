<?php
/**
 * Invoice Form Page
 * Create/Edit Invoices
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'models/Invoice.php';

requireLogin();

$invoiceModel = new Invoice();
$invoiceId = $_GET['id'] ?? null;
$mode = $_GET['mode'] ?? 'edit'; // edit or view
$invoice = null;
$invoiceItems = [];
$invoiceTerms = [];

if ($invoiceId) {
    $invoice = $invoiceModel->getById($invoiceId);
    if ($invoice) {
        $invoiceItems = $invoiceModel->getItems($invoiceId);
        $invoiceTerms = $invoiceModel->getTerms($invoiceId);
        $pageTitle = $mode == 'view' ? 'View Invoice' : 'Edit Invoice';
    } else {
        header('Location: invoices.php');
        exit;
    }
} else {
    $pageTitle = 'Create Invoice';
    // Generate new invoice number
    $invoice = ['invoice_no' => $invoiceModel->generateInvoiceNo()];
}

$isViewMode = $mode == 'view';

include 'includes/header.php';
?>

<style>
.section-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.section-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 15px;
    color: #333;
}
.form-label {
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 5px;
}
.item-table th {
    background: #f8f9fa;
    font-size: 0.85rem;
    padding: 8px 5px;
    border: 1px solid #dee2e6;
}
.item-table td {
    padding: 5px;
    border: 1px solid #dee2e6;
}
.item-table input, .item-table select, .item-table textarea {
    font-size: 0.85rem;
    padding: 4px 8px;
    border: 1px solid #ced4da;
}
.total-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
.btn-add-row {
    background: #d4edda;
    color: #155724;
    border: 1px dashed #28a745;
    padding: 5px 15px;
    font-size: 0.9rem;
}
.btn-add-row:hover {
    background: #c3e6cb;
}
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col">
            <h4><?php echo $pageTitle; ?></h4>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print"></i> Print Settings
            </button>
            <?php if (!$isViewMode): ?>
                <a href="invoices.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="button" class="btn btn-success btn-sm" id="saveInvoiceBtn">
                    <i class="fas fa-check"></i> Save
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form id="invoiceForm" <?php echo $isViewMode ? 'disabled' : ''; ?>>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="invoice_id" value="<?php echo $invoiceId ?? ''; ?>">

        <!-- Basic Information -->
        <div class="section-card">
            <div class="section-title">Basic Information</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Type :</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" value="party_invoice" 
                                       <?php echo ($invoice['invoice_type'] ?? 'party_invoice') == 'party_invoice' ? 'checked' : ''; ?>
                                       <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <label class="form-check-label">Party Invoice</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" value="cash_memo" 
                                       <?php echo ($invoice['invoice_type'] ?? '') == 'cash_memo' ? 'checked' : ''; ?>
                                       <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <label class="form-check-label">Cash Memo</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" value="inter_state_transfer" 
                                       <?php echo ($invoice['invoice_type'] ?? '') == 'inter_state_transfer' ? 'checked' : ''; ?>
                                       <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <label class="form-check-label">Inter-State Transfer</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer :</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="customer_name" 
                                   value="<?php echo htmlspecialchars($invoice['customer_name'] ?? ''); ?>" 
                                   required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                            <?php if (!$isViewMode): ?>
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-outline-success" type="button">
                                <i class="fas fa-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Copy from :</label>
                        <select class="form-select" name="copy_from" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                            <option value="">None</option>
                            <!-- Can be populated with existing invoices -->
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Branch :</label>
                        <select class="form-select" name="branch_id" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                            <option value="">Own - Maharashtra (27AACCO7731K1Z5)</option>
                            <!-- Can be populated with branches -->
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Party Details -->
            <div class="col-md-8">
                <div class="section-card">
                    <div class="section-title">Party Details</div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person :</label>
                            <input type="text" class="form-control" name="contact_person" 
                                   value="<?php echo htmlspecialchars($invoice['contact_person'] ?? ''); ?>"
                                   <?php echo $isViewMode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sales Credit :</label>
                            <select class="form-select" name="sales_credit" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <option value="None" <?php echo ($invoice['sales_credit'] ?? 'None') == 'None' ? 'selected' : ''; ?>>None</option>
                                <!-- Can add sales credit options -->
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Address :</label>
                            <?php if (!$isViewMode): ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-success" id="addBillingAddressBtn">
                                    <i class="fas fa-plus"></i> Click here to add an address
                                </button>
                            </div>
                            <?php endif; ?>
                            <textarea class="form-control" name="billing_address" rows="3"
                                      <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($invoice['billing_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipping Address :</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="same_as_billing" id="sameAsBilling" 
                                       <?php echo ($invoice['same_as_billing'] ?? 0) ? 'checked' : ''; ?>
                                       <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="sameAsBilling">
                                    Same as Billing address
                                </label>
                            </div>
                            <textarea class="form-control" name="shipping_address" rows="3"
                                      <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($invoice['shipping_address'] ?? ''); ?></textarea>
                            
                            <label class="form-label mt-2">Shipping Details :</label>
                            <textarea class="form-control" name="shipping_details" rows="2"
                                      <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($invoice['shipping_details'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="col-md-4">
                <div class="section-card">
                    <div class="section-title">Document Details</div>
                    
                    <div class="mb-3">
                        <label class="form-label">Invoice No. :</label>
                        <input type="text" class="form-control" name="invoice_no" 
                               value="<?php echo htmlspecialchars($invoice['invoice_no'] ?? ''); ?>" 
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference :</label>
                        <input type="text" class="form-control" name="reference" 
                               value="<?php echo htmlspecialchars($invoice['reference'] ?? ''); ?>"
                               <?php echo $isViewMode ? 'readonly' : ''; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Invoice Date :</label>
                        <input type="date" class="form-control" name="invoice_date" 
                               value="<?php echo $invoice['invoice_date'] ?? date('Y-m-d'); ?>" 
                               required <?php echo $isViewMode ? 'readonly' : ''; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date :</label>
                        <input type="date" class="form-control" name="due_date" 
                               value="<?php echo $invoice['due_date'] ?? date('Y-m-d'); ?>"
                               <?php echo $isViewMode ? 'readonly' : ''; ?>>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item List -->
        <div class="section-card">
            <div class="section-title">Item List</div>
            <div class="table-responsive">
                <table class="table item-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 4%;">No.</th>
                            <th style="width: 20%;">Item & Description</th>
                            <th style="width: 8%;">HSN/SAC</th>
                            <th style="width: 6%;">Qty</th>
                            <th style="width: 6%;">Unit</th>
                            <th style="width: 8%;">Rate (₹)</th>
                            <th style="width: 8%;">Discount (₹)</th>
                            <th style="width: 10%;">Taxable (₹)</th>
                            <th style="width: 7%;">CGST (₹)</th>
                            <th style="width: 7%;">SGST (₹)</th>
                            <th style="width: 10%;">Amt (₹)</th>
                            <?php if (!$isViewMode): ?>
                            <th style="width: 4%;"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <?php if (!empty($invoiceItems)): ?>
                            <?php foreach ($invoiceItems as $index => $item): ?>
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
                                        <input type="number" class="form-control item-discount-amt" name="items[<?php echo $index; ?>][discount_amount]" value="<?php echo $item['discount_amount'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-taxable" name="items[<?php echo $index; ?>][taxable_amount]" value="<?php echo $item['taxable_amount']; ?>" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-cgst" name="items[<?php echo $index; ?>][cgst_amount]" value="<?php echo $item['cgst_amount'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-sgst" name="items[<?php echo $index; ?>][sgst_amount]" value="<?php echo $item['sgst_amount'] ?? 0; ?>" step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
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
            <?php if (!$isViewMode): ?>
            <div class="mt-2">
                <button type="button" class="btn btn-add-row" id="addItemBtn">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Terms & Conditions -->
        <div class="section-card">
            <div class="section-title">Terms & Conditions</div>
            <div id="termsContainer">
                <?php if (!empty($invoiceTerms)): ?>
                    <?php foreach ($invoiceTerms as $index => $term): ?>
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
            <?php if (!$isViewMode): ?>
            <button type="button" class="btn btn-add-row mt-2" id="addTermBtn">
                <i class="fas fa-plus"></i> Add Term / Condition
            </button>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Left Column: Notes & Bank Details -->
            <div class="col-md-6">
                <div class="section-card">
                    <div class="section-title">Notes</div>
                    <textarea class="form-control" name="notes" rows="4" 
                              <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
                </div>

                <div class="section-card">
                    <div class="section-title">Bank Details</div>
                    <select class="form-select" name="bank_details" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                        <option value="">--Select--</option>
                        <!-- Can be populated with bank accounts -->
                    </select>
                </div>

                <div class="section-card">
                    <div class="section-title">Payment Recovery</div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Update Recovery Amt</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="recovery_amount" 
                                       value="<?php echo $invoice['recovery_amount'] ?? 0; ?>" 
                                       step="0.01" <?php echo $isViewMode ? 'readonly' : ''; ?>>
                                <span class="input-group-text">(Add ₹ <input type="number" class="form-control-sm border-0" style="width:60px;" value="0">)</span>
                            </div>
                            <label class="form-label mt-2">Internal Notes</label>
                            <textarea class="form-control" name="internal_notes_recovery" rows="2"
                                      <?php echo $isViewMode ? 'readonly' : ''; ?>></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Update Invoice Status</label>
                            <select class="form-select" name="payment_status" <?php echo $isViewMode ? 'disabled' : ''; ?>>
                                <option value="unpaid" <?php echo ($invoice['payment_status'] ?? 'unpaid') == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="partial" <?php echo ($invoice['payment_status'] ?? '') == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                <option value="paid" <?php echo ($invoice['payment_status'] ?? '') == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="overdue" <?php echo ($invoice['payment_status'] ?? '') == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                            <label class="form-label mt-2">Internal Notes</label>
                            <textarea class="form-control" name="internal_notes" rows="2"
                                      <?php echo $isViewMode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($invoice['internal_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <?php if (!$isViewMode): ?>
                <div class="section-card">
                    <div class="section-title">Next Actions</div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_template" id="saveAsTemplate">
                        <label class="form-check-label" for="saveAsTemplate">
                            Save as Template
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="share_by_email" id="shareByEmail">
                        <label class="form-check-label" for="shareByEmail">
                            Share by Email
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="share_by_whatsapp" id="shareByWhatsapp">
                        <label class="form-check-label" for="shareByWhatsapp">
                            Share by Whatsapp
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="print_after_saving" id="printAfterSaving">
                        <label class="form-check-label" for="printAfterSaving">
                            Print Document after Saving
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Totals -->
            <div class="col-md-6">
                <div class="section-card">
                    <div class="total-box">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total :</span>
                            <strong id="displayTotal">₹ 0.00</strong>
                            <input type="hidden" name="subtotal" id="subtotal" value="0">
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <?php if (!$isViewMode): ?>
                            <button type="button" class="btn btn-sm btn-success me-2" id="addExtraChargeBtn">
                                <i class="fas fa-plus"></i> Add Extra Charge
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" id="addDiscountBtn">
                                <i class="fas fa-plus"></i> Add Discount
                            </button>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h5>Grand Total :</h5>
                            <h5 id="displayGrandTotal">₹ 0.00</h5>
                            <input type="hidden" name="total_amount" id="totalAmount" value="0">
                            <input type="hidden" name="taxable_amount" id="taxableAmount" value="0">
                            <input type="hidden" name="tax_amount" id="taxAmount" value="0">
                            <input type="hidden" name="discount_amount" id="discountAmount" value="0">
                            <input type="hidden" name="extra_charges" id="extraCharges" value="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$isViewMode): ?>
        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <button type="button" class="btn btn-success btn-lg" id="saveBtn">
                    <i class="fas fa-check"></i> Save
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="saveAndEnterAnotherBtn">
                    <i class="fas fa-plus"></i> Save & Enter Another
                </button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/invoice_form.js"></script>
