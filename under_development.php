<?php
/**
 * Under Development Page
 * Template for modules that are being built
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Get module name from URL parameter
$moduleName = isset($_GET['module']) ? htmlspecialchars($_GET['module']) : 'This Module';

$pageTitle = $moduleName . ' - Under Development';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <div class="under-development-container">
                    <div class="icon-wrapper mb-4">
                        <i class="fas fa-tools fa-5x text-primary" style="animation: rotate 4s linear infinite;"></i>
                    </div>
                    
                    <h1 class="display-4 mb-3">
                        <i class="fas fa-hard-hat"></i> Under Construction
                    </h1>
                    
                    <h2 class="h3 mb-4 text-muted">
                        <?php echo $moduleName; ?>
                    </h2>
                    
                    <div class="alert alert-info d-inline-block mx-auto mb-4" style="max-width: 600px;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Coming Soon!</strong>
                        <p class="mb-0 mt-2">
                            This module is currently under development. Our team is working hard to bring you this feature.
                            Please check back later or explore our other available modules.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <div class="progress mx-auto" style="max-width: 400px; height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 style="width: 35%;" 
                                 aria-valuenow="35" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                35% Complete
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group mb-5" role="group">
                        <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Go to Dashboard
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                    </div>
                    
                    <div class="available-modules-section mt-5 pt-5 border-top">
                        <h3 class="h4 mb-4">
                            <i class="fas fa-check-circle text-success"></i> 
                            Available Modules
                        </h3>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card h-100 shadow-sm hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                                        <h5 class="card-title">Inventory</h5>
                                        <p class="card-text small text-muted">Manage your stock items</p>
                                        <a href="<?php echo BASE_URL; ?>/inventory.php" class="btn btn-sm btn-outline-primary">
                                            Open <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card h-100 shadow-sm hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-truck fa-3x text-success mb-3"></i>
                                        <h5 class="card-title">Suppliers</h5>
                                        <p class="card-text small text-muted">Manage supplier details</p>
                                        <a href="<?php echo BASE_URL; ?>/suppliers.php" class="btn btn-sm btn-outline-success">
                                            Open <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card h-100 shadow-sm hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                                        <h5 class="card-title">Purchase Orders</h5>
                                        <p class="card-text small text-muted">Create and track POs</p>
                                        <a href="<?php echo BASE_URL; ?>/purchase_orders.php" class="btn btn-sm btn-outline-warning">
                                            Open <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card h-100 shadow-sm hover-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-invoice fa-3x text-info mb-3"></i>
                                        <h5 class="card-title">Purchases</h5>
                                        <p class="card-text small text-muted">Supplier invoices</p>
                                        <a href="<?php echo BASE_URL; ?>/purchases.php" class="btn btn-sm btn-outline-info">
                                            Open <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 pt-4">
                        <p class="text-muted">
                            <i class="far fa-clock"></i> Expected completion: Q1 2025
                        </p>
                        <p class="text-muted small">
                            Have questions? <a href="mailto:support@biziverse.com">Contact Support</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.hover-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
}

.under-development-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.icon-wrapper {
    display: inline-block;
}

.btn-group .btn {
    margin: 0 5px;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin: 5px 0;
        width: 100%;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
