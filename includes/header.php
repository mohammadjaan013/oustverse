<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $pageTitle ?? SITE_NAME; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    <span class="logo-text">OUSTFIRE</span>
                </div>
            </div>

            <div class="sidebar-menu">
                <!-- Navigation Icons -->
                <div class="nav-icons">
                    <a href="<?php echo BASE_URL; ?>/" class="nav-icon" title="Home">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="#" class="nav-icon" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                    <a href="#" class="nav-icon" title="Search">
                        <i class="fas fa-search"></i>
                    </a>
                    <a href="#" class="nav-icon" title="Settings">
                        <i class="fas fa-cog"></i>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="nav-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>

                <!-- Sales Section -->
                <div class="menu-section">
                    <div class="menu-section-title">Sales</div>
                    <ul class="menu-list">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/crm.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'crm.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie"></i>
                                <span>CRM</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/quotations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'quotations.php' || basename($_SERVER['PHP_SELF']) == 'quotation_form.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-invoice"></i>
                                <span>Quotes</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Orders</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/invoices.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'invoices.php' || basename($_SERVER['PHP_SELF']) == 'invoice_form.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Invoices</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Recovery" class="<?php echo basename($_SERVER['PHP_SELF']) == 'recovery.php' ? 'active' : ''; ?>">
                                <i class="fas fa-undo-alt"></i>
                                <span>Recovery</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Contracts" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contracts.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-contract"></i>
                                <span>Contracts</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Support" class="<?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>">
                                <i class="fas fa-headset"></i>
                                <span>Support</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Customers" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Customers</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- ERP Section -->
                <div class="menu-section">
                    <div class="menu-section-title">ERP</div>
                    <ul class="menu-list">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Accounts" class="<?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : ''; ?>">
                                <i class="fas fa-calculator"></i>
                                <span>Accounts</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/purchases.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'purchases.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-invoice"></i>
                                <span>Purchases</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/purchase_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'purchase_orders.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i>
                                <span>Purch Orders</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                                <i class="fas fa-boxes"></i>
                                <span>Inventory</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/production_jobs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'production_jobs.php' ? 'active' : ''; ?>">
                                <i class="fas fa-industry"></i>
                                <span>Manufacturing</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Tasks" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tasks"></i>
                                <span>Tasks</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/suppliers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
                                <i class="fas fa-truck"></i>
                                <span>Suppliers</span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 0.6rem;">Live</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Network Section -->
                <div class="menu-section">
                    <div class="menu-section-title">Network</div>
                    <ul class="menu-list">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Connections" class="<?php echo basename($_SERVER['PHP_SELF']) == 'connections.php' ? 'active' : ''; ?>">
                                <i class="fas fa-network-wired"></i>
                                <span>Connections</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Your Store" class="<?php echo basename($_SERVER['PHP_SELF']) == 'store.php' ? 'active' : ''; ?>">
                                <i class="fas fa-store"></i>
                                <span>Your Store</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Search" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>">
                                <i class="fas fa-search"></i>
                                <span>Search</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/under_development.php?module=Reports" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-light">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="ms-auto d-flex align-items-center">
                        <button class="btn btn-warning btn-sm me-2">
                            <i class="fas fa-question-circle"></i> Need help?
                        </button>
                        <button class="btn btn-warning btn-sm me-3">
                            <i class="fas fa-graduation-cap"></i> Access Training
                        </button>
                        
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown">
                                <div class="avatar me-2">
                                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                </div>
                                <span class="text-dark"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                            <?php if (isset($pageSubtitle)): ?>
                                <p class="text-muted"><?php echo $pageSubtitle; ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($pageActions)): ?>
                            <div class="col-auto">
                                <?php echo $pageActions; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
            <div class="container-fluid mt-3">
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content Area -->
            <div class="container-fluid py-4">
