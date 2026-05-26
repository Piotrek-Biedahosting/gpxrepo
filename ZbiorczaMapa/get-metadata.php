<?php
header('Content-Type: application/json; charset=utf-8');

$metadataFile = './trasy-metadata.json';
$filename = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($filename)) {
    echo json_encode(['error' => 'No file specified']);
    exit;
}

// Bezpieczeństwo - nie pozwalaj na path traversal
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

if (!file_exists($metadataFile)) {
    // Brak pliku metadata - zwróć pusty
    echo json_encode([]);
    exit;
}

$metadata = json_decode(file_get_contents($metadataFile), true);

if (!is_array($metadata)) {
    echo json_encode([]);
    exit;
}

$metadata = $metadata[$filename] ?? [];

echo json_encode($metadata);
?>
