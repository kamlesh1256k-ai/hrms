<?php
$env = file_get_contents(__DIR__ . '/.env');
echo "<h2>Server .env contents:</h2>";
echo "<pre>" . htmlspecialchars($env) . "</pre>";
