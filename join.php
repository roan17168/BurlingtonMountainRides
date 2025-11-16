<?php
header('Content-Type: application/json');

$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$input = json_decode($body, true);
$id = $input['id'] ?? '';
if(!$id){ http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

$file = __DIR__ . '/rides.json';
$rides = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if(!is_array($rides)) $rides = [];

$found = null;
foreach($rides as &$r){
  if($r['id'] === $id){
    if(($r['seatsLeft'] ?? 0) > 0){
      $r['seatsLeft'] = (int)$r['seatsLeft'] - 1;
      $found = $r;
    } else {
      echo json_encode(['success'=>false,'error'=>'No seats left']);
      exit;
    }
  }
}
unset($r);

if(!$found){
  echo json_encode(['success'=>false,'error'=>'Ride not found']);
  exit;
}

file_put_contents($file, json_encode($rides, JSON_PRETTY_PRINT), LOCK_EX);
echo json_encode(['success'=>true,'rides'=>$rides,'ride'=>$found]);
