<?php
/**
 * Inventory Management Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/InventoryController.php';

// Require login
requireLogin();

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new InventoryController();
    
    switch ($_GET['action']) {
        case 'list_json':
            $controller->getItemsJson();
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
        case 'stock_in':
            $controller->stockIn();
            break;
        case 'stock_out':
            $controller->stockOut();
            break;
        case 'stock_transfer':
            $controller->stockTransfer();
            break;
        case 'export_csv':
            $controller->exportCSV();
            break;
        case 'movements':
            $controller->getMovements();
            break;
        case 'get_item':
            // Get single item for editing
            $id = intval($_GET['id'] ?? 0);
            if ($id) {
                require_once __DIR__ . '/models/Inventory.php';
                $model = new Inventory();
                $item = $model->getItemById($id);
                jsonResponse(true, 'Item retrieved', $item);
            } else {
                jsonResponse(false, 'Invalid item ID');
            }
            break;
        case 'get_items':
            // Get items for location
            $locationId = intval($_GET['location_id'] ?? 0);
            require_once __DIR__ . '/models/Inventory.php';
            $model = new Inventory();
            $items = $model->getItems(['location_id' => $locationId]);
            jsonResponse(true, 'Items retrieved', $items);
            break;
    }
    exit;
}

// Load data for page
$controller = new InventoryController();
$data = $controller->index();

$pageTitle = 'Inventory';
$pageActions = '
    <button class="btn btn-warning" id="btnStockOut">
        <i class="fas fa-arrow-up me-1"></i>Out / Issue
    </button>
    <button class="btn btn-success" id="btnStockIn">
        <i class="fas fa-arrow-down me-1"></i>In / Receive
    </button>
    <button class="btn btn-primary" id="btnAddItem">
        <i class="fas fa-plus me-1"></i>Add Item
    </button>
    <button class="btn btn-secondary" id="btnImportItems">
        <i class="fas fa-upload me-1"></i>Import Items
    </button>
    <button class="btn btn-info" id="btnExportCSV">
        <i class="fas fa-download me-1"></i>Export CSV
    </button>
';

include __DIR__ . '/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-3" id="inventoryTypeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-type="" type="button">
                    All
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="products-tab" data-bs-toggle="pill" data-type="products" type="button">
                    <i class="fas fa-box text-success"></i> Products
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="materials-tab" data-bs-toggle="pill" data-type="materials" type="button">
                    <i class="fas fa-cubes text-warning"></i> Materials
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="spares-tab" data-bs-toggle="pill" data-type="spares" type="button">
                    <i class="fas fa-tools text-dark"></i> Spares
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assemblies-tab" data-bs-toggle="pill" data-type="assemblies" type="button">
                    <i class="fas fa-puzzle-piece text-primary"></i> Assemblies
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-2">
        <select class="form-select" id="filterLocation">
            <option value="">Factory</option>
            <?php foreach ($data['locations'] as $loc): ?>
                <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterCategory">
            <option value="">All Category</option>
            <?php foreach ($data['categories'] as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterSubCategory">
            <option value="">All Sub Category</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterStock">
            <option value="">All Non-Zero Stock</option>
            <option value="zero">Zero Stock</option>
            <option value="low">Low Stock</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterImportance">
            <option value="">All Importance Levels</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="text" class="form-control" id="filterTag" placeholder="Search by Tag">
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="badge bg-warning">Valuation: Standard Cost</span>
                    </div>
                    <div>
                        <input type="text" class="form-control form-control-sm" id="tableSearch" placeholder="Search...">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="inventoryTable" class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Code</th>
                                <th>Importance</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td>
                                    <?php 
                                    $importance = $item['reorder_level'] > 0 ? 'Normal' : 'Normal';
                                    echo '<span class="badge bg-secondary">' . $importance . '</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? '-'); ?></td>
                                <td><?php echo $item['total_qty'] . ' ' . $item['unit']; ?></td>
                                <td><?php echo formatCurrency($item['standard_cost']); ?></td>
                                <td><?php echo formatCurrency($item['total_value']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary btn-edit" data-id="<?php echo $item['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-info btn-view" data-id="<?php echo $item['id']; ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-danger btn-delete" data-id="<?php echo $item['id']; ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" id="itemId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="itemName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sku" id="itemSKU" required>
                            <small class="text-muted">Prev. Code: SS Syphon</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <div class="input-group">
                                <select class="form-select select2" name="category_id" id="itemCategory">
                                    <option value="">Select Category</option>
                                    <?php foreach ($data['categories'] as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-warning" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sub-Category</label>
                            <div class="input-group">
                                <select class="form-select select2" name="sub_category_id" id="itemSubCategory">
                                    <option value="">Select Sub-Category</option>
                                </select>
                                <button class="btn btn-outline-warning" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Qty</label>
                            <input type="number" class="form-control" name="initial_qty" id="itemQty" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="unit" id="itemUnit" value="PCS">
                                <button class="btn btn-outline-warning" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Store</label>
                            <div class="input-group">
                                <select class="form-select" name="location_id" id="itemLocation">
                                    <?php foreach ($data['locations'] as $loc): ?>
                                        <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-warning" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Importance</label>
                            <select class="form-select" name="importance" id="itemImportance">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <input type="checkbox" class="btn-check" name="type_product" id="typeProduct" checked>
                            <label class="btn btn-outline-dark" for="typeProduct">
                                <i class="fas fa-box"></i> Products
                            </label>
                            
                            <input type="checkbox" class="btn-check" name="type_material" id="typeMaterial">
                            <label class="btn btn-outline-warning" for="typeMaterial">
                                <i class="fas fa-cubes"></i> Materials
                            </label>
                            
                            <input type="checkbox" class="btn-check" name="type_spares" id="typeSpares">
                            <label class="btn btn-outline-dark" for="typeSpares">
                                <i class="fas fa-tools"></i> Spares
                            </label>
                            
                            <input type="checkbox" class="btn-check" name="type_assemblies" id="typeAssemblies">
                            <label class="btn btn-outline-primary" for="typeAssemblies">
                                <i class="fas fa-puzzle-piece"></i> Assemblies
                            </label>
                            
                            <input type="checkbox" class="btn-check" name="internal_manufacturing" id="internalManufacturing">
                            <label class="btn btn-outline-secondary" for="internalManufacturing">
                                Internal Manufacturing
                            </label>
                            
                            <input type="checkbox" class="btn-check" name="purchase" id="purchase">
                            <label class="btn btn-outline-secondary" for="purchase">
                                Purchase
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Std. Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="standard_cost" id="itemStdCost" value="0">
                                <span class="input-group-text">/ no.s</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purch. Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="purchase_cost" id="itemPurchCost" value="0">
                                <span class="input-group-text">/ no.s</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Std Sale Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" name="retail_price" id="itemRetailPrice" value="0">
                                <span class="input-group-text">/ no.s</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">HSN/SAC</label>
                            <input type="text" class="form-control" name="hsn_code" id="itemHSN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="tax_rate" id="itemGST" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Description (will be suggested in documents like invoices, orders, etc.)</label>
                            <textarea class="form-control" name="description" id="itemDescription" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Internal Notes</label>
                            <textarea class="form-control" name="internal_notes" id="itemNotes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Min Stock</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="reorder_level" id="itemMinStock" value="0">
                                <span class="input-group-text">no.s</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lead Time</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="lead_time" id="itemLeadTime" value="0">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="tags" id="itemTags">
                            <button class="btn btn-outline-warning" type="button">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-success" id="btnSaveItem">
                    <i class="fas fa-check me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">In / Receive Items</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button class="list-group-item list-group-item-action" data-type="purchase">
                        Purchase Inward (GRN)
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="user">
                        Receive from User
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="production">
                        Receive from Production
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="unused">
                        Receive Unused
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="jobwork">
                        Job Work (Out) - Receive
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Out Modal -->
<div class="modal fade" id="stockOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Out / Issue Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button class="list-group-item list-group-item-action" data-type="dispatch">
                        Dispatch
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="user">
                        Issue to User
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="production">
                        Issue for Production
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="backflush">
                        Quick Production Entry (Backflush)
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="transfer">
                        Transfer to Other Store
                    </button>
                    <button class="list-group-item list-group-item-action" data-type="jobwork">
                        Job Work (Out) - Dispatch
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Select Items Modal (for stock in/out operations) -->
<div class="modal fade" id="selectItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Select Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Store: <span class="text-danger">*</span></label>
                    <select class="form-select" id="selectStoreDropdown" required>
                        <option value="">Select</option>
                        <?php foreach ($data['locations'] as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Assign To:</label>
                    <select class="form-select select2" id="assignedToPerson">
                        <option value="">-- Not Assigned --</option>
                        <?php
                        // Fetch all active users for assignment
                        try {
                            $usersQuery = "SELECT id, name, role FROM users WHERE active = 1 ORDER BY name";
                            $usersStmt = getDB()->query($usersQuery);
                            while ($user = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
                                $roleLabel = ucfirst($user['role']);
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . ' (' . $roleLabel . ')</option>';
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching users: " . $e->getMessage());
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Optional: Assign this task to a specific person
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Assignment Notes:</label>
                    <textarea class="form-control" id="assignmentNotes" rows="2" placeholder="Add any special instructions or notes for the assigned person..."></textarea>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <input type="text" class="form-control" id="selectItemSearch" placeholder="Search items...">
                </div>
                
                <div id="selectItemsList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center text-muted p-3">
                        Please select store.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-dark" id="btnSelectItems">
                    <i class="fas fa-check me-1"></i>Select
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$customJS = "
<script>
    // Define CSRF token name for JavaScript
    window.CSRF_TOKEN_NAME = '" . CSRF_TOKEN_NAME . "';
</script>
<script src='" . BASE_URL . "/assets/js/inventory.js'></script>
";
include __DIR__ . '/includes/footer.php';
?>
