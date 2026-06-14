<?php
/**
 * Wunderground PWS API Proxy
 *
 * Fetches current weather observations from a personal weather station
 * via the Weather Underground (Wunderground) REST API, and returns the
 * data as JSON with CORS headers so it can be consumed by browser-side
 * JavaScript (e.g. an iframe widget embedded in MediaWiki).
 *
 * Place this file in the web root of your Synology NAS, e.g.:
 *   /volume1/web/wunderground-proxy.php
 *
 * Requirements (enable in DSM → Web Station → PHP settings):
 *   - openssl  (required for HTTPS / file_get_contents)
 *   - curl     (optional, not used in this version)
 *
 * Configuration:
 *   Copy config.php.example to config.php and fill in your values.
 */

require_once __DIR__ . '/config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$cacheFile = __DIR__ . "/wx-cache.json";
$cacheTime = 300; // Cache time in seconds (5 minutes)

// Serve cached data if fresh enough
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

$url = "https://api.weather.com/v2/pws/observations/current"
     . "?stationId=" . urlencode(WX_STATION_ID)
     . "&format=json&units=" . WX_UNITS
     . "&apiKey=" . urlencode(WX_API_KEY);

$ctx = stream_context_create([
    'http' => ['timeout' => 15, 'ignore_errors' => true]
]);

$data       = file_get_contents($url, false, $ctx);
$statusLine = $http_response_header[0] ?? '';

if ($data !== false && strpos($statusLine, '200') !== false) {
    // Fresh data received — update cache
    file_put_contents($cacheFile, $data);
    echo $data;
} elseif (file_exists($cacheFile)) {
    // API call failed — fall back to last known good cache
    echo file_get_contents($cacheFile);
} else {
    // No cache and no data
    http_response_code(502);
    echo json_encode([
        "error"  => "Could not fetch weather data",
        "status" => $statusLine
    ]);
}
?>
