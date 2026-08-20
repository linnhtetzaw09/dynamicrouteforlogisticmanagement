<?php
header("Content-Type: application/json");
include "config.php";

$currentLat = $_POST["current_lat"] ?? "";
$currentLon = $_POST["current_lon"] ?? "";
$destLat = $_POST["dest_lat"] ?? "";
$destLon = $_POST["dest_lon"] ?? "";

if ($currentLat == "" || $currentLon == "" || $destLat == "" || $destLon == "") {
    echo json_encode(["error" => "Missing coordinates"]);
    exit;
}

$url = "https://api.openrouteservice.org/v2/directions/driving-car/geojson";

$requestData = [
    "coordinates" => [
        [floatval($currentLon), floatval($currentLat)],
        [floatval($destLon), floatval($destLat)]
    ],
    "alternative_routes" => [
        "target_count" => 3,
        "weight_factor" => 1.6,
        "share_factor" => 0.6
    ]
];

$headers = [
    "Authorization: " . $ORS_API_KEY,
    "Content-Type: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (!isset($data["features"])) {
    echo json_encode(["error" => "No routes found", "details" => $data]);
    exit;
}

$routeNames = ["Route1", "Route2", "Route3"];
$routes = [];

foreach ($data["features"] as $i => $feature) {
    if ($i >= 3) break;

    $summary = $feature["properties"]["summary"];

    $routes[] = [
        "route" => $routeNames[$i],
        "distance_km" => round($summary["distance"] / 1000, 2),
        "api_time_min" => round($summary["duration"] / 60, 2),
        "geometry" => $feature["geometry"]
    ];
}

echo json_encode(["routes" => $routes]);
?>