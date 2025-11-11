<?php
/**
 * Invoices Page
 * Displays all invoices with filtering
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Invoices';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h4><i class="fas fa-file-invoice-dollar"></i> Invoices</h4>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print"></i> Print Settings
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-table"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chart-line"></i>
            </button>
            <button class="btn btn-dark btn-sm">
                <i class="fas fa-sticky-note"></i> Credit Notes
            </button>
            <a href="invoice_form.php" class="btn btn-warning btn-sm">
                <i class="fas fa-plus"></i> Create Invoice
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-2">
            <select id="filterMonth" class="form-select form-select-sm">
                <option value="">All Invoices</option>
                <option value="<?php echo date('Y-m'); ?>" selected>This Month</option>
                <option value="<?php echo date('Y-m', strtotime('-1 month')); ?>">Last Month</option>
                <option value="<?php echo date('Y-m', strtotime('-2 months')); ?>">2 Months Ago</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="selectDate">
                <label class="form-check-label" for="selectDate">
                    Select Date
                </label>
            </div>
        </div>
        <div class="col-md-2">
            <select id="filterType" class="form-select form-select-sm">
                <option value="">All Invoices</option>
                <option value="party_invoice">Party Invoice</option>
                <option value="cash_memo">Cash Memo</option>
                <option value="inter_state_transfer">Inter-State Transfer</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="filterStatus" class="form-select form-select-sm">
                <option value="">All Invoices</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="filterExecutive" class="form-select form-select-sm">
                <option value="">All Executives</option>
                <!-- Will be populated via AJAX -->
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" id="searchBox" class="form-control form-control-sm" placeholder="Search...">
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Count</small>
                    <h6 class="mb-0" id="summaryCount">0</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Pre-Tax</small>
                    <h6 class="mb-0" id="summaryPreTax">₹ 0.00</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Total</small>
                    <h6 class="mb-0" id="summaryTotal">₹ 0.00</h6>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Pending</small>
                    <h6 class="mb-0" id="summaryPending">₹ 0.00</h6>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoices Table -->
    <div class="card">
        <div class="card-body">
            <table id="invoicesTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice No.</th>
                        <th>Invoice Date</th>
                        <th>Taxable (₹)</th>
                        <th>Amount (₹)</th>
                        <th>Status</th>
                        <th>Pndng. (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Training Materials Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <button class="btn btn-outline-info btn-sm me-2">
                <i class="fas fa-book"></i> Training Materials
            </button>
            <button class="btn btn-outline-info btn-sm">
                <i class="fas fa-video"></i> Watch Training
            </button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/invoices.js"></script>
