<?php
header('Content-Type: application/json');
$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$ride = json_decode($body, true);
if (!$ride) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

$file = __DIR__ . '/rides.json';
$rides = [];
if (file_exists($file)) {
  $json = file_get_contents($file);
  $rides = json_decode($json, true);
  if (!is_array($rides)) { $rides = []; }
}

// normalize & validate
function norm_phone($p){ return preg_replace('/\D+/', '', $p ?? ''); }
function norm_time($t){
  if (!$t) return '';
  if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t;
  if (preg_match('/^(\d{1,2}):(\d{2})\s*([AP]M)$/i', $t, $m)) {
    $h = ((int)$m[1]) % 12; if (stripos($m[3],'P')!==false) $h += 12; return str_pad((string)$h,2,'0',STR_PAD_LEFT).':'.$m[2];
  }
  return $t;
}

$ride['id'] = $ride['id'] ?? uniqid('ride_');
$ride['date'] = $ride['date'] ?? ($ride['date_of_trip'] ?? '');
$ride['depart'] = $ride['depart'] ?? ($ride['aprox_dep'] ?? '');
$ride['depart'] = norm_time($ride['depart']);
$ride['mountain'] = $ride['mountain'] ?? ($ride['mountain_destination'] ?? '');
$ride['amount'] = (float)($ride['amount'] ?? ($ride['payment']['amount'] ?? 0));
$ride['method'] = $ride['method'] ?? ($ride['payment']['method'] ?? 'Cash');
$ride['notes'] = $ride['notes'] ?? ($ride['payment']['notes'] ?? '');
$ride['seats'] = (int)($ride['seats'] ?? ($ride['number_of_seats'] ?? 0));
$ride['seatsLeft'] = isset($ride['seatsLeft']) ? (int)$ride['seatsLeft'] : (int)$ride['seats'];
$ride['phone'] = $ride['phone'] ?? ($ride['phone_number'] ?? '');

if (!$ride['date'] || !$ride['depart'] || !$ride['mountain'] || !$ride['seats'] || !$ride['phone']) {
  http_response_code(422); echo json_encode(['error'=>'Missing required fields']); exit;
}

$rides[] = [
  'id'=>$ride['id'], 'date'=>$ride['date'], 'depart'=>$ride['depart'], 'mountain'=>$ride['mountain'],
  'amount'=>$ride['amount'], 'method'=>$ride['method'], 'notes'=>$ride['notes'],
  'seats'=>$ride['seats'], 'seatsLeft'=>$ride['seatsLeft'], 'phone'=>$ride['phone']
];

file_put_contents($file, json_encode($rides, JSON_PRETTY_PRINT), LOCK_EX);
echo json_encode(['success'=>true, 'rides'=>$rides]);
?>
