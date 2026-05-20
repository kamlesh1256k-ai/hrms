<?php
// Quick Database Setup for Grievance Module
// Direct database connection approach

// Database configuration
$host = 'localhost';
$dbname = 'hrms'; // Change this to your database name
$username = 'root'; // Change this to your database username
$password = ''; // Change this to your database password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Grievance Module Database Setup</h2>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.code{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";
    
    // Create grievances table
    $sql1 = "CREATE TABLE IF NOT EXISTS grievances (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL COMMENT 'Nullable for anonymous complaints',
        category VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',
        is_anonymous BOOLEAN DEFAULT FALSE,
        anonymous_token TEXT NULL UNIQUE COMMENT 'Token for tracking anonymous complaints',
        assigned_to BIGINT UNSIGNED NULL COMMENT 'HR/Admin assigned to handle',
        resolved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status_created (status, created_at),
        INDEX idx_category_status (category, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql1);
        echo "<p class='success'>✅ grievances table created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error creating grievances table: " . $e->getMessage() . "</p>";
    }
    
    // Create grievance_responses table
    $sql2 = "CREATE TABLE IF NOT EXISTS grievance_responses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        grievance_id BIGINT UNSIGNED NOT NULL,
        responder_id BIGINT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        response_type ENUM('hr_response', 'employee_reply', 'system_note') DEFAULT 'hr_response',
        is_internal_note BOOLEAN DEFAULT FALSE COMMENT 'Visible only to HR staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_grievance_created (grievance_id, created_at),
        INDEX idx_responder_created (responder_id, created_at),
        FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE CASCADE,
        FOREIGN KEY (responder_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql2);
        echo "<p class='success'>✅ grievance_responses table created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error creating grievance_responses table: " . $e->getMessage() . "</p>";
    }
    
    // Check if tables exist
    $check1 = $pdo->query("SHOW TABLES LIKE 'grievances'");
    $check2 = $pdo->query("SHOW TABLES LIKE 'grievance_responses'");
    
    if ($check1->rowCount() > 0 && $check2->rowCount() > 0) {
        echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;'>";
        echo "<h3>🎉 Setup Complete!</h3>";
        echo "<p>Database tables are ready. You can now use the grievance module:</p>";
        echo "<div class='code'>";
        echo "<a href='http://localhost/hrms/grievances' style='color:white;background:#007bff;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>View Grievances</a><br><br>";
        echo "<a href='http://localhost/hrms/grievances/create' style='color:white;background:#28a745;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>Raise Grievance</a><br><br>";
        echo "<a href='http://localhost/hrms/dashboard' style='color:white;background:#17a2b8;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>Dashboard</a>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='error' style='background:#f8d7da;padding:20px;border-radius:5px;margin:20px 0;'>";
        echo "<h3>❌ Setup Failed</h3>";
        echo "<p>Please check your database connection and try again.</p>";
        echo "</div>";
    }
    
    // Show current tables
    echo "<h3>Current Database Tables:</h3>";
    $tables = $pdo->query("SHOW TABLES");
    while ($row = $tables->fetch()) {
        echo "<p>" . $row[0] . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>Database Connection Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration:</p>";
    echo "<div class='code'>";
    echo "Host: $host<br>";
    echo "Database: $dbname<br>";
    echo "Username: $username<br>";
    echo "Password: $password";
    echo "</div>";
    echo "</div>";
}
?>
