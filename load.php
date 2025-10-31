<?php
header('Content-Type: application/json');
$file = __DIR__ . '/rides.json';
if (!file_exists($file)) { echo '[]'; exit; }
readfile($file);
?>
