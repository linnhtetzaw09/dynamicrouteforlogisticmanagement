<?php
session_start();
include "../config/db.php";

$job_id = $_GET["job_id"] ?? 0;

if ($job_id == 0) {
    die("Job ID is required");
}

$sql = "
SELECT 
    j.job_id,
    j.goods_name,
    j.assigned_vehicle_id,
    os.site_name AS origin_name,
    os.latitude AS origin_lat,
    os.longitude AS origin_lon,
    ds.site_name AS destination_name,
    ds.latitude AS dest_lat,
    ds.longitude AS dest_lon
FROM job j
JOIN site os ON j.origin_site_id = os.site_id
JOIN site ds ON j.destination_site_id = ds.site_id
WHERE j.job_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    die("Job not found");
}

if (!$job["origin_lat"] || !$job["origin_lon"] || !$job["dest_lat"] || !$job["dest_lon"]) {
    die("Origin or destination site has missing latitude/longitude");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Best Route Recommendation</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #map { height: 520px; width: 100%; border: 1px solid #ccc; }
        #result { margin-top: 15px; padding: 12px; background: #f1f1f1; }
        button { padding: 8px 12px; margin: 5px; }
        .best { color: blue; font-weight: bold; }
    </style>
</head>
<body>

<h2>LSTM Best Route Recommendation</h2>

<p>
    <b>Job:</b> <?php echo htmlspecialchars($job["goods_name"]); ?><br>
    <b>Origin:</b> <?php echo htmlspecialchars($job["origin_name"]); ?><br>
    <b>Destination:</b> <?php echo htmlspecialchars($job["destination_name"]); ?>
</p>

<button onclick="suggestRoute()">Suggest Best Route</button>
<a href="jobs.php"><button>Back to Jobs</button></a>

<div id="map"></div>
<div id="result">Waiting...</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
const jobId = <?php echo json_encode($job["job_id"]); ?>;
const vehicleId = <?php echo json_encode($job["assigned_vehicle_id"]); ?>;

const originLat = <?php echo json_encode(floatval($job["origin_lat"])); ?>;
const originLon = <?php echo json_encode(floatval($job["origin_lon"])); ?>;
const destLat = <?php echo json_encode(floatval($job["dest_lat"])); ?>;
const destLon = <?php echo json_encode(floatval($job["dest_lon"])); ?>;
const destinationName = <?php echo json_encode($job["destination_name"]); ?>;

let map = L.map("map").setView([originLat, originLon], 13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19
}).addTo(map);

L.marker([originLat, originLon]).addTo(map).bindPopup("Origin").openPopup();
L.marker([destLat, destLon]).addTo(map).bindPopup("Destination");

let routeLines = [];

function suggestRoute() {
    document.getElementById("result").innerHTML = "Loading routes and LSTM prediction...";

    Promise.all([
        getRoutesFromORS(),
        getPredictionFromLSTM()
    ])
    .then(([routeData, predictionData]) => {
        if (routeData.error) {
            document.getElementById("result").innerHTML = "ORS Error: " + routeData.error;
            return;
        }

        if (predictionData.error) {
            document.getElementById("result").innerHTML = "LSTM Error: " + predictionData.error;
            return;
        }

        let bestRoute = predictionData.best_route.route;

        drawRoutes(routeData.routes, predictionData, bestRoute);
        saveRouteToDatabase(routeData.routes, predictionData, bestRoute);
    })
    .catch(error => {
        document.getElementById("result").innerHTML =
            "Error. Make sure Apache and Flask LSTM API are running.";
    });
}

function getRoutesFromORS() {
    return fetch("../ai/api/ors_routes.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:
            "current_lat=" + encodeURIComponent(originLat) +
            "&current_lon=" + encodeURIComponent(originLon) +
            "&dest_lat=" + encodeURIComponent(destLat) +
            "&dest_lon=" + encodeURIComponent(destLon)
    })
    .then(response => response.json());
}

function getPredictionFromLSTM() {
    return fetch("http://127.0.0.1:5000/predict", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            destination: destinationName
        })
    })
    .then(response => response.json());
}

function drawRoutes(routes, predictionData, bestRoute) {
    routeLines.forEach(line => map.removeLayer(line));
    routeLines = [];

    let predictedTimes = {};

    predictionData.predictions.forEach(function(p) {
        predictedTimes[p.route] = p.predicted_time_min;
    });

    let html = "<h3>LSTM Prediction Result</h3>";

    routes.forEach(function(route) {
        let coords = route.geometry.coordinates.map(function(c) {
            return [c[1], c[0]];
        });

        let isBest = route.route === bestRoute;

        let line = L.polyline(coords, {
            color: isBest ? "blue" : "lightskyblue",
            weight: isBest ? 8 : 4,
            opacity: isBest ? 1 : 0.7
        }).addTo(map);

        routeLines.push(line);

        html += "<b>" + route.route + "</b>";

        if (isBest) {
            html += " <span class='best'>✅ Best Route</span>";
        }

        html += "<br>";
        html += "API Distance: " + route.distance_km + " km<br>";
        html += "API Time: " + route.api_time_min + " min<br>";
        html += "LSTM Predicted Time: " + predictedTimes[route.route] + " min<br><br>";
    });

    let group = L.featureGroup(routeLines);
    map.fitBounds(group.getBounds());

    document.getElementById("result").innerHTML = html;
}

function saveRouteToDatabase(routes, predictionData, bestRoute) {
    let predictedTimes = {};

    predictionData.predictions.forEach(function(p) {
        predictedTimes[p.route] = p.predicted_time_min;
    });

    fetch("save_route_recommendation.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            job_id: jobId,
            vehicle_id: vehicleId,
            origin_lat: originLat,
            origin_lon: originLon,
            dest_lat: destLat,
            dest_lon: destLon,
            best_route: bestRoute,
            routes: routes,
            predicted_times: predictedTimes
        })
    });
}
</script>

</body>
</html>