<?php
/**
 * Supplier Invoice Form Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check authentication
requireLogin();

$invoiceId = $_GET['id'] ?? null;
$invoiceType = $_GET['type'] ?? 'supplier_invoice';
$isEdit = isset($_GET['edit']) || $invoiceId;
$showInterStateModal = ($invoiceType === 'inter_state_transfer' && !$invoiceId);

$pageTitle = $invoiceId ? 'Edit Supplier Invoice' : 'Create Supplier Invoice';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Create Supplier Invoice</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" onclick="window.location.href='purchases.php'">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-success" id="saveInvoice">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>

        <form id="invoiceForm">
            <input type="hidden" id="invoice_id" name="id" value="<?php echo $invoiceId ?? ''; ?>">
            
            <!-- Basic Information -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Type :</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="typeSupplierInvoice" value="supplier_invoice" <?php echo $invoiceType == 'supplier_invoice' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="typeSupplierInvoice">
                                            Supplier Invoice
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="typeInterState" value="inter_state_transfer" <?php echo $invoiceType == 'inter_state_transfer' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="typeInterState">
                                            Inter-State Transfer
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="supplierField" style="<?php echo $invoiceType === 'inter_state_transfer' ? 'display: none;' : ''; ?>">
                                <label class="form-label">Supplier :</label>
                                <div class="input-group">
                                    <select class="form-select" id="supplier_id" name="supplier_id" style="width: 80%;">
                                        <option value="">Select Supplier</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" id="searchSupplier">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-success" type="button" onclick="window.open('suppliers.php', '_blank')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="sourceBranchField" style="<?php echo $invoiceType === 'inter_state_transfer' ? '' : 'display: none;'; ?>">
                                <label class="form-label">Source Branch :</label>
                                <select class="form-select" id="source_branch_transfer" name="source_branch_transfer">
                                    <option value="">Select</option>
                                    <option value="Main Branch">Main Branch</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Copy from :</label>
                                <select class="form-select" id="copy_from" name="copy_from">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3" id="destinationBranchField">
                                <label class="form-label">Branch :</label>
                                <select class="form-select" id="branch" name="branch">
                                    <option value="">Own - Maharashtra (27AACCO7731K1Z5)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="row">
                <!-- Left Column - Party Details -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Party Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Source Address :</label>
                                <button type="button" class="btn btn-sm btn-success" id="addSourceAddress">
                                    <i class="fas fa-plus"></i> Click here to add an address.
                                </button>
                                <div id="sourceAddressDisplay" class="mt-2 text-muted small" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Document Details -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Document Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Invoice No. :</label>
                                <input type="text" class="form-control" id="invoice_no" name="invoice_no" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reference :</label>
                                <input type="text" class="form-control" id="reference" name="reference">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Invoice Date :</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Due Date :</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item List -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Item List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th width="5%">No.</th>
                                    <th width="25%">Item & Description</th>
                                    <th width="10%">HSN/SAC</th>
                                    <th width="8%">Qty</th>
                                    <th width="8%">Unit</th>
                                    <th width="10%">Rate (₹)</th>
                                    <th width="10%">Discount (₹)</th>
                                    <th width="10%">Taxable (₹)</th>
                                    <th width="10%">CGST (₹)</th>
                                    <th width="10%">SGST (₹)</th>
                                    <th width="10%">Amt (₹)</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </div>

            <!-- Summary and Actions -->
            <div class="row">
                <!-- Left Column - Terms & Conditions and Notes -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Terms & Conditions</h5>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-sm btn-success" id="addTermBtn">
                                <i class="fas fa-plus"></i> Add Term / Condition
                            </button>
                            <textarea class="form-control mt-2" id="terms_conditions" name="terms_conditions" rows="3" style="display: none;"></textarea>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Notes</h5>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" id="notes" name="notes" rows="5"></textarea>
                            
                            <div class="mt-3">
                                <label class="form-label">Upload File :</label>
                                <button type="button" class="btn btn-warning btn-sm">
                                    <i class="fas fa-upload"></i> Upload File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-end"><strong>Total :</strong></td>
                                    <td class="text-end" width="30%">₹ <span id="subtotal">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <button type="button" class="btn btn-sm btn-success me-2" id="addExtraChargeBtn">
                                            <i class="fas fa-plus"></i> Add Extra Charge
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" id="addDiscountBtn">
                                            <i class="fas fa-plus"></i> Add Discount
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Grand Total :</strong></td>
                                    <td class="text-end"><strong>₹ <span id="grandTotal">0.00</span></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="button" class="btn btn-success me-2" id="saveBtn">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-success me-2" id="saveAndEnterAnotherBtn">
                                <i class="fas fa-save"></i> Save & Enter Another
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<!-- Inter-State Transfer Modal -->
<div class="modal fade" id="interStateModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Enter Inter-State Transfer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Source Branch :</label>
                    <select class="form-select" id="modal_source_branch">
                        <option value="">Select</option>
                        <option value="Main Branch">Main Branch</option>
                    </select>
                    <small class="text-muted">Please select a source branch</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelInterState">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmInterState">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Source Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" id="modal_address" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" id="modal_city">
                </div>
                <div class="mb-3">
                    <label class="form-label">State</label>
                    <input type="text" class="form-control" id="modal_state">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pincode</label>
                    <input type="text" class="form-control" id="modal_pincode">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAddress">Save</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // Show inter-state transfer modal on page load if needed
    <?php if ($showInterStateModal): ?>
    $(document).ready(function() {
        $('#interStateModal').modal('show');
    });
    <?php endif; ?>
</script>

<script src="assets/js/supplier_invoice_form.js"></script>
