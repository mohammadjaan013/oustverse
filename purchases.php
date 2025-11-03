<?php
/**
 * Supplier Invoices Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'controllers/SupplierInvoiceController.php';

// Check authentication
requireLogin();

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new SupplierInvoiceController();
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'getInvoices':
            $controller->getInvoicesJson();
            break;
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'getInvoice':
            $controller->getInvoice();
            break;
        case 'approve':
            $controller->approve();
            break;
        case 'generateInvoiceNo':
            $controller->generateInvoiceNumber();
            break;
        case 'getSuppliers':
            $controller->getSuppliers();
            break;
        case 'getProducts':
            $controller->getProducts();
            break;
        case 'addPayment':
            $controller->addPayment();
            break;
        case 'getPayments':
            $controller->getPayments();
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
    exit;
}

// Initialize model
$invoiceModel = new SupplierInvoice();

// Get statistics
$stats = $invoiceModel->getStatistics();

$pageTitle = 'Purchases';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Purchases</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-warning" onclick="window.location.href='supplier_invoice_form.php?type=inter_state_transfer'">
                    <i class="fas fa-truck"></i> Enter Inter-State Transfer
                </button>
                <button class="btn btn-warning" onclick="window.location.href='supplier_invoice_form.php'">
                    <i class="fas fa-plus"></i> Enter Supplier Invoice
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Invoices</h6>
                        <h3 class="card-title"><?php echo number_format($stats['total_invoices']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Pending Approval</h6>
                        <h3 class="card-title text-warning"><?php echo number_format($stats['pending_count']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Unpaid Amount</h6>
                        <h3 class="card-title text-danger">₹ <?php echo number_format($stats['unpaid_amount'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Amount</h6>
                        <h3 class="card-title text-success">₹ <?php echo number_format($stats['total_amount'], 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Period</label>
                        <select class="form-select" id="period" name="period">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="dateFromGroup" style="display: none;">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>
                    <div class="col-md-2" id="dateToGroup" style="display: none;">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Invoices</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice Type</label>
                        <select class="form-select" id="invoice_type" name="invoice_type">
                            <option value="">All Types</option>
                            <option value="supplier_invoice">Supplier Invoice</option>
                            <option value="inter_state_transfer">Inter-State Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-secondary w-100" id="resetFilter" title="Reset Filters">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="invoicesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Contact</th>
                                <th>Invoice No.</th>
                                <th>Invoice Date</th>
                                <th>Taxable (₹)</th>
                                <th>Amount (₹)</th>
                                <th>Credit Month</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Training Materials -->
        <div class="mt-3">
            <button class="btn btn-outline-primary me-2">
                <i class="fas fa-book"></i> Training Materials
            </button>
            <button class="btn btn-outline-secondary">
                <i class="fas fa-play-circle"></i> Watch Training
            </button>
        </div>

    </div>
</div>

<!-- Add Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="payment_invoice_id" name="invoice_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" id="payment_mode" name="payment_mode">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reference No.</label>
                            <input type="text" class="form-control" id="reference_no" name="reference_no">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePayment">Add Payment</button>
                </div>
            </div>
        </div>
    </div>
    

<?php require_once 'includes/footer.php'; ?>

<script src="assets/js/purchases.js"></script>
