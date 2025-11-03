<?php
/**
 * Common Helper Functions
 */

require_once __DIR__ . '/config.php';

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime) {
    return formatDate($datetime, DISPLAY_DATETIME_FORMAT);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

/**
 * Create slug from string
 */
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'approved' => '<span class="badge bg-info">Approved</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
    ];
    
    return $badges[strtolower($status)] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Send JSON response
 */
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Log audit trail
 */
function logAudit($action, $tableName, $recordId, $before = null, $after = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $db = getDB();
        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, before_data, after_data, ip_address, created_at) 
                VALUES (:user_id, :action, :table_name, :record_id, :before_data, :after_data, :ip_address, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'before_data' => $before ? json_encode($before) : null,
            'after_data' => $after ? json_encode($after) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Redirect with message
 */
function redirectWith($url, $type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Generate unique code
 */
function generateUniqueCode($prefix = '', $length = 8) {
    return $prefix . strtoupper(uniqid() . substr(md5(time()), 0, $length));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10;
}
