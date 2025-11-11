<?php
/**
 * Orders Page
 * Displays all orders with commitment tracking
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Sale Orders';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h4><i class="fas fa-file-invoice"></i> Sale Orders</h4>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enterOrderModal">
                <i class="fas fa-plus"></i> Enter Order
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enterDeliveryModal">
                <i class="fas fa-truck"></i> Enter Delivery
            </button>
            <a href="order_form.php" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Create Sale Order
            </a>
        </div>
    </div>
    
    <!-- Commitment View Tabs -->
    <ul class="nav nav-tabs mb-3" id="commitmentTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-commitment="overdue">
                Overdue <span class="badge bg-danger" id="badge-overdue">0</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-commitment="today">
                Today <span class="badge bg-warning" id="badge-today">0</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-commitment="tomorrow">
                Tomorrow <span class="badge bg-info" id="badge-tomorrow">0</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-commitment="">
                All Orders <span class="badge bg-secondary" id="badge-all">0</span>
            </button>
        </li>
    </ul>
    
    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="orderTypeFilter" class="form-select">
                <option value="">All Types</option>
                <option value="sales">Sales</option>
                <option value="service">Service</option>
            </select>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="viewType" id="itemView" checked>
                <label class="btn btn-outline-primary" for="itemView">Item View</label>
                
                <input type="radio" class="btn-check" name="viewType" id="summaryView">
                <label class="btn btn-outline-primary" for="summaryView">Summary View</label>
            </div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <table id="ordersTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Order No.</th>
                        <th>Cstr P.O.</th>
                        <th>Item</th>
                        <th>Due Date</th>
                        <th>Qty</th>
                        <th>Pndg</th>
                        <th>Done</th>
                        <th>Unit</th>
                        <th>Total</th>
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

<!-- Enter Order Modal -->
<div class="modal fade" id="enterOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Customer Details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Date</label>
                            <input type="date" class="form-control" name="order_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                    </div>
                    
                    <!-- Order Type Tabs -->
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button class="nav-link active" type="button" data-order-type="sales">Sales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" type="button" data-order-type="service">Service</button>
                        </li>
                    </ul>
                    <input type="hidden" name="order_type" value="sales">
                    
                    <!-- Executives -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sales Executive</label>
                            <select class="form-select" name="executive_id">
                                <option value="">Select Executive</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsible Executive</label>
                            <select class="form-select" name="responsible_id">
                                <option value="">Select Executive</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" rows="2"></textarea>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="same_as_billing" id="sameAsBilling">
                                <label class="form-check-label" for="sameAsBilling">
                                    Same as Billing Address
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <h6 class="mb-2">Items</h6>
                    <div id="quickItemsContainer">
                        <div class="row mb-2 quick-item-row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="items[0][description]" placeholder="Item Description" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="items[0][quantity]" placeholder="Qty" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control" name="items[0][unit]" placeholder="Unit" value="nos">
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="items[0][rate]" placeholder="Rate" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <textarea class="form-control" name="items[0][notes]" placeholder="Notes" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mb-3" id="addQuickItemBtn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                    
                    <!-- Update Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_by_email" id="updateEmail">
                                <label class="form-check-label" for="updateEmail">
                                    Update by Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_by_whatsapp" id="updateWhatsapp">
                                <label class="form-check-label" for="updateWhatsapp">
                                    Update by WhatsApp
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enter Delivery Modal -->
<div class="modal fade" id="enterDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deliveryForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Customer Details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" name="delivery_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                    </div>
                    
                    <!-- Executives -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sales Executive</label>
                            <select class="form-select" name="sales_executive_id">
                                <option value="">Select Executive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsible Executive</label>
                            <select class="form-select" name="responsible_executive_id">
                                <option value="">Select Executive</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" rows="2"></textarea>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="same_as_billing" id="deliverySameAsBilling">
                                <label class="form-check-label" for="deliverySameAsBilling">
                                    Same as Billing Address
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Details -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Delivery Details</label>
                            <textarea class="form-control" name="delivery_details" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <h6 class="mb-2">Items</h6>
                    <div id="deliveryItemsContainer">
                        <div class="row mb-2 delivery-item-row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="items[0][description]" placeholder="Item Description" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="items[0][quantity]" placeholder="Qty" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control" name="items[0][unit]" placeholder="Unit" value="nos">
                            </div>
                            <div class="col-md-4">
                                <textarea class="form-control" name="items[0][notes]" placeholder="Notes" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mb-3" id="addDeliveryItemBtn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                    
                    <!-- Recovery -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Recovery Amount</label>
                            <input type="number" class="form-control" name="recovery_amount" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Add Recovery</label>
                            <input type="number" class="form-control" name="add_recovery" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <!-- Upload Invoice -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-outline-primary">
                                <i class="fas fa-upload"></i> Upload Invoice
                            </button>
                        </div>
                    </div>
                    
                    <!-- Update Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_by_email" id="deliveryUpdateEmail">
                                <label class="form-check-label" for="deliveryUpdateEmail">
                                    Update by Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_by_whatsapp" id="deliveryUpdateWhatsapp">
                                <label class="form-check-label" for="deliveryUpdateWhatsapp">
                                    Update by WhatsApp
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/orders.js"></script>
