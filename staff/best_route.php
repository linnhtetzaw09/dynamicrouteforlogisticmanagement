<?php
session_start();

require_once "../config/db_config.php";

$job_id = $_GET["job_id"] ?? 0;

if ($job_id == 0) {
    die("Job ID is required");
}


/*
|--------------------------------------------------------------------------
| Get Job Information
|--------------------------------------------------------------------------
| A job can have either:
|
| 1. destination_site_id
| 2. destination_place_id
|
| The destination coordinates are selected automatically using COALESCE().
|--------------------------------------------------------------------------
*/

$sql = "
SELECT 
    j.job_id,
    j.goods_name,
    j.assigned_vehicle_id,

    -- Origin Site
    os.site_name AS origin_name,
    os.latitude AS origin_lat,
    os.longitude AS origin_lon,

    -- Destination Name
    COALESCE(
        ds.site_name,
        p.place_name
    ) AS destination_name,

    -- Destination Coordinates
    COALESCE(
        ds.latitude,
        p.latitude
    ) AS dest_lat,

    COALESCE(
        ds.longitude,
        p.longitude
    ) AS dest_lon,

    -- Destination Type
    CASE
        WHEN j.destination_site_id IS NOT NULL
            THEN 'Company Site'
        WHEN j.destination_place_id IS NOT NULL
            THEN p.place_type
        ELSE 'Unknown'
    END AS destination_type,

    -- Destination City
    p.city AS destination_city

FROM job j

-- Origin
JOIN site os
    ON j.origin_site_id = os.site_id

-- Company Site destination
LEFT JOIN site ds
    ON j.destination_site_id = ds.site_id

-- Myanmar Place destination
LEFT JOIN place p
    ON j.destination_place_id = p.place_id

WHERE j.job_id = ?
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query error: " . $conn->error);
}

$stmt->bind_param("i", $job_id);

$stmt->execute();

$result = $stmt->get_result();

$job = $result->fetch_assoc();


if (!$job) {
    die("Job not found");
}


/*
|--------------------------------------------------------------------------
| Check Coordinates
|--------------------------------------------------------------------------
*/

if (
    $job["origin_lat"] === null ||
    $job["origin_lon"] === null ||
    $job["dest_lat"] === null ||
    $job["dest_lon"] === null
) {
    die("Origin or destination has missing latitude/longitude");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Best Route Recommendation
    </title>


    <!-- Leaflet CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet/dist/leaflet.css"
    >


    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        #map {
            height: 520px;
            width: 100%;
            border: 1px solid #ccc;
            margin-top: 20px;
        }

        #result {
            margin-top: 15px;
            padding: 12px;
            background: #f1f1f1;
        }

        button {
            padding: 8px 12px;
            margin: 5px;
            cursor: pointer;
        }

        .best {
            color: blue;
            font-weight: bold;
        }

        .destination-info {
            margin-top: 5px;
            color: #555;
        }

    </style>

</head>


<body>


<h2>
    LSTM Best Route Recommendation
</h2>


<!-- ========================================================= -->
<!-- JOB INFORMATION -->
<!-- ========================================================= -->

<p>

    <b>Job:</b>

    <?php echo htmlspecialchars(
        $job["goods_name"]
    ); ?>

    <br>


    <b>Origin:</b>

    <?php echo htmlspecialchars(
        $job["origin_name"]
    ); ?>

    <br>


    <b>Destination:</b>

    <?php echo htmlspecialchars(
        $job["destination_name"]
    ); ?>


    <br>


    <b>Destination Type:</b>

    <?php echo htmlspecialchars(
        $job["destination_type"]
    ); ?>


    <?php

    if (
        !empty(
            $job["destination_city"]
        )
    ):

    ?>

        <br>

        <b>City:</b>

        <?php echo htmlspecialchars(
            $job["destination_city"]
        ); ?>

    <?php endif; ?>

</p>


<!-- ========================================================= -->
<!-- BUTTONS -->
<!-- ========================================================= -->

<button
    onclick="suggestRoute()"
>

    Suggest Best Route

</button>


<a href="jobs.php">

    <button>
        Back to Jobs
    </button>

</a>


<!-- ========================================================= -->
<!-- MAP -->
<!-- ========================================================= -->

<div id="map"></div>


<!-- ========================================================= -->
<!-- RESULT -->
<!-- ========================================================= -->

<div id="result">

    Waiting...

</div>


<!-- Leaflet JS -->

<script
    src="https://unpkg.com/leaflet/dist/leaflet.js">
</script>


<script>

/*
|--------------------------------------------------------------------------
| Job Information
|--------------------------------------------------------------------------
*/

const jobId =
    <?php echo json_encode(
        $job["job_id"]
    ); ?>;


const vehicleId =
    <?php echo json_encode(
        $job["assigned_vehicle_id"]
    ); ?>;


/*
|--------------------------------------------------------------------------
| Origin Coordinates
|--------------------------------------------------------------------------
*/

