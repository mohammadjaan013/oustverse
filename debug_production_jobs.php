<?php
/**
 * Debug Production Jobs
 * Test if everything is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Production Jobs Debug</h1>";

// Test 1: Check if config loads
echo "<h2>Test 1: Config</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Config loaded successfully<br>";
    echo "BASE_URL: " . BASE_URL . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    require_once 'includes/db.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if production_jobs table exists
echo "<h2>Test 3: Check Tables</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'production_jobs'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ production_jobs table exists<br>";
        
        // Check structure
        $stmt = $db->query("DESCRIBE production_jobs");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(", ", $columns) . "<br>";
    } else {
        echo "❌ production_jobs table NOT found<br>";
    }
} catch (Exception $e) {
    echo "❌ Table check error: " . $e->getMessage() . "<br>";
}

// Test 4: Load ProductionJob model
echo "<h2>Test 4: ProductionJob Model</h2>";
try {
    require_once 'models/ProductionJob.php';
    $productionJobModel = new ProductionJob($db);
    echo "✅ ProductionJob model loaded<br>";
} catch (Exception $e) {
    echo "❌ Model error: " . $e->getMessage() . "<br>";
}

// Test 5: Get statistics
echo "<h2>Test 5: Get Statistics</h2>";
try {
    $stats = $productionJobModel->getStatistics();
    echo "✅ Statistics retrieved<br>";
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Statistics error: " . $e->getMessage() . "<br>";
}

// Test 6: Get all jobs
echo "<h2>Test 6: Get All Jobs</h2>";
try {
    $jobs = $productionJobModel->getAll();
    echo "✅ Jobs retrieved: " . count($jobs) . " jobs found<br>";
    if (count($jobs) > 0) {
        echo "<pre>";
        print_r($jobs);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Get jobs error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>If all tests pass:</h2>";
echo '<a href="production_jobs.php">Go to Production Jobs Page</a>';
?>
