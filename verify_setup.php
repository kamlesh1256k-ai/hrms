<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');
    
    // Get all columns from attendance_employees
    $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'attendance_employees' 
              AND TABLE_SCHEMA = 'hrm_software'
              ORDER BY ORDINAL_POSITION";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $allColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "All columns in attendance_employees table:\n";
    echo str_repeat("=", 50) . "\n";
    foreach ($allColumns as $col) {
        echo "  - $col\n";
    }
    
    // Check specific photo columns
    echo "\n\nPhoto & Location Columns:\n";
    echo str_repeat("=", 50) . "\n";
    $photoColumns = ['device_type', 'latitude', 'longitude', 'address', 'photo', 
                    'device_type_out', 'latitude_out', 'longitude_out', 'address_out', 'photo_out'];
    
    foreach ($photoColumns as $col) {
        $exists = in_array($col, $allColumns);
        $status = $exists ? '✓' : '❌';
        echo "$status $col\n";
    }
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

// Check if uploads directory exists and is writable
echo "\n\nUploads directory check:\n";
echo str_repeat("=", 50) . "\n";
$uploadDir = __DIR__ . '/public/uploads/attendance';
if (is_dir($uploadDir)) {
    echo "✓ Directory exists: $uploadDir\n";
    $files = glob($uploadDir . '/*');
    echo "  Files: " . count($files) . "\n";
    if (is_writable($uploadDir)) {
        echo "✓ Directory is writable\n";
    } else {
        echo "❌ Directory is NOT writable\n";
    }
} else {
    echo "❌ Directory does NOT exist\n";
    echo "Creating: $uploadDir\n";
    @mkdir($uploadDir, 0777, true);
    if (is_dir($uploadDir)) {
        echo "✓ Created successfully\n";
    } else {
        echo "❌ Failed to create\n";
    }
}