const originLat =
    <?php echo json_encode(
        floatval($job["origin_lat"])
    ); ?>;


const originLon =
    <?php echo json_encode(
        floatval($job["origin_lon"])
    ); ?>;


/*
|--------------------------------------------------------------------------
| Destination Coordinates
|--------------------------------------------------------------------------
*/

const destLat =
    <?php echo json_encode(
        floatval($job["dest_lat"])
    ); ?>;


const destLon =
    <?php echo json_encode(
        floatval($job["dest_lon"])
    ); ?>;


/*
|--------------------------------------------------------------------------
| Destination Information
|--------------------------------------------------------------------------
*/

const destinationName =
    <?php echo json_encode(
        $job["destination_name"]
    ); ?>;


const destinationType =
    <?php echo json_encode(
        $job["destination_type"]
    ); ?>;


/*
|--------------------------------------------------------------------------
| Create Map
|--------------------------------------------------------------------------
*/

let map = L.map(
    "map"
).setView(
    [originLat, originLon],
    13
);


/*
|--------------------------------------------------------------------------
| OpenStreetMap Layer
|--------------------------------------------------------------------------
*/

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(map);


/*
|--------------------------------------------------------------------------
| Origin Marker
|--------------------------------------------------------------------------
*/

L.marker(
    [originLat, originLon]
)
.addTo(map)
.bindPopup(
    "<b>Origin</b><br>" +
    escapeHtml(
        <?php echo json_encode(
            $job["origin_name"]
        ); ?>
    )
)
.openPopup();


/*
|--------------------------------------------------------------------------
| Destination Marker
|--------------------------------------------------------------------------
*/

L.marker(
    [destLat, destLon]
)
.addTo(map)
.bindPopup(
    "<b>Destination</b><br>" +
    escapeHtml(destinationName) +
    "<br><small>" +
    escapeHtml(destinationType) +
    "</small>"
);


/*
|--------------------------------------------------------------------------
| Route Lines
|--------------------------------------------------------------------------
*/

let routeLines = [];


/*
|--------------------------------------------------------------------------
| Suggest Best Route
|--------------------------------------------------------------------------
*/

function suggestRoute() {

    document.getElementById(
        "result"
    ).innerHTML =
        "Loading routes and LSTM prediction...";


    /*
    |--------------------------------------------------------------------------
    | Call ORS and LSTM at the same time
    |--------------------------------------------------------------------------
    */

    Promise.all([
        getRoutesFromORS(),
        getPredictionFromLSTM()
    ])

    .then(
        ([routeData, predictionData]) => {

            /*
            |--------------------------------------------------------------------------
            | ORS Error
            |--------------------------------------------------------------------------
            */

            if (routeData.error) {

                document.getElementById(
                    "result"
                ).innerHTML =
                    "ORS Error: " +
                    escapeHtml(
                        routeData.error
                    );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | LSTM Error
            |--------------------------------------------------------------------------
            */

            if (predictionData.error) {

                document.getElementById(
                    "result"
                ).innerHTML =
                    "LSTM Error: " +
                    escapeHtml(
                        predictionData.error
                    );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Check LSTM Best Route
            |--------------------------------------------------------------------------
            */

            if (
                !predictionData.best_route ||
                !predictionData.best_route.route
            ) {

                document.getElementById(
                    "result"
                ).innerHTML =
                    "LSTM did not return a best route.";

                return;
            }


            let bestRoute =
                predictionData.best_route.route;


            /*
            |--------------------------------------------------------------------------
            | Draw Routes
            |--------------------------------------------------------------------------
            */

            drawRoutes(
                routeData.routes,
                predictionData,
                bestRoute
            );


            /*
            |--------------------------------------------------------------------------
            | Save Recommendation
            |--------------------------------------------------------------------------
            */

            saveRouteToDatabase(
                routeData.routes,
                predictionData,
                bestRoute
            );

        }
    )


    .catch(
        error => {

            console.error(error);

            document.getElementById(
                "result"
            ).innerHTML =
                "Error. Make sure Apache and Flask LSTM API are running.";

        }
    );

}


/*
|--------------------------------------------------------------------------
| Get Routes From ORS
|--------------------------------------------------------------------------
*/

function getRoutesFromORS() {

    return fetch(
        "../ai/api/ors_routes.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body:
                "current_lat=" +
                encodeURIComponent(originLat) +

                "&current_lon=" +
                encodeURIComponent(originLon) +

                "&dest_lat=" +
                encodeURIComponent(destLat) +

                "&dest_lon=" +
                encodeURIComponent(destLon)
        }
    )

    .then(
        response => {

            if (!response.ok) {

                throw new Error(
                    "ORS API request failed."
                );

            }

            return response.json();

        }
    );

}


/*
|--------------------------------------------------------------------------
| Get Prediction From LSTM
|--------------------------------------------------------------------------
*/

function getPredictionFromLSTM() {

    return fetch(
        "http://127.0.0.1:5000/predict",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({

                destination:
                    destinationName

            })
        }
    )

    .then(
        response => {

            if (!response.ok) {

                throw new Error(
                    "LSTM API request failed."
                );

            }

            return response.json();

        }
    );

}


