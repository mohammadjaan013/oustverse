<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Testing Production Jobs Page Load</h2>";
echo "<p>Let's try to include the production_jobs page files step by step...</p>";

try {
    echo "<h3>Step 1: Including config.php...</h3>";
    require_once 'includes/config.php';
    echo "✅ config.php included successfully<br>";
    
    echo "<h3>Step 2: Including db.php...</h3>";
    require_once 'includes/db.php';
    echo "✅ db.php included successfully<br>";
    
    echo "<h3>Step 3: Including ProductionJob model...</h3>";
    require_once 'models/ProductionJob.php';
    echo "✅ ProductionJob model included successfully<br>";
    
    echo "<h3>Step 4: Checking session...</h3>";
    if (!isset($_SESSION['user_id'])) {
        echo "❌ User not logged in - this is why page redirects!<br>";
        echo "<p><a href='login.php'>Please login first</a></p>";
        exit;
    } else {
        echo "✅ User logged in (ID: " . $_SESSION['user_id'] . ")<br>";
    }
    
    echo "<h3>Step 5: Getting database connection...</h3>";
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
    
    echo "<h3>Step 6: Creating ProductionJob instance...</h3>";
    $productionJobModel = new ProductionJob($db);
    echo "✅ ProductionJob model instantiated<br>";
    
    echo "<h3>Step 7: Getting statistics...</h3>";
    $stats = $productionJobModel->getStatistics();
    echo "✅ Statistics: " . json_encode($stats) . "<br>";
    
    echo "<h3>✅ All steps successful! The page should work now.</h3>";
    echo "<p><a href='production_jobs.php'>Go to Production Jobs Page</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
