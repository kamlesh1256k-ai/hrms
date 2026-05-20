<?php
$pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');

$columns = [
    '`device_type` varchar(255) NULL AFTER `created_by`',
    '`latitude` varchar(255) NULL AFTER `device_type`',
    '`longitude` varchar(255) NULL AFTER `latitude`',
    '`address` longtext NULL AFTER `longitude`',
    '`photo` varchar(255) NULL AFTER `address`',
    '`device_type_out` varchar(255) NULL AFTER `photo`',
    '`latitude_out` varchar(255) NULL AFTER `device_type_out`',
    '`longitude_out` varchar(255) NULL AFTER `latitude_out`',
    '`address_out` longtext NULL AFTER `longitude_out`',
    '`photo_out` varchar(255) NULL AFTER `address_out`',
];

foreach ($columns as $col) {
    $sql = "ALTER TABLE attendance_employees ADD COLUMN $col";
    try {
        $pdo->exec($sql);
        echo "✓ Added column\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Verify columns now exist
$query = "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION SEPARATOR ', ') as columns FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'hrm_software' AND TABLE_NAME = 'attendance_employees'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n\nVerification - Photo columns:\n";
$cols = explode(', ', $result['columns']);
$photoRelated = array_filter($cols, function($c) {
    return strpos($c, 'photo') !== false || 
           strpos($c, 'latitude') !== false || 
           strpos($c, 'longitude') !== false ||
           strpos($c, 'address') !== false ||
           strpos($c, 'device') !== false;
});

if (empty($photoRelated)) {
    echo "NONE FOUND\n";
} else {
    foreach ($photoRelated as $col) {
        echo "  ✓ $col\n";
    }
}
?>
