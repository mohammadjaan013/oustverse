<?php
/**
 * Quotations Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/QuotationController.php';

requireLogin();

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new QuotationController();
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'list_json':
            $controller->getQuotationsJson();
            break;
        case 'get_totals':
            $controller->getTotals();
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
        case 'get_quotation':
            $controller->getQuotation();
            break;
        case 'get_next_quote_no':
            $controller->getNextQuoteNo();
            break;
    }
    exit;
}

$pageTitle = 'Quotations';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Quotations</h4>
        <div class="d-flex gap-2 align-items-center">
            <!-- Summary Boxes -->
            <div class="badge bg-warning text-dark px-3 py-2">
                Count: <strong id="totalCount">0</strong>
            </div>
            <div class="badge bg-info text-dark px-3 py-2">
                Pre-Tax: ₹ <strong id="preTaxTotal">0.00</strong>
            </div>
            <div class="badge bg-success px-3 py-2">
                Total: ₹ <strong id="grandTotal">0.00</strong>
            </div>
            
            <!-- Action Buttons -->
            <button class="btn btn-secondary" id="btnPrintSettings">
                <i class="fas fa-print"></i> Print Settings
            </button>
            <button class="btn btn-primary" id="btnUpload">
                <i class="fas fa-upload"></i>
            </button>
            <button class="btn btn-dark" id="btnGrid">
                <i class="fas fa-th"></i>
            </button>
            <button class="btn btn-warning" id="btnCreateQuotation">
                <i class="fas fa-plus"></i> Create Quotation
            </button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group me-2" role="group">
                <button type="button" class="btn btn-outline-warning active" data-filter="all">All</button>
                <button type="button" class="btn btn-outline-warning" data-filter="quotation">Quotations</button>
                <button type="button" class="btn btn-outline-warning" data-filter="proforma">Proforma Invoices</button>
            </div>
            
            <select class="form-select d-inline-block" id="filterMonth" style="width: 200px;">
                <option value="">This Month</option>
                <option value="<?php echo date('Y-m'); ?>"><?php echo date('F Y'); ?></option>
                <option value="<?php echo date('Y-m', strtotime('-1 month')); ?>"><?php echo date('F Y', strtotime('-1 month')); ?></option>
                <option value="<?php echo date('Y-m', strtotime('-2 months')); ?>"><?php echo date('F Y', strtotime('-2 months')); ?></option>
            </select>
            
            <select class="form-select d-inline-block" id="filterStatus" style="width: 150px;">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
                <option value="expired">Expired</option>
            </select>
            
            <select class="form-select d-inline-block" id="filterBranch" style="width: 200px;">
                <option value="">All Branches</option>
            </select>
            
            <select class="form-select d-inline-block" id="filterExecutive" style="width: 200px;">
                <option value="">All Executives</option>
                <?php
                try {
                    $usersQuery = "SELECT id, name FROM users WHERE active = 1 ORDER BY name";
                    $usersStmt = getDB()->query($usersQuery);
                    while ($user = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . '</option>';
                    }
                } catch (Exception $e) {
                    error_log("Error fetching users: " . $e->getMessage());
                }
                ?>
            </select>
        </div>
    </div>

    <!-- Quotations Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="quotationsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Quote No.</th>
                            <th>Customer</th>
                            <th>Amount (₹)</th>
                            <th>Valid till</th>
                            <th>Issued on</th>
                            <th>Issued by</th>
                            <th>Type</th>
                            <th>Executive</th>
                            <th>Response</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$customJS = "
<script>
    window.CSRF_TOKEN_NAME = '" . CSRF_TOKEN_NAME . "';
</script>
<script src='" . BASE_URL . "/assets/js/quotations.js'></script>
";
include __DIR__ . '/includes/footer.php';
?>
