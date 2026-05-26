<?php
/**
 * Like Route V3 NO SESSION - PURE SERVER-SIDE
 * 
 * ZERO COOKIES - Brak banera cookie!
 * 
 * FEATURES:
 * 1. IP HASHNIĘTY SHA256
 * 2. Rate limit tylko server-side
 * 3. BRAK sesji w cookie
 * 4. BRAK privacy issues
 * 5. GDPR compliant - ZERO banera!
 */

header('Content-Type: application/json; charset=utf-8');

$filename = isset($_GET['file']) ? basename($_GET['file']) : '';
$action = isset($_GET['like']) ? 'like' : (isset($_GET['get']) ? 'get' : '');
$listAll = isset($_GET['list']);

// Bezpieczeństwo - blokuj path traversal
if (!empty($filename) && (strpos($filename, '..') !== false || strpos($filename, '/') !== false)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

$likesFile = '.likes.json';
$rateLimitFile = '.rate-limit.json';
$trasyFolder = __DIR__ . '/trasy';

// ===== HELPER: Hashuj IP bez exposure =====
function hashIP($ip) {
    // SHA256 hash IP'a - nie można go reverse'ować
    return hash('sha256', $ip . 'salt_bieda_secret_2024');
}

function getUserIdentifier() {
    // Kombinacja IP + User Agent dla lepszej identyfikacji
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $identifier = $ip . '::' . $userAgent;
    return hashIP($identifier);
}

// ===== LIST - Wszystkie likes =====
if ($listAll) {
    $likes = [];
    if (file_exists($likesFile)) {
        $content = file_get_contents($likesFile);
        $likes = json_decode($content, true) ?? [];
    }
    
    arsort($likes);
    
    echo json_encode([
        'total_files' => count($likes),
        'total_likes' => array_sum($likes),
        'likes' => $likes
    ]);
    exit;
}

// ===== GET - Pobierz liczbę likes =====
if ($action === 'get') {
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing filename']);
        exit;
    }
    
    $likes = [];
    if (file_exists($likesFile)) {
        $content = file_get_contents($likesFile);
        $likes = json_decode($content, true) ?? [];
    }
    
    $count = $likes[$filename] ?? 0;
    echo json_encode([
        'file' => $filename,
        'likes' => $count
    ]);
    exit;
}

// ===== LIKE - Dodaj like (PURE SERVER-SIDE, NO SESSION) =====
if ($action === 'like') {
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing filename']);
        exit;
    }
    
    // ===== SPRAWDZENIE CZY PLIK ISTNIEJE =====
    $gpxPath = $trasyFolder . '/' . $filename;
    if (!file_exists($gpxPath) || !preg_match('/\.gpx$/i', $filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'GPX file does not exist']);
        exit;
    }
    
    // ===== RATE LIMITING - HASHOWANE IP (TYLKO SERVER-SIDE) =====
    $userHash = getUserIdentifier();
    $rateKey = $filename . '::' . $userHash;
    
    $rateLimit = [];
    if (file_exists($rateLimitFile)) {
        $content = file_get_contents($rateLimitFile);
        $rateLimit = json_decode($content, true) ?? [];
    }
    
    // Sprawdzić ostatni like
    $lastLikeTime = $rateLimit[$rateKey] ?? 0;
    $currentTime = time();
    $timeSinceLast = $currentTime - $lastLikeTime;
    $cooldownSeconds = 120; // 2 minuty
    
    if ($timeSinceLast < $cooldownSeconds && $lastLikeTime !== 0) {
        $secondsLeft = $cooldownSeconds - $timeSinceLast;
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'error' => 'Rate limited',
            'message' => 'Wait ' . $secondsLeft . ' seconds before liking again',
            'seconds_left' => $secondsLeft
        ]);
        exit;
    }
    
    // ===== DODAJ LIKE =====
    $likes = [];
    if (file_exists($likesFile)) {
        $content = file_get_contents($likesFile);
        $likes = json_decode($content, true) ?? [];
    }
    
    // Zwiększ licznik
    $likes[$filename] = ($likes[$filename] ?? 0) + 1;
    
    // Zapisz likes
    $json = json_encode($likes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents($likesFile, $json);
    
    // ===== ZAKTUALIZUJ RATE LIMIT - Z HASHEM IP (BRAK SESJI!) =====
    $rateLimit[$rateKey] = $currentTime;
    $json = json_encode($rateLimit, JSON_PRETTY_PRINT);
    file_put_contents($rateLimitFile, $json);
    
    echo json_encode([
        'file' => $filename,
        'likes' => $likes[$filename],
        'status' => 'ok',
        'message' => 'Like added! Wait 2 minutes before liking again'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
