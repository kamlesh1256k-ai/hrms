<?php
$pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');
$query = "DESC attendance_employees";
$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "All columns in attendance_employees:\n";
echo str_repeat("=", 50) . "\n";
foreach ($results as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
