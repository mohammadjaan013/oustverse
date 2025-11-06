<?php
/**
 * Production Jobs Page
 * Main page for managing production jobs and manufacturing
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'models/ProductionJob.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Page title
$pageTitle = 'Production Jobs';

// Get database connection
$database = Database::getInstance();
$db = $database->getConnection();

// Get statistics
$productionJobModel = new ProductionJob($db);
$stats = $productionJobModel->getStatistics();

// Include header
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="page-title"><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-warning" id="quickEntryBtn">
                    <i class="fas fa-plus"></i> Quick Entry
                </button>
                <button class="btn btn-primary" id="createJobBtn">
                    <i class="fas fa-plus"></i> Create Job
                </button>
                <button class="btn btn-dark" id="reportsBtn">
                    <i class="fas fa-chart-line"></i>
                </button>
                <button class="btn btn-secondary" id="settingsBtn">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search production jobs...">
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="jobTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" 
                        type="button" role="tab">
                    <i class="fas fa-tasks"></i> Active Jobs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" 
                        type="button" role="tab">
                    <i class="fas fa-history"></i> History
                </button>
            </li>
        </ul>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-card-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stats-label">WIP</p>
                            <h3 class="stats-value" id="wipCount"><?php echo $stats['wip_jobs'] ?? 0; ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stats-label">Overdue</p>
                            <h3 class="stats-value text-danger" id="overdueCount"><?php echo $stats['overdue_jobs'] ?? 0; ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stats-label">Total Jobs</p>
                            <h3 class="stats-value" id="totalCount"><?php echo $stats['total_jobs'] ?? 0; ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stats-label">Completed</p>
                            <h3 class="stats-value text-success" id="completedCount"><?php echo $stats['completed_jobs'] ?? 0; ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="jobTabsContent">
            <!-- Pending Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <!-- Action Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="action-card">
                            <h5 class="mb-3">Create a Production Job</h5>
                            <p class="text-muted mb-3">Create a production job so you can track its progress stage-wise.</p>
                            <button class="btn btn-warning" id="createJobCardBtn">
                                <i class="fas fa-plus"></i> Create Job
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="action-card">
                            <h5 class="mb-3">Add a Product</h5>
                            <p class="text-muted mb-3">Enter a product for which you want to launch a production job.</p>
                            <button class="btn btn-warning" id="addProductBtn">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Training Resources -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <button class="btn btn-outline-primary me-2">
                            <i class="fas fa-book"></i> Training Materials
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fas fa-video"></i> Watch Training
                        </button>
                    </div>
                </div>

                <!-- Pending Jobs Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pendingJobsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>WIP No.</th>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Quantity</th>
                                        <th>Target Date</th>
                                        <th>Days Left</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="historyJobsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>WIP No.</th>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Quantity</th>
                                        <th>Target Date</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Production Job Modal -->
<div class="modal fade" id="productionJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Create Production Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productionJobForm">
                    <input type="hidden" id="jobId" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="wipNo" class="form-label">WIP No.</label>
                            <input type="text" class="form-control" id="wipNo" name="wip_no" 
                                   placeholder="Auto-generated if empty">
                        </div>
                        <div class="col-md-6">
                            <label for="targetDate" class="form-label">Target Date</label>
                            <input type="date" class="form-control" id="targetDate" name="target_date" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="productId" class="form-label">
                                Product <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="productId" name="product_id" required>
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customerId" class="form-label">Customer</label>
                            <select class="form-control" id="customerId" name="customer_id">
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="quantity" 
                                   name="quantity" value="1" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="specialInstructions" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="specialInstructions" name="special_instructions" 
                                      rows="3" placeholder="Enter special instructions..."></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="saveJobBtn">
                    <i class="fas fa-check"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Job Details Modal -->
<div class="modal fade" id="viewJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Production Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <!-- Job details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once 'includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>/assets/js/production_jobs.js"></script>
