<?php
/**
 * CRM - Leads & Prospects Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/LeadController.php';

requireLogin();

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new LeadController();
    
    switch ($_GET['action']) {
        case 'list_json':
            $controller->getLeadsJson();
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
        case 'toggle_star':
            $controller->toggleStar();
            break;
        case 'get_lead':
            $controller->getLead();
            break;
    }
    exit;
}

$pageTitle = 'Leads & Prospects';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Leads & Prospects</h4>
        <div class="d-flex gap-2">
            <input type="text" id="globalSearch" class="form-control" placeholder="Search" style="width: 300px;">
            <button class="btn btn-warning" id="btnAddLead">
                <i class="fas fa-plus"></i> Add Lead
            </button>
            <button class="btn btn-warning" id="btnImport">
                <i class="fas fa-file-import"></i> Import
            </button>
            <button class="btn btn-secondary" id="btnFilters">
                <i class="fas fa-filter"></i> Filters (0)
            </button>
        </div>
    </div>

    <!-- Stage Filter Tabs -->
    <ul class="nav nav-tabs mb-3" id="stageTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-stage="">All Active Leads & Prospects</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="raw">Raw (Unqualified)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="new">New</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="discussion">Discussion</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="demo">Demo</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="proposal">Proposal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="decided">Decided</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-stage="inactive">Inactive</a>
        </li>
    </ul>

    <!-- Sort Tabs -->
    <ul class="nav nav-pills mb-3" id="sortTabs">
        <li class="nav-item">
            <button class="nav-link" id="btnAppointments">Appointments</button>
        </li>
        <li class="nav-item">
            <button class="nav-link active" id="btnNewest">Newest First</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="btnOldest">Oldest First</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="btnKanban">Kanban (Prospects)</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="btnStarLeads">Star Leads</button>
        </li>
        <li class="nav-item ms-auto">
            <span class="badge bg-dark">Count: <span id="leadCount">0</span></span>
        </li>
    </ul>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="leadsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="30"></th>
                            <th width="30"></th>
                            <th>Business</th>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Stage</th>
                            <th>Since</th>
                            <th>Assigned to</th>
                            <th>Last Talk</th>
                            <th>Next</th>
                            <th>Requirements</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Entry Sections -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Enter a Lead</h5>
                    <p class="text-muted">Enter details of a received lead/inquiry so you can track interactions and appointments.</p>
                    <button class="btn btn-warning" id="btnQuickAddLead">
                        <i class="fas fa-plus"></i> Add Lead
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Import Leads from B2B Platforms</h5>
                    <p class="text-muted">Integrate your IndiaMART, Meta, JustDial, TradeIndia, 99Acres, etc. lead platform accounts to manage received leads.</p>
                    <button class="btn btn-warning" id="btnIntegrate">
                        <i class="fas fa-link"></i> Integrate & Import
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Import Leads from Excel</h5>
                    <p class="text-muted">Use our Excel template to upload your existing leads.</p>
                    <button class="btn btn-warning" id="btnImportExcel">
                        <i class="fas fa-file-excel"></i> Import from Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="leadForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" id="leadId">
                    
                    <!-- Core Data Section -->
                    <h6 class="text-success mb-3">Core Data</h6>
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="business_name" id="businessName" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-3">
                                        <select class="form-select" name="title" id="title">
                                            <option value="Mr">Mr</option>
                                            <option value="Ms">Ms</option>
                                            <option value="Mrs">Mrs</option>
                                            <option value="Dr">Dr</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" name="first_name" id="firstName" placeholder="First Name">
                                    </div>
                                    <div class="col-5">
                                        <input type="text" class="form-control" name="contact_name" id="contactName" placeholder="Last Name">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" id="designation">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mobile</label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="tel" class="form-control" name="mobile" id="mobile">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" id="website">
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control mb-2" name="address_line1" id="addressLine1" placeholder="Line 1">
                                <input type="text" class="form-control" name="address_line2" id="addressLine2" placeholder="Line 2">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select class="form-select" name="country" id="country">
                                    <option value="India" selected>India</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="city" id="city">
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100" disabled>+</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">State</label>
                                    <select class="form-select" name="state" id="state">
                                        <option value="">Select</option>
                                        <option value="Maharashtra">Maharashtra</option>
                                        <option value="Delhi">Delhi</option>
                                        <option value="Karnataka">Karnataka</option>
                                        <option value="Tamil Nadu">Tamil Nadu</option>
                                        <option value="Gujarat">Gujarat</option>
                                        <option value="Rajasthan">Rajasthan</option>
                                        <option value="Uttar Pradesh">Uttar Pradesh</option>
                                        <option value="West Bengal">West Bengal</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">GSTIN</label>
                                    <input type="text" class="form-control" name="gstin" id="gstin">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" name="pincode" id="pincode">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control" name="code" id="code" placeholder="Lead Code (Auto-generated)">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Business Opportunity Section -->
                    <h6 class="text-success mb-3">Business Opportunity</h6>
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Source</label>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <select class="form-select" name="source" id="source">
                                            <option value="">Select</option>
                                            <option value="Mail">Mail</option>
                                            <option value="Call">Call</option>
                                            <option value="Website">Website</option>
                                            <option value="Referral">Referral</option>
                                            <option value="IndiaMART">IndiaMART</option>
                                            <option value="JustDial">JustDial</option>
                                            <option value="TradeIndia">TradeIndia</option>
                                            <option value="Social Media">Social Media</option>
                                            <option value="Walk-in">Walk-in</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100" disabled>+</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Since</label>
                                <input type="date" class="form-control" name="since_date" id="sinceDate" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" id="category">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Product</label>
                                <div class="row g-2">
                                    <div class="col-10">
                                        <input type="text" class="form-control" name="product" id="product">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-outline-secondary w-100" disabled>+</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Potential (₹)</label>
                                <input type="number" class="form-control" name="potential" id="potential" value="0" step="0.01">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Assigned to</label>
                                <select class="form-select" name="assigned_to" id="assignedTo">
                                    <option value="">Select User</option>
                                    <?php
                                    try {
                                        $usersQuery = "SELECT id, name, role FROM users WHERE active = 1 ORDER BY name";
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
                            
                            <div class="mb-3">
                                <label class="form-label">Stage</label>
                                <select class="form-select" name="stage" id="stage">
                                    <option value="raw" selected>Raw (Unqualified)</option>
                                    <option value="new">New</option>
                                    <option value="discussion">Discussion</option>
                                    <option value="demo">Demo</option>
                                    <option value="proposal">Proposal</option>
                                    <option value="decided">Decided</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" class="form-control" name="tags" id="tags" placeholder="Comma separated tags">
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Requirement</label>
                                <textarea class="form-control" name="requirements" id="requirements" rows="5"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnSaveLead">
                    <i class="fas fa-check"></i> Save & Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$customJS = "
<script>
    window.CSRF_TOKEN_NAME = '" . CSRF_TOKEN_NAME . "';
</script>
<script src='" . BASE_URL . "/assets/js/crm.js'></script>
";
include __DIR__ . '/includes/footer.php';
?>
