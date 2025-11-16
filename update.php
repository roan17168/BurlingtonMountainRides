<?php
header('Content-Type: application/json');

$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$input = json_decode($body, true);

$id        = $input['id'] ?? '';
$phone     = $input['phone_number'] ?? '';
$new_left  = isset($input['new_seats_left']) ? (int)$input['new_seats_left'] : null;

if(!$id || !$phone || $new_left === null){
  http_response_code(400);
  echo json_encode(['error'=>'Missing id, phone_number, or new_seats_left']);
  exit;
}

function norm_phone($p){ return preg_replace('/\D+/','',$p ?? ''); }

$file = __DIR__ . '/rides.json';
$rides = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if(!is_array($rides)) $rides = [];

$np = norm_phone($phone);
$updated = false;

foreach($rides as &$r){
  if($r['id'] === $id){
    if(norm_phone($r['phone']) !== $np){
      echo json_encode(['success'=>false,'error'=>'Phone number does not match creator']);
      exit;
    }
    $max = isset($r['seats']) ? (int)$r['seats'] : 0;
    if($max <= 0){
      echo json_encode(['success'=>false,'error'=>'Invalid total seats']);
      exit;
    }
    if($new_left < 0)      $new_left = 0;
    if($new_left > $max)   $new_left = $max;

    $r['seatsLeft'] = $new_left;
    $updated = true;
    break;
  }
}
unset($r);

if(!$updated){
  echo json_encode(['success'=>false,'error'=>'Ride not found']);
  exit;
}

file_put_contents($file, json_encode($rides, JSON_PRETTY_PRINT), LOCK_EX);
echo json_encode(['success'=>true,'rides'=>$rides]);
