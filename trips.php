<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Trip Logging System with Transport Cost Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        iframe {
            border-radius: 10px;
            width: 100%;
            height: 450px;
            border: 2px solid #000;
            margin-top: 20px;
        }
        .cost-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
        }
    </style>
</head>
<body class="p-4">

<?php
$driversStmt = $pdo->query("SELECT * FROM drivers");
$drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);

$vehiclesStmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'Available'");
$vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Trips Logging</h2>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_id  = $_POST['vehicle_id'] ?? null;
    $driver_id   = $_POST['driver_id'] ?? null;
    $origin      = $_POST['origin'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $estimated_cost = $_POST['estimated_cost'] ?? 0;

    if (!$vehicle_id || !$driver_id) {
        echo "<div class='alert alert-danger'>Please select both a driver and a vehicle.</div>";
    } else {
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM trips")->fetchAll(PDO::FETCH_COLUMN);
            $hasCost = in_array('estimated_cost', $columns);

            if ($hasCost && in_array('origin', $columns) && in_array('destination', $columns)) {
                $stmt = $pdo->prepare("
                    INSERT INTO trips (vehicle_id, driver_id, trip_start, trip_end, origin, destination, estimated_cost)
                    VALUES (?, ?, NOW(), NOW(), ?, ?, ?)
                ");
                $stmt->execute([$vehicle_id, $driver_id, $origin, $destination, $estimated_cost]);
            } elseif (in_array('origin', $columns) && in_array('destination', $columns)) {
                $stmt = $pdo->prepare("
                    INSERT INTO trips (vehicle_id, driver_id, trip_start, trip_end, origin, destination)
                    VALUES (?, ?, NOW(), NOW(), ?, ?)
                ");
                $stmt->execute([$vehicle_id, $driver_id, $origin, $destination]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO trips (vehicle_id, driver_id, trip_start, trip_end)
                    VALUES (?, ?, NOW(), NOW())
                ");
                $stmt->execute([$vehicle_id, $driver_id]);
            }

            $updateStatus = $pdo->prepare("UPDATE vehicles SET status = 'Currently Out/Going' WHERE id = ?");
            $updateStatus->execute([$vehicle_id]);

            echo "<div class='alert alert-success mt-3'>✅ Dispatch logged successfully! Vehicle marked as 'Currently Out/Going'.</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<form method="post" class="mt-3" id="tripForm">
    <div class="row mb-3">
        <div class="col">
            <label>Driver</label>
            <select name="driver_id" class="form-control" required>
                <option value="">-- Select Driver --</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col">
            <label>Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
                <option value="">-- Select Vehicle --</option>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?= htmlspecialchars($v['id']) ?>">
                        <?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['plate_number'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label>Start of Route (Origin)</label>
        <input type="text" id="origin" name="origin" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>End of Route (Destination)</label>
        <input type="text" id="destination" name="destination" class="form-control" required>
    </div>

    <div class="mb-3">
        <button type="button" class="btn btn-success" onclick="updateMapAndETA()">Show Route & Cost</button>
    </div>

    <iframe id="mapFrame"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.591712181253!2d121.0194!3d14.7539!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1f0d5b1adcd%3A0xbfbcb582e3f5a89!2sQuezon%20City!5e0!3m2!1sen!2sph!4v1759784819391!5m2!1sen!2sph"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>

    <div class="mt-3 cost-box">
        <p><strong>Estimated Travel Time:</strong> <span id="duration">N/A</span></p>
        <p><strong>Estimated Distance:</strong> <span id="distance">N/A</span></p>
        <p><strong>Fuel Cost per Liter:</strong> ₱<span id="fuelCost">0.60</span></p>
        <p><strong>Vehicle Efficiency:</strong> <span id="efficiency">10</span> km/l</p>
        <p><strong>Estimated Transport Cost:</strong> ₱<span id="totalCost">0.00</span></p>
        <input type="hidden" name="estimated_cost" id="estimated_cost">
    </div>

    <button type="submit" class="btn btn-primary mt-3">Dispatch</button>
</form>

<script>
const GOOGLE_API_KEY = "";
const FUEL_COST_PER_LITER = 0.60;
const KM_PER_LITER = 10;
const DRIVER_FEE = 150;

let lastOrigin = "";
let lastDestination = "";
let cachedResult = null;

function updateMapAndETA() {
    const origin = document.getElementById("origin").value.trim();
    const destination = document.getElementById("destination").value.trim();
    const mapFrame = document.getElementById("mapFrame");

    if (!origin || !destination) {
        alert("Please enter both origin and destination.");
        return;
    }

    if (origin === lastOrigin && destination === lastDestination && cachedResult) {
        console.log("Using cached result for same route.");
        displayCost(cachedResult);
        return;
    }

    let mapUrl = GOOGLE_API_KEY
        ? `https://www.google.com/maps/embed/v1/directions?key=${GOOGLE_API_KEY}&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&mode=driving`
        : `https://www.google.com/maps?q=${encodeURIComponent(origin)}+to+${encodeURIComponent(destination)}&output=embed`;

    mapFrame.src = mapUrl;

    if (!GOOGLE_API_KEY) {
        const seed = origin.length + destination.length;
        const pseudoDistance = 5 + (seed % 46);
        const pseudoETA = 15 + (seed % 76);
        const estimatedCost = ((pseudoDistance / KM_PER_LITER) * FUEL_COST_PER_LITER + DRIVER_FEE).toFixed(2);

        cachedResult = { duration: pseudoETA + " mins (approx)", distance: pseudoDistance + " km (approx)", cost: estimatedCost };
        lastOrigin = origin;
        lastDestination = destination;
        displayCost(cachedResult);
        return;
    }

    fetch(`https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=${encodeURIComponent(origin)}&destinations=${encodeURIComponent(destination)}&key=${GOOGLE_API_KEY}`)
        .then(res => res.json())
        .then(data => {
            if (data.rows && data.rows[0].elements[0].status === "OK") {
                const duration = data.rows[0].elements[0].duration.text;
                const distanceText = data.rows[0].elements[0].distance.text;
                const distanceKm = parseFloat(distanceText.replace(" km", ""));
                const estimatedCost = ((distanceKm / KM_PER_LITER) * FUEL_COST_PER_LITER + DRIVER_FEE).toFixed(2);

                cachedResult = { duration, distance: distanceText, cost: estimatedCost };
                lastOrigin = origin;
                lastDestination = destination;
                displayCost(cachedResult);
            } else {
                document.getElementById("duration").innerText = "N/A";
                document.getElementById("distance").innerText = "N/A";
            }
        })
        .catch(() => {
            document.getElementById("duration").innerText = "Error fetching ETA";
        });
}

function displayCost(result) {
    document.getElementById("duration").innerText = result.duration;
    document.getElementById("distance").innerText = result.distance;
    document.getElementById("totalCost").innerText = result.cost;
    document.getElementById("estimated_cost").value = result.cost;
}
</script>
</body>
</html>