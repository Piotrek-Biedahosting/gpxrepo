<?php
header('Content-Type: application/json; charset=utf-8');

$filename = isset($_GET['file']) ? basename($_GET['file']) : null;

if (!$filename || !preg_match('/\.gpx$/', $filename)) {
    echo json_encode(['error' => 'Brak pliku']);
    exit;
}

$file = './trasy/' . $filename;

if (!file_exists($file)) {
    echo json_encode(['error' => 'Plik nie znaleziony']);
    exit;
}

// Parsuj XML
$xml = simplexml_load_file($file);

if ($xml === false) {
    echo json_encode(['error' => 'Błąd parsowania GPX']);
    exit;
}

$points = array();

// Pobierz punkty z GPX
foreach ($xml->trk as $track) {
    foreach ($track->trkseg as $segment) {
        foreach ($segment->trkpt as $point) {
            $lat = (float)$point['lat'];
            $lon = (float)$point['lon'];
            $ele = isset($point->ele) ? (float)$point->ele : 0;
            
            $points[] = array(
                'lat' => $lat,
                'lon' => $lon,
                'ele' => $ele
            );
        }
    }
}

echo json_encode([
    'success' => true,
    'points' => $points,
    'count' => count($points)
], JSON_UNESCAPED_UNICODE);
?>
