<?php
// save.php — append a new ride to rides.json

header("Content-Type: application/json");

// get raw POST body
$data = file_get_contents("php://input");
if (!$data) {
  http_response_code(400);
  echo json_encode(["error" => "No data received"]);
  exit;
}

$newRide = json_decode($data, true);
if (!$newRide) {
  http_response_code(400);
  echo json_encode(["error" => "Invalid JSON"]);
  exit;
}

// read existing rides
$file = "rides.json";
$rides = [];
if (file_exists($file)) {
  $rides = json_decode(file_get_contents($file), true);
  if (!is_array($rides)) $rides = [];
}

// assign an id if not provided
if (!isset($newRide["id"])) {
  $newRide["id"] = uniqid("ride_");
}

$rides[] = $newRide;

// write back to file (with lock to avoid overlap)
file_put_contents($file, json_encode($rides, JSON_PRETTY_PRINT), LOCK_EX);

echo json_encode(["success" => true, "rides" => $rides]);
?>
