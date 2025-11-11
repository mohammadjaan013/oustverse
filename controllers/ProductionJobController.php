<?php
/**
 * Production Job Controller
 * Handles AJAX requests for production jobs
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/ProductionJob.php';

// Require login
requireLogin();

// Get database connection
$db = getDB();

// Initialize models
$productionJobModel = new ProductionJob($db);

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getJobs':
        getJobs($productionJobModel);
        break;
    
    case 'getJob':
        getJob($productionJobModel);
        break;
    
    case 'create':
        createJob($productionJobModel);
        break;
    
    case 'update':
        updateJob($productionJobModel);
        break;
    
    case 'updateStatus':
        updateStatus($productionJobModel);
        break;
    
    case 'delete':
        deleteJob($productionJobModel);
        break;
    
    case 'getStatistics':
        getStatistics($productionJobModel);
        break;
    
    case 'getJobItems':
        getJobItems($productionJobModel);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get all production jobs (for DataTables)
 */
function getJobs($model) {
    $filters = [
        'status' => $_GET['status'] ?? '',
        'search' => $_GET['search'] ?? '',
        'overdue' => isset($_GET['overdue']) ? (bool)$_GET['overdue'] : false
    ];

    $jobs = $model->getAll($filters);

    // Format data for DataTables
    $data = [];
    foreach ($jobs as $job) {
        // Determine badge class based on status
        $statusClass = match($job['status']) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };

        // Check if overdue
        $isOverdue = $job['target_date'] < date('Y-m-d') && $job['status'] != 'completed';
        
        $data[] = [
            'id' => $job['id'],
            'wip_no' => $job['wip_no'],
            'product_name' => $job['product_name'],
            'product_code' => $job['product_code'],
            'customer_name' => $job['customer_name'] ?? 'N/A',
            'quantity' => number_format($job['quantity'], 2),
            'target_date' => date('d-M-Y', strtotime($job['target_date'])),
            'days_remaining' => $job['days_remaining'],
            'is_overdue' => $isOverdue,
            'status' => '<span class="badge bg-' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $job['status'])) . '</span>',
            'status_raw' => $job['status'],
            'created_at' => date('d-M-Y H:i', strtotime($job['created_at'])),
            'actions' => generateActionButtons($job)
        ];
    }

    echo json_encode(['data' => $data]);
}

/**
 * Get single production job
 */
function getJob($model) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }

    $job = $model->getById($id);
    
    if ($job) {
        echo json_encode(['success' => true, 'data' => $job]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
    }
}

/**
 * Create new production job
 */
function createJob($model) {
    $data = [
        'wip_no' => $_POST['wip_no'] ?? '',
        'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
        'product_id' => $_POST['product_id'] ?? '',
        'quantity' => $_POST['quantity'] ?? 1,
        'target_date' => $_POST['target_date'] ?? date('Y-m-d', strtotime('+7 days')),
        'status' => $_POST['status'] ?? 'pending',
        'special_instructions' => $_POST['special_instructions'] ?? '',
        'created_by' => $_SESSION['user_id']
    ];

    // Validation
    if (empty($data['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Product is required']);
        return;
    }

    $jobId = $model->create($data);
    
    if ($jobId) {
        echo json_encode([
            'success' => true, 
            'message' => 'Production job created successfully',
            'job_id' => $jobId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create production job']);
    }
}

/**
 * Update production job
 */
function updateJob($model) {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }

    $data = [
        'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
        'product_id' => $_POST['product_id'] ?? '',
        'quantity' => $_POST['quantity'] ?? 1,
        'target_date' => $_POST['target_date'] ?? date('Y-m-d'),
        'status' => $_POST['status'] ?? 'pending',
        'special_instructions' => $_POST['special_instructions'] ?? ''
    ];

    if ($model->update($id, $data)) {
        echo json_encode(['success' => true, 'message' => 'Production job updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update production job']);
    }
}

/**
 * Update production job status
 */
function updateStatus($model) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (!$id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Job ID and status are required']);
        return;
    }

    if ($model->updateStatus($id, $status)) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}

/**
 * Delete production job
 */
function deleteJob($model) {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }

    if ($model->delete($id)) {
        echo json_encode(['success' => true, 'message' => 'Production job deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete production job']);
    }
}

/**
 * Get statistics
 */
function getStatistics($model) {
    $stats = $model->getStatistics();
    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Get all products (for dropdown)
 */
function getProducts($model) {
    $products = $model->getAll();
    
    $data = array_map(function($product) {
        return [
            'id' => $product['id'],
            'text' => $product['name'] . ' (' . $product['code'] . ')',
            'name' => $product['name'],
            'code' => $product['code'],
            'unit' => $product['unit']
        ];
    }, $products);

    echo json_encode(['results' => $data]);
}

/**
 * Get all customers (for dropdown)
 */
function getCustomers($model) {
    $customers = $model->getAll();
    
    $data = array_map(function($customer) {
        return [
            'id' => $customer['id'],
            'text' => $customer['name'],
            'name' => $customer['name'],
            'email' => $customer['email']
        ];
    }, $customers);

    echo json_encode(['results' => $data]);
}

/**
 * Get production job items
 */
function getJobItems($model) {
    $jobId = $_GET['job_id'] ?? 0;
    
    if (!$jobId) {
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        return;
    }

    $items = $model->getJobItems($jobId);
    echo json_encode(['success' => true, 'data' => $items]);
}

/**
 * Generate action buttons for each row
 */
function generateActionButtons($job) {
    $buttons = '<div class="btn-group btn-group-sm" role="group">';
    
    // View button
    $buttons .= '<button class="btn btn-info view-job" data-id="' . $job['id'] . '" title="View">
                    <i class="fas fa-eye"></i>
                </button>';
    
    // Edit button (only for pending and in_progress jobs)
    if ($job['status'] != 'completed' && $job['status'] != 'cancelled') {
        $buttons .= '<button class="btn btn-primary edit-job" data-id="' . $job['id'] . '" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>';
    }
    
    // Status update buttons
    if ($job['status'] == 'pending') {
        $buttons .= '<button class="btn btn-success start-job" data-id="' . $job['id'] . '" title="Start Production">
                        <i class="fas fa-play"></i>
                    </button>';
    } elseif ($job['status'] == 'in_progress') {
        $buttons .= '<button class="btn btn-success complete-job" data-id="' . $job['id'] . '" title="Mark Complete">
                        <i class="fas fa-check"></i>
                    </button>';
    }
    
    // Delete button (only for pending jobs)
    if ($job['status'] == 'pending') {
        $buttons .= '<button class="btn btn-danger delete-job" data-id="' . $job['id'] . '" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>';
    }
    
    $buttons .= '</div>';
    
    return $buttons;
}
