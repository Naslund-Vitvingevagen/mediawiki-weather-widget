<<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$apiKey    = "";
$stationId = "";
$units     = "m";

$url = "https://api.weather.com/v2/pws/observations/current"
     . "?stationId={$stationId}&format=json&units={$units}&apiKey={$apiKey}";

$ctx = stream_context_create([
    'http' => ['timeout' => 10, 'ignore_errors' => true]
]);

$data = file_get_contents($url, false, $ctx);

if ($data === false) {
    http_response_code(502);
    echo json_encode(["error" => "file_get_contents misslyckades", "allow_url_fopen" => ini_get('allow_url_fopen')]);
} else {
    // Visa även HTTP-statuskoden från svaret
    $statusLine = $http_response_header[0] ?? '';
    echo $data;
}
?>