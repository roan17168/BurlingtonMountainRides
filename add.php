<?php
header('Content-Type: application/json');

$body = file_get_contents('php://input');
if (!$body) {
  http_response_code(400);
  echo json_encode(['error' => 'No data']);
  exit;
}
$input = json_decode($body, true);
if (!$input) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

$file = __DIR__ . '/rides.json';
$rides = [];
if (file_exists($file)) {
  $data = file_get_contents($file);
  $rides = json_decode($data, true);
  if (!is_array($rides)) $rides = [];
}

function norm_time($t){
  if(!$t) return '';
  if(preg_match('/^\d{2}:\d{2}$/',$t)) return $t;
  if(preg_match('/^(\d{1,2}):(\d{2})\s*([AP]M)$/i',$t,$m)){
    $h = ((int)$m[1]) % 12;
    if(stripos($m[3],'P') !== false) $h += 12;
    return str_pad((string)$h,2,'0',STR_PAD_LEFT).':'.$m[2];
  }
  return $t;
}

// extract payment info from "Pitch In For Gas" or "payment"
$amount = 0; $method = 'Venmo'; $notes = '';
if (isset($input['Pitch In For Gas'])) {
  $pig = $input['Pitch In For Gas'];
  $amount = isset($pig['amount']) ? (float)$pig['amount'] : 0;
  $method = isset($pig['method']) ? $pig['method'] : 'Venmo';
  $notes  = isset($pig['notes'])  ? $pig['notes']  : '';
} elseif (isset($input['payment'])) {
  $amount = isset($input['payment']['amount']) ? (float)$input['payment']['amount'] : 0;
  $method = isset($input['payment']['method']) ? $input['payment']['method'] : 'Venmo';
  $notes  = isset($input['payment']['notes'])  ? $input['payment']['notes']  : '';
}
if (!in_array($method, ['Venmo','Cash'])) {
  $method = 'Venmo';
}

$ride = [
  'id'        => uniqid('ride_'),
  'date'      => $input['date_of_trip'] ?? ($input['date'] ?? ''),
  'depart'    => norm_time($input['aprox_dep'] ?? ($input['depart'] ?? '')),
  'mountain'  => $input['mountain_destination'] ?? ($input['mountain'] ?? ''),
  'amount'    => $amount,
  'method'    => $method,
  'notes'     => $notes,
  'seats'     => (int)($input['number_of_seats'] ?? $input['seats'] ?? 0),
  'seatsLeft' => (int)($input['number_of_seats'] ?? $input['seats'] ?? 0),
  'phone'     => $input['phone_number'] ?? ($input['phone'] ?? '')
];

if(!$ride['date'] || !$ride['depart'] || !$ride['mountain'] || !$ride['seats'] || !$ride['phone']){
  http_response_code(422);
  echo json_encode(['error'=>'Missing required fields']);
  exit;
}

$rides[] = $ride;
file_put_contents($file, json_encode($rides, JSON_PRETTY_PRINT), LOCK_EX);

echo json_encode(['success'=>true, 'rides'=>$rides]);
