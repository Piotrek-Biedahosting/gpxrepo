<?php
/**
 * Get Route Points from GPX (WITH CACHE)
 * 
 * Zwraca wszystkie punkty trasy (lat, lon, ele)
 * Używane do rysowania na mapach
 * 
 * Cache: Przechowuje punkty w .points-cache/ dla szybkości
 */

header('Content-Type: application/json; charset=utf-8');

$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

// Bezpieczeństwo - blokuj path traversal
if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

$gpxFile = __DIR__ . '/trasy/' . $filename;
$cacheDir = __DIR__ . '/.points-cache';
$cacheFile = $cacheDir . '/' . md5($filename) . '.json';

// Utwórz folder cache jeśli nie istnieje
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// ===== SPRAWDZENIE CACHE =====
if (file_exists($cacheFile) && is_readable($cacheFile)) {
    $gpxMtime = @filemtime($gpxFile);
    $cacheMtime = @filemtime($cacheFile);
    
    // Cache jest aktualny (plik GPX się nie zmienił)
    if ($cacheMtime > 0 && $cacheMtime >= $gpxMtime) {
        header('Cache-Control: public, max-age=3600');
        header('X-Cache: HIT');
        $content = file_get_contents($cacheFile);
        if ($content !== false) {
            echo $content;
            exit;
        }
    }
}

// ===== CACHE MISS - PARSUJ GPX =====
if (!file_exists($gpxFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'GPX file not found']);
    exit;
}

// Parsuj GPX
$xml = simplexml_load_file($gpxFile);
if (!$xml) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid GPX file']);
    exit;
}

// Pobierz wszystkie punkty
$points = [];

// Obsługuj namespace
$namespaces = $xml->getNamespaces();
$ns = isset($namespaces['']) ? $namespaces[''] : null;

if ($ns) {
    $xml->registerXPathNamespace('gpx', $ns);
    $trkpts = $xml->xpath('//gpx:trkpt');
    if (empty($trkpts)) {
        $trkpts = $xml->xpath('//gpx:wpt');
    }
} else {
    $trkpts = $xml->xpath('//trkpt');
    if (empty($trkpts)) {
        $trkpts = $xml->xpath('//wpt');
    }
}

// Konwertuj do tablicy
foreach ($trkpts as $pt) {
    $points[] = [
        'lat' => (float)$pt['lat'],
        'lon' => (float)$pt['lon'],
        'ele' => isset($pt->ele) ? (float)$pt->ele : null
    ];
}

// ===== ZAPISZ DO CACHE =====
$json = json_encode($points);

// Upewnij się że folder istnieje z dobrymi uprawnieniami
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
    @chmod($cacheDir, 0777);
}

// Spróbuj zapisać cache
if (is_writable($cacheDir)) {
    @file_put_contents($cacheFile, $json);
    @chmod($cacheFile, 0666);
}

// Cache header
header('Cache-Control: public, max-age=3600');
header('X-Cache: MISS');

echo $json;
?>

