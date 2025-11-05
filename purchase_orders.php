<?php
/**
 * Purchase Orders Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'controllers/PurchaseOrderController.php';

// Check authentication
requireLogin();

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new PurchaseOrderController();
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'list_json':
            $controller->getPurchaseOrdersJson();
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
        case 'get_po':
            $controller->getPurchaseOrder();
            break;
        case 'approve':
            $controller->approve();
            break;
        case 'reject':
            $controller->reject();
            break;
        case 'generate_po_number':
            $controller->generatePoNumber();
            break;
        case 'get_suppliers':
            $controller->getSuppliers();
            break;
        case 'get_products':
            $controller->getProducts();
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
    exit;
}

// Load page data
$controller = new PurchaseOrderController();
$pageData = $controller->index();

$pageTitle = 'Purchase Orders';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Purchase Orders</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Settings
                </button>
                <button class="btn btn-warning" onclick="window.location.href='purchase_order_form.php'">
                    <i class="fas fa-plus"></i> Create Purchase Order
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Period</label>
                        <select id="filterMonth" class="form-select">
                            <option value="<?php echo date('Y-m'); ?>">This Month</option>
                            <option value="<?php echo date('Y-m', strtotime('-1 month')); ?>">Last Month</option>
                            <option value="<?php echo date('Y-m', strtotime('-2 months')); ?>">2 Months Ago</option>
                            <option value="">All Time</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="" selected>All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="filterBranch" class="form-select">
                            <option value="">All Branches</option>
                            <option value="1">Main Branch</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Executive</label>
                        <select id="filterExecutive" class="form-select">
                            <option value="">All Executives</option>
                            <?php
                            // Get users for executive filter
                            $usersQuery = "SELECT id, name FROM users WHERE active = 1 ORDER BY name";
                            $usersStmt = getDB()->query($usersQuery);
                            while ($user = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Orders Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="purchaseOrdersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Contact</th>
                                <th>Order No.</th>
                                <th>Order Date</th>
                                <th>Taxable (₹)</th>
                                <th>Amount (₹)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Entry Link -->
        <div class="mt-3">
            <button class="btn btn-warning" onclick="window.location.href='purchase_order_form.php'">
                <i class="fas fa-plus"></i> Click here to enter a purchase order
            </button>
        </div>

        <!-- Training Links -->
        <div class="mt-3">
            <button class="btn btn-outline-secondary me-2" onclick="alert('Training materials coming soon')">
                <i class="fas fa-book"></i> Training Materials
            </button>
            <button class="btn btn-outline-primary" onclick="alert('Training video coming soon')">
                <i class="fab fa-youtube"></i> Watch Training
            </button>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="assets/js/purchase_orders.js"></script>
