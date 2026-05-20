<?php
$pdo = new PDO('mysql:host=localhost;dbname=hrm_software', 'root', '');
$query = "SELECT migration FROM migrations ORDER BY batch DESC LIMIT 10";
$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Latest migrations applied:\n";
foreach ($results as $migration) {
    echo "  - $migration\n";
}
?>
