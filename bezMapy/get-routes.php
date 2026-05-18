<?php
header('Content-Type: application/json; charset=utf-8');

$cacheFile = './routes-cache.json';
$cacheMaxAge = 3600; // 1 godzina

// NAJPIERW: spróbuj cache
if (file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    header('X-Cache: HIT (' . $cacheAge . 's old)');
    echo file_get_contents($cacheFile);
    exit;
}

// JEŚLI CACHE BRAKUJE: parsuj GPX-y "na żywo" (fallback)
header('X-Cache: MISS - Parsing live');

$trasyFolder = './trasy/';

if (!is_dir($trasyFolder)) {
    echo json_encode(['error' => 'Folder /trasy/ nie istnieje']);
    exit;
}

$files = glob($trasyFolder . '*.gpx');
$routes = array();

foreach ($files as $file) {
    $filename = basename($file);
    $xml = simplexml_load_file($file);
    
    if ($xml === false) continue;
    
    $name = (string)$xml->trk->name;
    if (empty($name)) {
        $name = str_replace('.gpx', '', $filename);
    }
    
    $distance = 0;
    $ascent = 0;
    $descent = 0;
    $pointCount = 0;
    $firstLat = null;
    $firstLon = null;
    $lastLat = null;
    $lastLon = null;
    $prevLat = null;
    $prevLon = null;
    $prevEle = null;
    
    foreach ($xml->trk as $track) {
        foreach ($track->trkseg as $segment) {
            foreach ($segment->trkpt as $point) {
                $lat = (float)$point['lat'];
                $lon = (float)$point['lon'];
                $ele = isset($point->ele) ? (float)$point->ele : 0;
                
                $pointCount++;
                
                if ($firstLat === null) {
                    $firstLat = $lat;
                    $firstLon = $lon;
                }
                $lastLat = $lat;
                $lastLon = $lon;
                
                if ($prevEle !== null) {
                    $eleDiff = $ele - $prevEle;
                    if ($eleDiff > 0) {
                        $ascent += $eleDiff;
                    } else {
                        $descent += abs($eleDiff);
                    }
                }
                
                if ($prevLat !== null && $prevLon !== null) {
                    $distance += haversineDistance($prevLat, $prevLon, $lat, $lon);
                }
                
                $prevLat = $lat;
                $prevLon = $lon;
                $prevEle = $ele;
            }
        }
    }
    
    $routes[] = array(
        'name' => $name,
        'filename' => $filename,
        'distance' => round($distance, 2),
        'elevation' => round($ascent),
        'ascent' => round($ascent),
        'descent' => round($descent),
        'pointCount' => $pointCount,
        'startLat' => $firstLat,
        'startLon' => $firstLon,
        'endLat' => $lastLat,
        'endLon' => $lastLon,
        'fileSize' => filesize($file),
        'lastModified' => filemtime($file)
    );
}

usort($routes, function($a, $b) {
    return $b['lastModified'] - $a['lastModified'];
});

echo json_encode($routes, JSON_UNESCAPED_UNICODE);

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}
?>
