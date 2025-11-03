<?php
/**
 * Dashboard - Main Landing Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
requireLogin();

$pageTitle = 'Smart Business Console';
$pageSubtitle = 'for ' . ($_SESSION['company_name'] ?? 'Your Company');

// Fetch dashboard statistics
$db = getDB();

// Sales Module Stats
$leadsCount = 2; // Will be dynamic from database
$quotesCount = 2;
$ordersCount = 28;
$contractsCount = 0;
$billingAmount = 0;
$recoveryAmount = 0;

// ERP Module Stats
$accountsBalance = 0;
$stockCount = 188;
$purchasesAmount = 0;
$purchaseOrdersCount = 2;

// Try to fetch real data
try {
    // Get counts from database when tables exist
    // $stmt = $db->query("SELECT COUNT(*) as count FROM leads");
    // $leadsCount = $stmt->fetch()['count'];
} catch (Exception $e) {
    // Tables might not exist yet
}

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <!-- Sales Modules Section -->
    <div class="col-12">
        <div class="module-section">
            <h2><i class="fas fa-chart-line me-2"></i>Sales Modules</h2>
            <div class="row">
                <!-- Leads -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/crm.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Leads</h3>
                            <div class="card-value"><?php echo $leadsCount; ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Appointments -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/appointments.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Appointments</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Quotes -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/quotes.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Quotes (Nov)</h3>
                            <div class="card-value"><?php echo $quotesCount; ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Orders -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/orders.php" class="text-decoration-none">
                        <div class="dashboard-card primary">
                            <h3>Orders</h3>
                            <div class="card-value"><?php echo $ordersCount; ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Support -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/support.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Support</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Contracts -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/contracts.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Contracts</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Billing -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/billing.php" class="text-decoration-none">
                        <div class="dashboard-card success">
                            <h3>Billing (Nov)</h3>
                            <div class="card-value"><?php echo formatCurrency($billingAmount); ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Recovery -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/recovery.php" class="text-decoration-none">
                        <div class="dashboard-card success">
                            <h3>Recovery</h3>
                            <div class="card-value"><?php echo formatCurrency($recoveryAmount); ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Customers -->
                <div class="col-md-3 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/customers.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Customers</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- ERP Modules Section -->
    <div class="col-lg-8">
        <div class="module-section">
            <h2><i class="fas fa-cogs me-2"></i>ERP Modules</h2>
            <div class="row">
                <!-- Accounts (P&L) -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/accounts.php" class="text-decoration-none">
                        <div class="dashboard-card success">
                            <h3>Accounts (P&L)</h3>
                            <div class="card-value"><?php echo formatCurrency($accountsBalance); ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Stock -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/inventory.php" class="text-decoration-none">
                        <div class="dashboard-card primary">
                            <h3>Stock</h3>
                            <div class="card-value"><?php echo $stockCount; ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Prodn -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/manufacturing.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Prodn</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Purchases -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/purchases.php" class="text-decoration-none">
                        <div class="dashboard-card success">
                            <h3>Purchases (Nov)</h3>
                            <div class="card-value"><?php echo formatCurrency($purchasesAmount); ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Purchase Orders -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/purchase_orders.php" class="text-decoration-none">
                        <div class="dashboard-card primary">
                            <h3>Purchase Orders (Nov)</h3>
                            <div class="card-value"><?php echo $purchaseOrdersCount; ?></div>
                        </div>
                    </a>
                </div>
                
                <!-- Inbox -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/inbox.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Inbox</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Outbox -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/outbox.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Outbox</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Suppliers -->
                <div class="col-md-4 col-sm-6">
                    <a href="<?php echo BASE_URL; ?>/suppliers.php" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Suppliers</h3>
                            <div class="card-action">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Shortcuts Section -->
        <div class="module-section mt-4">
            <h2><i class="fas fa-bolt me-2"></i>Shortcuts</h2>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Appointments</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Deliveries</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Trade Profitability</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Valuable Items</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Low Stock</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Credit Notes</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Debit Notes</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Important Dates</h3>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="#" class="text-decoration-none">
                        <div class="dashboard-card">
                            <h3>Stock Shortfall</h3>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- Business Setup Progress -->
        <div class="dashboard-right-panel mb-4">
            <h5 class="mb-3">Get your Business Listed for Leads</h5>
            <p class="text-muted small">Five minutes to establish your stellar presence on Biziverse!</p>
            
            <div class="text-center my-4">
                <div class="progress-circle" style="--progress: 33">
                    33%
                </div>
            </div>
            
            <div class="d-grid">
                <button class="btn btn-primary">
                    <i class="fas fa-store me-2"></i>Set up Web Store
                </button>
            </div>
        </div>
        
        <!-- Newsfeed -->
        <div class="dashboard-right-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Newsfeed</h5>
                <button class="btn btn-warning btn-sm">
                    <i class="fas fa-plus me-1"></i>Create Post
                </button>
            </div>
            
            <div class="mb-4">
                <button class="btn btn-light w-100 text-start">
                    <i class="fas fa-edit me-2"></i>Write a post
                </button>
                <p class="text-muted small mt-2">Share news about your business with the Biziverse community!</p>
            </div>
            
            <!-- Sample Newsfeed Items -->
            <div class="newsfeed-item">
                <div class="d-flex justify-content-between">
                    <div class="flex-grow-1">
                        <div class="newsfeed-company">TECHLINK SURVEILLANCE SYSTEMS</div>
                        <div class="newsfeed-title">Annekal India</div>
                        <div class="newsfeed-description">
                            <i class="fas fa-thumbs-up text-warning"></i> Crystal Clear Vision – Day or Night Experience...
                        </div>
                        <button class="btn btn-sm btn-light mt-2">
                            <i class="fas fa-thumbs-up me-1"></i>Like
                        </button>
                    </div>
                    <img src="<?php echo BASE_URL; ?>/assets/images/placeholder.jpg" alt="Post" class="newsfeed-image ms-3">
                </div>
            </div>
            
            <div class="newsfeed-item">
                <div class="d-flex justify-content-between">
                    <div class="flex-grow-1">
                        <div class="newsfeed-company">TORQUE ELEVATORS LLP</div>
                        <div class="newsfeed-title">How to Choose the Right Elevator for Your Building</div>
                        <div class="newsfeed-description">
                            <i class="fas fa-file-alt text-muted"></i> How to Choose the Right Elevator for Your Build...
                        </div>
                        <button class="btn btn-sm btn-light mt-2">
                            <i class="fas fa-thumbs-up me-1"></i>Like
                        </button>
                    </div>
                    <img src="<?php echo BASE_URL; ?>/assets/images/placeholder.jpg" alt="Post" class="newsfeed-image ms-3">
                </div>
            </div>
            
            <div class="newsfeed-item">
                <div class="d-flex justify-content-between">
                    <div class="flex-grow-1">
                        <div class="newsfeed-company">VRUMI VEDIC GRUH UDYOG</div>
                        <div class="newsfeed-title">SUGAR FREE LIFE</div>
                        <div class="newsfeed-description">
                            With our Diabetes care Powder you can Control Sug...
                        </div>
                        <button class="btn btn-sm btn-light mt-2">
                            <i class="fas fa-thumbs-up me-1"></i>Like
                        </button>
                    </div>
                    <img src="<?php echo BASE_URL; ?>/assets/images/placeholder.jpg" alt="Post" class="newsfeed-image ms-3">
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
