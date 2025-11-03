<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Log audit trail before logging out
if (isLoggedIn()) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, ip_address, created_at) 
                              VALUES (:user_id, 'logout', 'users', :ip_address, NOW())");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Silent fail
    }
}

// Perform logout
logoutUser();

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit;
