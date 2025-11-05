<?php
/**
 * Check Existing Tables and Report What's Missing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

$database = Database::getInstance();
$db = $database->getConnection();

echo "<h1>Database Tables Check</h1>";

// Get all existing tables
$stmt = $db->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Existing Tables in Database:</h2>";
echo "<ul>";
foreach ($existingTables as $table) {
    echo "<li>✅ $table</li>";
}
echo "</ul>";

// Required tables for Production Jobs
$requiredTables = ['production_jobs', 'production_job_items', 'production_job_stages', 'products', 'customers', 'users'];

echo "<h2>Required Tables Check:</h2>";
$missingTables = [];
echo "<ul>";
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "<li>✅ $table (exists)</li>";
    } else {
        echo "<li>❌ $table (MISSING)</li>";
        $missingTables[] = $table;
    }
}
echo "</ul>";

if (empty($missingTables)) {
    echo "<h2 style='color: green;'>✅ All required tables exist!</h2>";
    echo '<a href="production_jobs.php">Go to Production Jobs</a>';
} else {
    echo "<h2 style='color: red;'>❌ Missing Tables:</h2>";
    echo "<p>The following tables need to be created: " . implode(", ", $missingTables) . "</p>";
}

// Check if tables have data
echo "<h2>Table Row Counts:</h2>";
echo "<ul>";
foreach ($existingTables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<li>$table: <strong>$count</strong> rows</li>";
    } catch (Exception $e) {
        echo "<li>$table: Error checking count</li>";
    }
}
echo "</ul>";
?>
