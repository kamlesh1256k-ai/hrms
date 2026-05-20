<?php
$pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');
$query = "SHOW COLUMNS FROM attendance_employees LIKE '%photo%'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "COLUMN: " . $row['Field'] . "\n";
}

if (empty($results)) {
    echo "NO PHOTO COLUMNS FOUND\n";
}
?>
