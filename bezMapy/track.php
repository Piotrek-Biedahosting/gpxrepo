<?php
/**
 * GPX Download Tracker
 * 
 * Interceptuje pobierania GPX-ów
 * Licznik jest bezpieczny - hacker NIC nie widzi!
 */

// Pobierz nazwę pliku z URL
$requestUri = $_SERVER['REQUEST_URI'];
$fileName = basename(parse_url($requestUri, PHP_URL_PATH));

// Bezpieczeństwo - sprawdź czy to GPX
if (!preg_match('/\.gpx$/i', $fileName)) {
    http_response_code(404);
    exit('Not found');
}

// Bezpieczeństwo - blokuj path traversal
if (strpos($fileName, '..') !== false) {
    http_response_code(403);
    exit('Forbidden');
}

$gpxFile = __DIR__ . '/trasy/' . $fileName;
$downloadsFile = __DIR__ . '/.downloads.json';

// Sprawdź czy plik GPX istnieje
if (!file_exists($gpxFile)) {
    http_response_code(404);
    exit('GPX file not found');
}

// ===== ZWIĘKSZ LICZNIK =====
$downloads = [];
if (file_exists($downloadsFile)) {
    $content = file_get_contents($downloadsFile);
    $downloads = json_decode($content, true) ?? [];
}

// Zwiększ licznik dla tego pliku
$downloads[$fileName] = ($downloads[$fileName] ?? 0) + 1;

// Zapisz z ładnym formatem
$json = json_encode($downloads, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($downloadsFile, $json);

// ===== WYSYŁAJ PLIK =====
// Ustaw headers dla pobrania
header('Content-Type: application/gpx+xml');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($gpxFile));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Wyślij plik
readfile($gpxFile);
exit;
?>
