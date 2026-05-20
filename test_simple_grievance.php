<?php
// Simple grievance test without Laravel complexity
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Grievance Test</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.card{background:#f8f9fa;padding:20px;border-radius:10px;margin:10px 0;}</style>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Test if grievances table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM grievances");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ grievances table accessible, {$result['count']} records found</p>";
    
    // Test grievance_responses table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM grievance_responses");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ grievance_responses table accessible, {$result['count']} records found</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
}

// Create sample data if tables are empty
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if grievances table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM grievances");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<p class='info'>ℹ️ Creating sample grievance data...</p>";
        
        // Insert sample grievance
        $sql = "INSERT INTO grievances (user_id, category, title, description, status, is_anonymous, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([1, 'HR', 'Sample Grievance', 'This is a sample grievance for testing purposes.', 'open', false]);
        
        echo "<p class='success'>✅ Sample grievance created</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error creating sample data: " . $e->getMessage() . "</p>";
}

// Display current grievances
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Current Grievances:</h3>";
    $stmt = $pdo->query("SELECT * FROM grievances ORDER BY created_at DESC");
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='card'>";
        while ($row = $stmt->fetch()) {
            echo "<div style='border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:5px;'>";
            echo "<h4>" . htmlspecialchars($row['title']) . "</h4>";
            echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($row['status']) . "</p>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
            echo "<p><small><strong>Created:</strong> " . $row['created_at'] . "</small></p>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>No grievances found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching grievances: " . $e->getMessage() . "</p>";
}

// Test Laravel route access
echo "<h3>Laravel Route Test:</h3>";
echo "<div class='card'>";
echo "<p><strong>Direct Grievance Route:</strong></p>";
echo "<a href='/hrms/grievances' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;margin:5px;'>Try Grievances Route</a>";
echo "<a href='/hrms/grievances/create' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;margin:5px;'>Try Create Route</a>";
echo "</div>";

// Laravel troubleshooting
echo "<h3>Laravel Troubleshooting:</h3>";
echo "<div class='card'>";
echo "<p><strong>If routes still give 500 error, try these fixes:</strong></p>";
echo "<ol>";
echo "<li>Clear Laravel caches:</li>";
echo "<code>php artisan cache:clear</code><br>";
echo "<code>php artisan config:clear</code><br>";
echo "<code>php artisan view:clear</code><br>";
echo "<code>php artisan route:clear</code><br>";
echo "<li>Restart Apache server</li>";
echo "<li>Check .env file settings</li>";
echo "<li>Run: <code>php artisan migrate:fresh --seed</code></li>";
echo "</ol>";
echo "</div>";

?>
