<?php
// Test the ProductionJobController directly
require_once 'includes/config.php';
require_once 'includes/db.php';

// Simulate logged-in user
$_SESSION['user_id'] = 1;

echo "<h2>Testing ProductionJobController</h2>";

// Test the getJobs action
$_GET['action'] = 'getJobs';
$_GET['status'] = 'pending';

echo "<h3>Response from getJobs action:</h3>";
echo "<pre>";

// Capture output
ob_start();
include 'controllers/ProductionJobController.php';
$response = ob_get_clean();

echo htmlspecialchars($response);
echo "</pre>";

// Try to decode as JSON
echo "<h3>JSON Validation:</h3>";
$json = json_decode($response, true);
if ($json === null) {
    echo "❌ Invalid JSON! Error: " . json_last_error_msg() . "<br>";
    echo "Raw response length: " . strlen($response) . " bytes<br>";
} else {
    echo "✅ Valid JSON!<br>";
    echo "<pre>" . print_r($json, true) . "</pre>";
}
?>
