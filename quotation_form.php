<?php
/**
 * Create/Edit Quotation Form
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Quotation.php';

requireLogin();

$quotationModel = new Quotation();
$quoteId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$quotation = null;
$items = [];
$terms = [];

// Load existing quotation if editing
if ($quoteId) {
    $quotation = $quotationModel->getById($quoteId);
    if ($quotation) {
        $items = $quotationModel->getItems($quoteId);
        $terms = $quotationModel->getTerms($quoteId);
    }
}

// Generate next quote number for new quotations
$nextQuoteNo = $quoteId ? $quotation['quote_no'] : $quotationModel->generateQuoteNo();

$pageTitle = $quoteId ? 'Edit Quotation' : 'Create Quotation';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
        <div class="d-flex gap-2">
            <button class="btn btn-secondary" id="btnPrintSettings">
                <i class="fas fa-print"></i> Print Settings
            </button>
            <button class="btn btn-secondary" id="btnBack">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <button class="btn btn-success" id="btnSave">
                <i class="fas fa-check"></i> Save
            </button>
        </div>
    </div>

    <form id="quotationForm">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" id="quotationId" value="<?php echo $quoteId; ?>">
        <input type="hidden" name="subtotal" id="hiddenSubtotal" value="0">
        <input type="hidden" name="tax_amount" id="hiddenTaxAmount" value="0">
        <input type="hidden" name="discount_amount" id="hiddenDiscountAmount" value="0">
        <input type="hidden" name="extra_charges" id="hiddenExtraCharges" value="0">
        <input type="hidden" name="total_amount" id="hiddenTotalAmount" value="0">

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Basic Information Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="customer_name" id="customerName" 
                                           value="<?php echo htmlspecialchars($quotation['customer_name'] ?? ''); ?>" required>
                                    <button type="button" class="btn btn-outline-success" id="btnSearchCustomer">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="btnAddCustomer">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="customer_id" id="customerId" value="<?php echo $quotation['customer_id'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Copy from</label>
                                <select class="form-select" name="copy_from" id="copyFrom">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Party Details Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Party Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person" id="contactPerson"
                                       value="<?php echo htmlspecialchars($quotation['contact_person'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sales Credit</label>
                                <select class="form-select" name="sales_credit" id="salesCredit">
                                    <option value="None">None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <button type="button" class="btn btn-sm btn-success float-end" id="btnAddAddress">
                                <i class="fas fa-plus"></i> Click here to add an address
                            </button>
                            <textarea class="form-control" name="address" id="address" rows="2"><?php echo htmlspecialchars($quotation['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Shipping Address</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="same_as_billing" id="sameAsBilling" 
                                       <?php echo ($quotation['same_as_billing'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sameAsBilling">
                                    Same as Billing address
                                </label>
                            </div>
                            <textarea class="form-control" name="shipping_address" id="shippingAddress" rows="2"><?php echo htmlspecialchars($quotation['shipping_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Item List Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Item List</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-sm mb-3" id="btnAddItem">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">No.</th>
                                        <th style="width: 80px;">Image</th>
                                        <th>Item & Description</th>
                                        <th style="width: 100px;">HSN/SAC</th>
                                        <th style="width: 80px;">Qty</th>
                                        <th style="width: 80px;">Unit</th>
                                        <th style="width: 100px;">Rate (₹)</th>
                                        <th style="width: 80px;">Disc (%)</th>
                                        <th style="width: 100px;">Taxable (₹)</th>
                                        <th style="width: 80px;">CGST (%)</th>
                                        <th style="width: 80px;">SGST (%)</th>
                                        <th style="width: 120px;">Amt (₹)</th>
                                        <th style="width: 100px;">Lead Time</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $index => $item): ?>
                                            <tr data-row="<?php echo $index; ?>">
                                                <td><?php echo $index + 1; ?></td>
                                                <td><input type="file" class="form-control form-control-sm" name="items[<?php echo $index; ?>][image]"></td>
                                                <td><textarea class="form-control form-control-sm" name="items[<?php echo $index; ?>][item_description]" rows="2"><?php echo htmlspecialchars($item['item_description']); ?></textarea></td>
                                                <td><input type="text" class="form-control form-control-sm" name="items[<?php echo $index; ?>][hsn_sac]" value="<?php echo htmlspecialchars($item['hsn_sac']); ?>"></td>
                                                <td><input type="number" class="form-control form-control-sm item-qty" name="items[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>" step="0.01"></td>
                                                <td>
                                                    <select class="form-select form-select-sm" name="items[<?php echo $index; ?>][unit]">
                                                        <option value="Nos" <?php echo $item['unit'] == 'Nos' ? 'selected' : ''; ?>>Nos</option>
                                                        <option value="Kg" <?php echo $item['unit'] == 'Kg' ? 'selected' : ''; ?>>Kg</option>
                                                        <option value="Ltr" <?php echo $item['unit'] == 'Ltr' ? 'selected' : ''; ?>>Ltr</option>
                                                        <option value="Mtr" <?php echo $item['unit'] == 'Mtr' ? 'selected' : ''; ?>>Mtr</option>
                                                    </select>
                                                </td>
                                                <td><input type="number" class="form-control form-control-sm item-rate" name="items[<?php echo $index; ?>][rate]" value="<?php echo $item['rate']; ?>" step="0.01"></td>
                                                <td><input type="number" class="form-control form-control-sm item-discount" name="items[<?php echo $index; ?>][discount_percent]" value="<?php echo $item['discount_percent']; ?>" step="0.01"></td>
                                                <td><input type="number" class="form-control form-control-sm item-taxable" name="items[<?php echo $index; ?>][taxable_amount]" value="<?php echo $item['taxable_amount']; ?>" readonly></td>
                                                <td><input type="number" class="form-control form-control-sm item-cgst" name="items[<?php echo $index; ?>][cgst_percent]" value="<?php echo $item['cgst_percent']; ?>" step="0.01"></td>
                                                <td><input type="number" class="form-control form-control-sm item-sgst" name="items[<?php echo $index; ?>][sgst_percent]" value="<?php echo $item['sgst_percent']; ?>" step="0.01"></td>
                                                <td><input type="number" class="form-control form-control-sm item-amount" name="items[<?php echo $index; ?>][amount]" value="<?php echo $item['amount']; ?>" readonly></td>
                                                <td><input type="text" class="form-control form-control-sm" name="items[<?php echo $index; ?>][lead_time]" value="<?php echo htmlspecialchars($item['lead_time'] ?? ''); ?>"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals Summary -->
                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Total:</strong></td>
                                        <td class="text-end">₹ <span id="displayTotal">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Grand Total:</strong></td>
                                        <td class="text-end"><strong>₹ <span id="displayGrandTotal">0.00</span></strong></td>
                                    </tr>
                                </table>
                                <div class="text-end">
                                    <button type="button" class="btn btn-success btn-sm" id="btnAddExtraCharge">
                                        <i class="fas fa-plus"></i> Add Extra Charge
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" id="btnAddDiscount">
                                        <i class="fas fa-plus"></i> Add Discount
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Terms & Conditions</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-sm mb-2" id="btnAddTerm">
                            <i class="fas fa-plus"></i> Add Term / Condition
                        </button>
                        <div id="termsContainer">
                            <?php if (!empty($terms)): ?>
                                <?php foreach ($terms as $index => $term): ?>
                                    <div class="input-group mb-2 term-row">
                                        <textarea class="form-control" name="terms[]" rows="2"><?php echo htmlspecialchars($term['term_condition']); ?></textarea>
                                        <button type="button" class="btn btn-danger btn-remove-term"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" id="notes" rows="3"><?php echo htmlspecialchars($quotation['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Bank Details Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Bank Details</h6>
                    </div>
                    <div class="card-body">
                        <select class="form-select mb-2" name="bank_details" id="bankDetails">
                            <option value="">--Select--</option>
                        </select>
                    </div>
                </div>

                <!-- Upload File Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Upload File</h6>
                    </div>
                    <div class="card-body">
                        <input type="file" class="form-control" name="upload_file" id="uploadFile">
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Document Details Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Document Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Quotation No.</label>
                            <input type="text" class="form-control" name="quote_no" id="quoteNo" 
                                   value="<?php echo htmlspecialchars($nextQuoteNo); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control" name="reference" id="reference"
                                   value="<?php echo htmlspecialchars($quotation['reference'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quotation Date</label>
                            <input type="date" class="form-control" name="quotation_date" id="quotationDate"
                                   value="<?php echo $quotation['quotation_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valid till</label>
                            <input type="date" class="form-control" name="valid_till" id="validTill"
                                   value="<?php echo $quotation['valid_till'] ?? date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" id="branchId">
                                <option value="">Select Branch</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Next Actions Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Next Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="save_as_template" id="saveAsTemplate">
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
                                Share by WhatsApp
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="print_after_saving" id="printAfterSaving">
                            <label class="form-check-label" for="printAfterSaving">
                                Print Document after Saving
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="alert_on_opening" id="alertOnOpening">
                            <label class="form-check-label" for="alertOnOpening">
                                Alert me on Opening
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" id="btnSaveQuotation">
                        <i class="fas fa-check"></i> Save
                    </button>
                    <button type="button" class="btn btn-success" id="btnSaveAndEnterAnother">
                        <i class="fas fa-plus"></i> Save & Enter Another
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
$customJS = "
<script>
    window.CSRF_TOKEN_NAME = '" . CSRF_TOKEN_NAME . "';
    window.QUOTATION_ID = " . ($quoteId ?? 0) . ";
</script>
<script src='" . BASE_URL . "/assets/js/quotation_form.js'></script>
";
include __DIR__ . '/includes/footer.php';
?>
