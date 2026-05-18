<?php
/**
 * GPX Repository Smart Cache Generator + File Sanitizer
 * 
 * FEATURES:
 * 1. Liczy hash MD5 wszystkich GPX-ów
 * 2. Porównuje z poprzednim hashem
 * 3. Jeśli się różni -> regeneruje cache
 * 4. BONUS: Sanitizuje nazwy plików GPX
 *    - Usuwa polskie znaki (ąćęłńóśźż)
 *    - Usuwa spacje
 *    - Usuwa znaki specjalne
 *    - Zmienia extensję .gpx na lowercase
 * 5. Automatycznie przenosi pliki jeśli nazwa się zmieniła
 * 
 * Umieść POZA public_html (w katalogu rodzica)
 */

// ===== KONFIGURACJA =====
define('PUBLIC_HTML_PATH', __DIR__ . '/public_html');
define('CACHE_FILE', PUBLIC_HTML_PATH . '/routes-cache.json');
define('TRASY_FOLDER', PUBLIC_HTML_PATH . '/trasy');
define('FILES_HASH_FILE', __DIR__ . '/.gpx-files-hash');

// ===== SECURITY - Tylko localhost/cron =====
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isCli = php_sapi_name() === 'cli';

if (!$isLocalhost && !$isCli) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ===== SANITIZE FILENAME =====
function sanitizeFilename($filename) {
    // Zamień polskie znaki na ascii
    $replacements = array(
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
        'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z'
    );
    
    $filename = str_replace(array_keys($replacements), array_values($replacements), $filename);
    
    // Usuń spacje i znaki specjalne - zostaw tylko a-z, 0-9, -, _
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Usuń wielkie myślniki
    $filename = preg_replace('/-+/', '-', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    
    // Zmień .GPX na .gpx
    $filename = preg_replace_callback('/\.gpx$/i', function($m) {
        return '.gpx';
    }, $filename);
    
    // Usuń spacje na końcu/początku
    $filename = trim($filename);
    
    return $filename;
}

// ===== SPRAWDZENIE CZY PLIKI SIĘ ZMIENIŁY =====

if (!is_dir(TRASY_FOLDER)) {
    die(json_encode(array('error' => 'Folder /trasy/ nie istnieje')));
}

// Skan folderu
$files = scandir(TRASY_FOLDER);
$gpxFiles = array();
$renamedFiles = array();

foreach ($files as $file) {
    if (preg_match('/\.gpx$/i', $file)) {
        $cleanName = sanitizeFilename($file);
        $gpxFiles[$cleanName] = true;
        
        // Jeśli nazwa się zmieniła -> przemiń plik
        if ($cleanName !== $file) {
            $oldPath = TRASY_FOLDER . '/' . $file;
            $newPath = TRASY_FOLDER . '/' . $cleanName;
            
            if (file_exists($oldPath) && !file_exists($newPath)) {
                if (rename($oldPath, $newPath)) {
                    $renamedFiles[] = array(
                        'old' => $file,
                        'new' => $cleanName,
                        'status' => 'Renamed'
                    );
                    echo "OK: Renamed $file -> $cleanName\n";
                } else {
                    echo "ERROR: Could not rename $file\n";
                }
            } elseif (file_exists($newPath)) {
                echo "WARN: File exists $cleanName (removed old)\n";
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
        }
    }
}

// Hash wszystkich plików
$filenames = array_keys($gpxFiles);
sort($filenames);

$hashData = array();
foreach ($filenames as $fname) {
    $fpath = TRASY_FOLDER . '/' . $fname;
    $mtime = file_exists($fpath) ? filemtime($fpath) : 0;
    $hashData[] = $fname . '::' . $mtime;
}

$newHash = md5(implode('|', $hashData));
$oldHash = file_exists(FILES_HASH_FILE) ? trim(file_get_contents(FILES_HASH_FILE)) : '';

// ===== COMPARE & DECIDE =====
if ($newHash === $oldHash && file_exists(CACHE_FILE) && empty($renamedFiles)) {
    echo json_encode(array(
        'status' => 'NO_CHANGES',
        'message' => 'Cache is up to date',
        'files_count' => count($gpxFiles),
        'renamed' => $renamedFiles
    ));
    exit;
}

echo "CHANGES DETECTED - Regenerating cache...\n";

// ===== PARSE GPX FILES =====
$routes = array();
foreach ($filenames as $gpxFile) {
    $gpxPath = TRASY_FOLDER . '/' . $gpxFile;
    
    if (!file_exists($gpxPath)) {
        continue;
    }
    
    $xml = simplexml_load_file($gpxPath);
    if (!$xml) {
        echo "ERROR parsing: $gpxFile\n";
        continue;
    }
    
    // Jeśli GPX ma namespace, zarejestruj go
    $namespaces = $xml->getNamespaces();
    $ns = isset($namespaces['']) ? $namespaces[''] : null;
    
    if ($ns) {
        // Zarejestruj namespace dla xpath
        $xml->registerXPathNamespace('gpx', $ns);
        $trkpts = $xml->xpath('//gpx:trkpt');
        if (empty($trkpts)) {
            $trkpts = $xml->xpath('//gpx:wpt');
        }
    } else {
        // Bez namespace
        $trkpts = $xml->xpath('//trkpt');
        if (empty($trkpts)) {
            $trkpts = $xml->xpath('//wpt');
        }
    }
    
    // Jeśli brak punktów - pomiń
    if (empty($trkpts)) {
        echo "WARN: No points in $gpxFile\n";
        continue;
    }
    
    // Nazwa trasy z <name> lub z nazwy pliku
    $routeName = (string)$xml->trk->name;
    if (empty($routeName)) {
        $routeName = str_replace('.gpx', '', $gpxFile);
        $routeName = str_replace('-', ' ', $routeName);
        $routeName = str_replace('_', ' ', $routeName);
        $routeName = ucwords($routeName);
    }
    
    // Dystans
    $distance = 0;
    $lastLat = null;
    $lastLon = null;
    
    foreach ($trkpts as $pt) {
        $lat = (float)$pt['lat'];
        $lon = (float)$pt['lon'];
        
        if ($lastLat !== null) {
            $distance += haversine($lastLat, $lastLon, $lat, $lon);
        }
        
        $lastLat = $lat;
        $lastLon = $lon;
    }
    
    // Ascent/Descent
    $ascent = 0;
    $descent = 0;
    $lastEle = null;
    
    foreach ($trkpts as $pt) {
        $ele = (float)$pt->ele;
        
        if ($lastEle !== null) {
            $diff = $ele - $lastEle;
            if ($diff > 0) $ascent += $diff;
            if ($diff < 0) $descent += abs($diff);
        }
        
        $lastEle = $ele;
    }
    
    $routes[] = array(
        'filename' => $gpxFile,
        'name' => $routeName,
        'distance' => $distance,
        'ascent' => (int)$ascent,
        'descent' => (int)$descent,
        'pointCount' => count($trkpts)
    );
    
    echo "OK: Processed $gpxFile\n";
}

// ===== SAVE CACHE =====
$json = json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents(CACHE_FILE, $json);
echo "OK: Cache saved - " . count($routes) . " routes\n";

// ===== SAVE NEW HASH =====
file_put_contents(FILES_HASH_FILE, $newHash);
echo "OK: Hash updated\n";

// ===== RESPONSE =====
echo json_encode(array(
    'status' => 'OK',
    'message' => 'Regeneration complete',
    'routes_count' => count($routes),
    'renamed_files' => $renamedFiles,
    'new_hash' => $newHash
));

// ===== HELPER: Haversine =====
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}
?>