/*
|--------------------------------------------------------------------------
| Draw Routes
|--------------------------------------------------------------------------
*/

function drawRoutes(
    routes,
    predictionData,
    bestRoute
) {

    /*
    |--------------------------------------------------------------------------
    | Remove Previous Routes
    |--------------------------------------------------------------------------
    */

    routeLines.forEach(
        line => map.removeLayer(line)
    );

    routeLines = [];


    /*
    |--------------------------------------------------------------------------
    | Predicted Times
    |--------------------------------------------------------------------------
    */

    let predictedTimes = {};


    if (
        predictionData.predictions
    ) {

        predictionData.predictions.forEach(
            function (p) {

                predictedTimes[
                    p.route
                ] =
                    p.predicted_time_min;

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Result HTML
    |--------------------------------------------------------------------------
    */

    let html =
        "<h3>LSTM Prediction Result</h3>";


    /*
    |--------------------------------------------------------------------------
    | Display Each Route
    |--------------------------------------------------------------------------
    */

    routes.forEach(
        function (route) {

            /*
            |--------------------------------------------------------------------------
            | ORS geometry
            |--------------------------------------------------------------------------
            */

            let coords =
                route.geometry.coordinates.map(
                    function (c) {

                        return [
                            c[1],
                            c[0]
                        ];

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Check Best Route
            |--------------------------------------------------------------------------
            */

            let isBest =
                route.route === bestRoute;


            /*
            |--------------------------------------------------------------------------
            | Route Line
            |--------------------------------------------------------------------------
            */

            let line =
                L.polyline(
                    coords,
                    {

                        color:
                            isBest
                                ? "blue"
                                : "lightskyblue",

                        weight:
                            isBest
                                ? 8
                                : 4,

                        opacity:
                            isBest
                                ? 1
                                : 0.7

                    }
                ).addTo(map);


            routeLines.push(line);


            /*
            |--------------------------------------------------------------------------
            | Route Information
            |--------------------------------------------------------------------------
            */

            html +=
                "<b>" +
                escapeHtml(
                    route.route
                ) +
                "</b>";


            if (isBest) {

                html +=
                    " <span class='best'>" +
                    "✅ Best Route" +
                    "</span>";

            }


            html += "<br>";


            /*
            |--------------------------------------------------------------------------
            | API Distance
            |--------------------------------------------------------------------------
            */

            html +=
                "API Distance: " +
                escapeHtml(
                    route.distance_km
                ) +
                " km<br>";


            /*
            |--------------------------------------------------------------------------
            | API Time
            |--------------------------------------------------------------------------
            */

            html +=
                "API Time: " +
                escapeHtml(
                    route.api_time_min
                ) +
                " min<br>";


            /*
            |--------------------------------------------------------------------------
            | LSTM Prediction
            |--------------------------------------------------------------------------
            */

            if (
                predictedTimes[
                    route.route
                ] !== undefined
            ) {

                html +=
                    "LSTM Predicted Time: " +
                    escapeHtml(
                        predictedTimes[
                            route.route
                        ]
                    ) +
                    " min<br>";

            } else {

                html +=
                    "LSTM Predicted Time: N/A<br>";

            }


            html += "<br>";

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Fit Map To Routes
    |--------------------------------------------------------------------------
    */

    if (routeLines.length > 0) {

        let group =
            L.featureGroup(
                routeLines
            );

        map.fitBounds(
            group.getBounds()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Display Result
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        "result"
    ).innerHTML = html;

}


/*
|--------------------------------------------------------------------------
| Save Route Recommendation
|--------------------------------------------------------------------------
*/

function saveRouteToDatabase(
    routes,
    predictionData,
    bestRoute
) {

    /*
    |--------------------------------------------------------------------------
    | Predicted Times
    |--------------------------------------------------------------------------
    */

    let predictedTimes = {};


    if (
        predictionData.predictions
    ) {

        predictionData.predictions.forEach(
            function (p) {

                predictedTimes[
                    p.route
                ] =
                    p.predicted_time_min;

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Save Data
    |--------------------------------------------------------------------------
    */

    fetch(
        "save_route_recommendation.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({

                job_id:
                    jobId,

                vehicle_id:
                    vehicleId,

                origin_lat:
                    originLat,

                origin_lon:
                    originLon,

                dest_lat:
                    destLat,

                dest_lon:
                    destLon,

                best_route:
                    bestRoute,

                routes:
                    routes,

                predicted_times:
                    predictedTimes

            })
        }
    )

    .then(
        response => {

            if (!response.ok) {

                console.error(
                    "Failed to save route recommendation."
                );

            }

        }
    )

    .catch(
        error => {

            console.error(
                "Save route error:",
                error
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| HTML Escape Helper
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    if (value === null ||
        value === undefined) {

        return "";

    }


    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}

</script>


</body>
</html>