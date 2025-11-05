<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Check items table structure
echo "<h2>Items Table Structure:</h2>";
$stmt = $db->query("DESCRIBE items");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
