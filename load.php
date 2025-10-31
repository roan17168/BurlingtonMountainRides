<?php
// load.php — send back the rides.json content
header("Content-Type: application/json");

$file = "rides.json";

if (!file_exists($file)) {
  echo "[]";
  exit;
}

echo file_get_contents($file);
?>
