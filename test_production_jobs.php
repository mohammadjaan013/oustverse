<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Production Jobs Page Dependencies</h2>";

// Test 1: Check if includes directory exists
echo "<h3>1. Checking includes directory...</h3>";
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "✅ config.php exists<br>";
    require_once __DIR__ . '/includes/config.php';
} else {
    echo "❌ config.php NOT found<br>";
}

if (file_exists(__DIR__ . '/includes/db.php')) {
    echo "✅ db.php exists<br>";
    require_once __DIR__ . '/includes/db.php';
} else {
    echo "❌ db.php NOT found<br>";
}

if (file_exists(__DIR__ . '/includes/header.php')) {
    echo "✅ header.php exists<br>";
} else {
    echo "❌ header.php NOT found<br>";
}

if (file_exists(__DIR__ . '/includes/footer.php')) {
    echo "✅ footer.php exists<br>";
} else {
    echo "❌ footer.php NOT found<br>";
}

// Test 2: Check models
echo "<h3>2. Checking models...</h3>";
if (file_exists(__DIR__ . '/models/ProductionJob.php')) {
    echo "✅ ProductionJob.php exists<br>";
    try {
        require_once __DIR__ . '/models/ProductionJob.php';
        echo "✅ ProductionJob.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error loading ProductionJob.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ ProductionJob.php NOT found<br>";
}

// Test 3: Check database connection
echo "<h3>3. Testing database connection...</h3>";
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if tables exist
    $tables = ['production_jobs', 'production_job_items', 'production_job_stages', 'items', 'customers'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Try to instantiate ProductionJob model
echo "<h3>4. Testing ProductionJob model...</h3>";
try {
    $productionJob = new ProductionJob($db);
    echo "✅ ProductionJob model instantiated successfully<br>";
    
    // Try to get statistics
    $stats = $productionJob->getStatistics();
    echo "✅ Statistics retrieved: " . json_encode($stats) . "<br>";
} catch (Exception $e) {
    echo "❌ Error with ProductionJob model: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 5: Check JavaScript file
echo "<h3>5. Checking JavaScript file...</h3>";
if (file_exists(__DIR__ . '/assets/js/production_jobs.js')) {
    echo "✅ production_jobs.js exists<br>";
} else {
    echo "❌ production_jobs.js NOT found<br>";
}

// Test 6: Check session
echo "<h3>6. Checking session...</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in (ID: " . $_SESSION['user_id'] . ")<br>";
} else {
    echo "❌ User is NOT logged in<br>";
}

echo "<h3>All tests complete!</h3>";
echo "<p><a href='production_jobs.php'>Try Production Jobs Page</a></p>";
?>
