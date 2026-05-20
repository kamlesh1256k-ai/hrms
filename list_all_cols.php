<?php
$pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');
$query = "DESC attendance_employees";
$result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

echo "All columns in attendance_employees table:\n";
echo "==========================================\n";
foreach ($result as $row) {
    echo($row['Field'] . "\n");
}

echo "\n\nPhoto-related columns found:\n";
$photoFound = 0;
foreach ($result as $row) {
    $field = $row['Field'];
    if (strpos($field, 'photo') !== false || strpos($field, 'latitude') !== false || 
        strpos($field, 'longitude') !== false || strpos($field, 'address') !== false ||
        strpos($field, 'device') !== false) {
        echo "  ✓ $field\n";
        $photoFound++;
    }
}

if ($photoFound == 0) {
    echo "  ❌ NONE FOUND\n";
}
?>
