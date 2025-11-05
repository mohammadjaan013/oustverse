<?php
/**
 * Suppliers Management Page
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'controllers/SupplierController.php';

// Check authentication
requireLogin();

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new SupplierController();
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'list_json':
            $controller->getSuppliersJson();
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
        case 'get_supplier':
            $controller->getSupplier();
            break;
        case 'get_contacts':
            $controller->getContacts();
            break;
        case 'add_contact':
            $controller->addContact();
            break;
        case 'update_contact':
            $controller->updateContact();
            break;
        case 'delete_contact':
            $controller->deleteContact();
            break;
        case 'generate_code':
            $controller->generateCode();
            break;
        case 'export_csv':
            $controller->exportCSV();
            break;
        case 'import_csv':
            $controller->importCSV();
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
    exit;
}

// Load page data
$controller = new SupplierController();
$pageData = $controller->index();

// Get cities and states for modal dropdowns
$cities = [];
$states = [];
try {
    $citiesQuery = "SELECT DISTINCT city FROM suppliers WHERE city IS NOT NULL AND city != '' ORDER BY city";
    $citiesStmt = getDB()->query($citiesQuery);
    while ($row = $citiesStmt->fetch(PDO::FETCH_ASSOC)) {
        $cities[] = $row['city'];
    }
    
    $statesQuery = "SELECT DISTINCT state FROM suppliers WHERE state IS NOT NULL AND state != '' ORDER BY state";
    $statesStmt = getDB()->query($statesQuery);
    while ($row = $statesStmt->fetch(PDO::FETCH_ASSOC)) {
        $states[] = $row['state'];
    }
} catch (Exception $e) {
    // If table doesn't exist yet, use default Indian states
    $states = ['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Gujarat', 'Uttar Pradesh', 'West Bengal', 'Rajasthan', 'Kerala', 'Telangana'];
    $cities = ['Mumbai', 'Bangalore', 'Chennai', 'Delhi', 'Hyderabad', 'Ahmedabad', 'Pune', 'Kolkata', 'Surat', 'Jaipur'];
}

$pageTitle = 'Suppliers Management';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Connections (Suppliers)</h4>
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" id="topSearch" class="form-control" placeholder="Search">
                </div>
                <button class="btn btn-warning" onclick="addSupplier()">
                    <i class="fas fa-plus"></i> Enter Supplier
                </button>
                <button class="btn btn-secondary" onclick="alert('Appointments feature coming soon')">
                    Appointments
                </button>
                <button class="btn btn-primary" onclick="$('#importModal').modal('show')">
                    <i class="fas fa-file-import"></i> Import Suppliers
                </button>
                <button class="btn btn-outline-secondary" onclick="alert('Calendar feature coming soon')">
                    <i class="far fa-calendar"></i>
                </button>
                <button class="btn btn-outline-secondary" onclick="alert('Mobile sync coming soon')">
                    <i class="fas fa-mobile-alt"></i>
                </button>
            </div>
        </div>

        <!-- Connection Type Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterByConnectionType('all'); return false;" id="tab-all">All</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterByConnectionType('customer'); return false;" id="tab-customer">
                    <i class="fas fa-circle text-warning"></i> Customers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="filterByConnectionType('supplier'); return false;" id="tab-supplier">
                    <i class="fas fa-circle text-success"></i> Suppliers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterByConnectionType('neighbour'); return false;" id="tab-neighbour">
                    <i class="fas fa-circle text-primary"></i> Neighbours
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterByConnectionType('friend'); return false;" id="tab-friend">
                    <i class="fas fa-circle text-secondary"></i> Friends
                </a>
            </li>
        </ul>

        <!-- Quick Action Buttons -->
        <div class="mb-3">
            <button class="btn btn-warning btn-sm">
                <i class="fas fa-shopping-cart"></i> Supplier Invoices
            </button>
            <button class="btn btn-light btn-sm ms-2">
                <i class="fas fa-file-alt"></i> Purchase Orders
            </button>
        </div>

        <!-- Filters -->
        <div class="d-flex gap-2 mb-3">
            <select id="filterExecutive" class="form-select" style="width: 200px;">
                <option value="">Select Executive</option>
                <?php
                // Get users for executive filter
                $usersQuery = "SELECT id, name FROM users WHERE active = 1 ORDER BY name";
                $usersStmt = getDB()->query($usersQuery);
                while ($user = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . '</option>';
                }
                ?>
            </select>
            <select id="filterCity" class="form-select" style="width: 200px;">
                <option value="">All Cities</option>
                <?php
                // Get distinct cities
                try {
                    $citiesQuery = "SELECT DISTINCT city FROM suppliers WHERE city IS NOT NULL AND city != '' ORDER BY city";
                    $citiesStmt = getDB()->query($citiesQuery);
                    while ($city = $citiesStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . htmlspecialchars($city['city']) . '">' . htmlspecialchars($city['city']) . '</option>';
                    }
                } catch (Exception $e) {
                    // Silently fail if table doesn't exist yet
                }
                ?>
            </select>
            <select id="filterState" class="form-select" style="width: 200px;">
                <option value="">All States</option>
                <?php
                // Get distinct states
                try {
                    $statesQuery = "SELECT DISTINCT state FROM suppliers WHERE state IS NOT NULL AND state != '' ORDER BY state";
                    $statesStmt = getDB()->query($statesQuery);
                    while ($state = $statesStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . htmlspecialchars($state['state']) . '">' . htmlspecialchars($state['state']) . '</option>';
                    }
                } catch (Exception $e) {
                    // Silently fail if table doesn't exist yet
                }
                ?>
            </select>
        </div>

        <!-- Suppliers Table -->
        <div class="card">
            <div class="card-body">
                <table id="suppliersTable" class="table table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th style="width: 80px;">Relation</th>
                            <th>Last Talk</th>
                            <th>Next Action</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="supplierModalLabel">Enter Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm">
                <input type="hidden" name="id" id="supplierId">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= generateCSRFToken() ?>">
                
                <div class="modal-body">
                    <!-- Quick Entry Section -->
                    <div id="quickEntrySection">
                        <div class="row g-3">
                            
                            <!-- Supplier Code -->
                            <div class="col-md-6">
                                <label class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" id="supplierCode" required placeholder="e.g., SUP-001">
                                <small class="text-muted">Unique identifier for this supplier</small>
                            </div>
                            
                            <!-- Business Name -->
                            <div class="col-12">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" name="name" id="supplierName" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="fillUsingGSTIN()">
                                        Fill using GSTIN
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Contact Name -->
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-auto" style="width: 100px;">
                                        <select class="form-select" id="namePrefix" name="name_prefix">
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Dr.">Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="contact_person" id="contactPerson" placeholder="First Name" required>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="last_name" id="lastName" placeholder="Last Name">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mobile OR Email -->
                            <div class="col-12">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <label class="form-label">Mobile</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+91</span>
                                            <input type="text" class="form-control" name="mobile" id="supplierMobile" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <strong>OR</strong>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" id="supplierEmail">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Connection Type Checkboxes -->
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_customer" id="isCustomer" value="1">
                                        <label class="form-check-label text-warning" for="isCustomer">
                                            <i class="fas fa-square"></i> Customer
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_supplier" id="isSupplier" value="1" checked>
                                        <label class="form-check-label text-success" for="isSupplier">
                                            <i class="fas fa-check-square"></i> Supplier
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_neighbour" id="isNeighbour" value="1">
                                        <label class="form-check-label text-primary" for="isNeighbour">
                                            <i class="fas fa-square"></i> Neighbour
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_friend" id="isFriend" value="1">
                                        <label class="form-check-label text-secondary" for="isFriend">
                                            <i class="fas fa-square"></i> Friend
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Expandable Section Trigger -->
                            <div class="col-12">
                                <a href="#" class="text-decoration-none" onclick="toggleMoreDetails(); return false;">
                                    <i class="fas fa-chevron-down" id="moreDetailsIcon"></i> Enter More Details
                                </a>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Expanded Details Section (Hidden by default) -->
                    <div id="moreDetailsSection" style="display: none;">
                        <hr>
                        <div class="row g-3">
                        
                        <!-- Additional Information -->
                        <div class="col-12">
                            <h6 class="border-bottom pb-2">Additional Information</h6>
                        </div>
                        
                        <!-- Website -->
                        <div class="col-md-12">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" name="website" id="supplierWebsite" placeholder="https://example.com">
                        </div>
                        
                        <!-- Industry & Segment -->
                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <select class="form-select" name="industry" id="supplierIndustry">
                                <option value="">Select Industry</option>
                                <option value="manufacturing">Manufacturing</option>
                                <option value="retail">Retail</option>
                                <option value="services">Services</option>
                                <option value="technology">Technology</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="construction">Construction</option>
                                <option value="food_beverage">Food & Beverage</option>
                                <option value="textile">Textile</option>
                                <option value="automotive">Automotive</option>
                                <option value="electronics">Electronics</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Segment</label>
                            <select class="form-select" name="segment" id="supplierSegment">
                                <option value="">Select Segment</option>
                                <option value="large_enterprise">Large Enterprise</option>
                                <option value="mid_size">Mid-Size</option>
                                <option value="small_business">Small Business</option>
                                <option value="startup">Startup</option>
                                <option value="msme">MSME</option>
                            </select>
                        </div>
                        
                        <!-- Location Information -->
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <select class="form-select" name="country" id="supplierCountry">
                                <option value="India" selected>India</option>
                                <option value="USA">USA</option>
                                <option value="UK">UK</option>
                                <option value="China">China</option>
                                <option value="UAE">UAE</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <select class="form-select" name="state" id="supplierState">
                                <option value="">Select State</option>
                                <?php foreach($states as $state): ?>
                                    <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select class="form-select" name="city" id="supplierCity">
                                <option value="">Select City</option>
                                <?php foreach($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Tax & Compliance -->
                        <div class="col-md-6">
                            <label class="form-label">MSME No</label>
                            <input type="text" class="form-control" name="msme_no" id="supplierMsmeNo" placeholder="Enter MSME Number">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">PAN No</label>
                            <input type="text" class="form-control" name="pan" id="supplierPan" maxlength="10" placeholder="ABCDE1234F" style="text-transform: uppercase;">
                        </div>
                        
                        <!-- GSTIN -->
                        <div class="col-md-12">
                            <label class="form-label">GSTIN</label>
                            <input type="text" class="form-control" name="gstin" id="supplierGstin" maxlength="15" placeholder="22AAAAA0000A1Z5" style="text-transform: uppercase;">
                        </div>
                        
                        <!-- Manage Addresses & GST Button -->
                        <div class="col-12 mt-3">
                            <button type="button" class="btn btn-warning" onclick="manageAddressesGST()">
                                <i class="fas fa-building"></i> Manage Addresses & GST
                            </button>
                        </div>
                        
                        <!-- Payment Terms -->
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Payment Terms</label>
                            <select class="form-select" name="payment_terms" id="paymentTerms">
                                <option value="net15">Net 15</option>
                                <option value="net30" selected>Net 30</option>
                                <option value="net45">Net 45</option>
                                <option value="net60">Net 60</option>
                                <option value="cod">COD</option>
                                <option value="advance">Advance</option>
                            </select>
                        </div>
                        
                        <!-- Credit Limit -->
                        <div class="col-md-6">
                            <label class="form-label">Credit Limit</label>
                            <input type="number" class="form-control" name="credit_limit" id="creditLimit" value="0" step="0.01">
                        </div>
                        
                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="supplierAddress" rows="2" placeholder="Enter full address"></textarea>
                        </div>
                        
                        <!-- Pincode -->
                        <div class="col-md-6">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control" name="pincode" id="supplierPincode" maxlength="6" placeholder="Enter pincode">
                        </div>
                        
                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="supplierPhone" placeholder="Enter phone number">
                        </div>
                        
                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="supplierNotes" rows="3" placeholder="Add any additional notes"></textarea>
                        </div>
                        
                        </div>
                    </div>
                    <!-- End More Details Section -->
                    
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Contacts Modal -->
<div class="modal fade" id="contactsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Contacts - <span id="contactSupplierName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="contactSupplierId">
                
                <!-- Add Contact Button -->
                <button class="btn btn-sm btn-primary mb-3" onclick="showAddContactForm()">
                    <i class="fas fa-plus"></i> Add Contact
                </button>
                
                <!-- Contact Form (hidden by default) -->
                <div id="contactFormDiv" style="display: none;">
                    <form id="contactForm" class="border p-3 rounded mb-3">
                        <input type="hidden" name="id" id="contactId">
                        <input type="hidden" name="supplier_id" id="contactFormSupplierId">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="contactName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" id="contactDesignation">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="contactEmail">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="contactPhone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" id="contactMobile">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" name="whatsapp" id="contactWhatsapp">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary" value="1">
                                    <label class="form-check-label" for="isPrimary">
                                        Primary Contact
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="contactNotes" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-save"></i> Save Contact
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="hideContactForm()">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Contacts List -->
                <div id="contactsList">
                    <p class="text-muted text-center">Loading contacts...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Suppliers from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= generateCSRFToken() ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>CSV Format:</strong><br>
                            Code, Name, Type, Contact Person, Email, Phone, Mobile, Address, City, State, Pincode, GSTIN, PAN, Payment Terms, Credit Limit, Credit Days, Status
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Pass PHP constants to JavaScript
const CSRF_TOKEN_NAME = '<?= CSRF_TOKEN_NAME ?>';
const CSRF_TOKEN = '<?= generateCSRFToken() ?>';
</script>

<script src="assets/js/suppliers.js"></script>
