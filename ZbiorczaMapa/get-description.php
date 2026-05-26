<?php
header('Content-Type: application/json; charset=utf-8');

$filename = isset($_GET['file']) ? basename($_GET['file']) : null;
$check = isset($_GET['check']);

if (!$filename) {
    echo json_encode(['error' => 'Brak pliku']);
    exit;
}

// Bezpieczeństwo - tylko .txt z folderu /trasy/
if (!preg_match('/^[a-zA-Z0-9_-]+\.txt$/', $filename)) {
    echo json_encode(['error' => 'Nieprawidłowa nazwa pliku']);
    exit;
}

$filepath = './trasy/' . $filename;

// Jeśli to tylko sprawdzenie istnienia
if ($check) {
    echo json_encode(['exists' => file_exists($filepath)]);
    exit;
}

// Pobierz zawartość
if (!file_exists($filepath)) {
    echo json_encode(['error' => 'Plik nie znaleziony']);
    exit;
}

$content = file_get_contents($filepath);

echo json_encode([
    'success' => true,
    'content' => $content
], JSON_UNESCAPED_UNICODE);
?>
