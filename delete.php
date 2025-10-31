<?php
header('Content-Type: application/json');
$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$input = json_decode($body, true);
$id = $input['id'] ?? '';
$phone = $input['phone_number'] ?? '';
if (!$id || !$phone) { http_response_code(400); echo json_encode(['error'=>'Missing id or phone_number']); exit; }

function norm_phone($p){ return preg_replace('/\D+/', '', $p ?? ''); }
$np = norm_phone($phone);

$file = __DIR__ . '/rides.json';
$rides = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($rides)) $rides = [];

$found = false; $new = [];
foreach ($rides as $r) {
  if ($r['id'] === $id) {
    if (norm_phone($r['phone']) === $np) { $found = true; continue; } // skip (delete)
    else { echo json_encode(['success'=>false, 'error'=>'Phone number does not match creator']); exit; }
  }
  $new[] = $r;
}

if (!$found) { echo json_encode(['success'=>false, 'error'=>'Ride not found']); exit; }
file_put_contents($file, json_encode($new, JSON_PRETTY_PRINT), LOCK_EX);
echo json_encode(['success'=>true, 'rides'=>$new]);
?>
