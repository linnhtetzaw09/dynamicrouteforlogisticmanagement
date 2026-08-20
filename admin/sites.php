<?php
/**
 * Sites Management Page
 * CRUD operations for site management (Admin only)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Sites Management';
include '../includes/header.php';


$message = '';
$message_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$site_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    if ($form_action === 'create' || $form_action === 'update') {
        // Retrieve and sanitize form data
        $site_name = isset($_POST['site_name']) ? trim($_POST['site_name']) : '';
        $address_line_1 = isset($_POST['address_line_1']) ? trim($_POST['address_line_1']) : '';
        $address_city = isset($_POST['address_city']) ? trim($_POST['address_city']) : '';
        $address_postcode = isset($_POST['address_postcode']) ? trim($_POST['address_postcode']) : '';
        $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

        // Validation for required fields
        if (empty($site_name) || empty($address_line_1) || empty($address_city) || empty($address_postcode) || empty($contact_phone) || empty($latitude) || empty($longitude)) {
            $message = 'All fields are required.';
            $message_type = 'danger';
        } else {
            // Check if the site name already exists (excluding the current site if updating)
            $checkQuery = "SELECT COUNT(*) as count FROM site WHERE site_name = ? AND site_id != ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param('si', $site_name, $site_id); 
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_site_count = $result->fetch_assoc()['count'];
            $stmt->close();

            if ($existing_site_count > 0) {
                // Site name already exists
                $message = 'Error: Site name already exists. Please choose a different name.';
                $message_type = 'danger';
            } else {
                // Validate phone number format (basic validation)
                if (!preg_match('/^[0-9]{10,15}$/', $contact_phone)) {
                    $message = 'Invalid phone number format. It should contain only numbers and be between 10 to 15 digits.';
                    $message_type = 'danger';
                } else {
                    // Proceed with insert or update
                    if ($form_action === 'create') {
                        $query = "INSERT INTO site (site_name, address_line_1, address_city, address_postcode, contact_phone, is_active, latitude, longitude) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('sssssidd', $site_name, $address_line_1, $address_city, $address_postcode, $contact_phone, $is_active, $latitude, $longitude);

                        if ($stmt->execute()) {
                            $message = 'Site created successfully!';
                            $message_type = 'success';
                            $action = 'list';
                        } else {
                            $message = 'Error creating site. Please try again.';
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    } elseif ($form_action === 'update') {
                        // Update existing site
                        $query = "UPDATE site SET site_name = ?, address_line_1 = ?, address_city = ?, address_postcode = ?, contact_phone = ?, is_active = ?, latitude = ?, longitude = ? 
                                  WHERE site_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('sssssiddi', $site_name, $address_line_1, $address_city, $address_postcode, $contact_phone, $is_active, $latitude, $longitude, $site_id);

                        if ($stmt->execute()) {
                            $message = 'Site updated successfully!';
                            $message_type = 'success';
                            $action = 'list';
                        } else {
                            $message = 'Error updating site. Please try again.';
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
    elseif ($form_action === 'delete') {
        // Delete site if no vehicles or jobs are associated
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;

        // Check if the site has any associated vehicles or jobs
        $result = $conn->query("SELECT COUNT(*) as count FROM vehicle WHERE home_site_id = $site_id");
        $vehicle_count = $result->fetch_assoc()['count'];

        $result = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = $site_id OR destination_site_id = $site_id");
        $job_count = $result->fetch_assoc()['count'];

        if ($vehicle_count > 0 || $job_count > 0) {
            $message = 'Cannot delete site. It has associated vehicles or jobs.';
            $message_type = 'danger';
        } else {
            $query = "DELETE FROM site WHERE site_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $site_id);

            if ($stmt->execute()) {
                $message = 'Site deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting site. Please try again.';
                $message_type = 'danger';
            }
            $stmt->close();
        }
        $action = 'list';
    }
}

// Get site data for edit
$site_data = null;
if ($action === 'edit' && $site_id > 0) {
    $query = "SELECT * FROM site WHERE site_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $site_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $site_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all sites
$sites = $conn->query("SELECT * FROM site ORDER BY site_name ASC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-map-marker-alt"></i> Sites Management</h1>
            <p class="lead">Create, read, update, and delete site information</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="card mb-5">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Sites</h5>
                    <a href="sites.php?action=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add New Site
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Site Name</th>
                                <th>City</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Vehicles</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($site = $sites->fetch_assoc()): 
                                $vehicle_count = $conn->query("SELECT COUNT(*) as count FROM vehicle WHERE home_site_id = " . $site['site_id'])->fetch_assoc()['count'];
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($site['site_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($site['address_city']); ?></td>
                                    <td><?php echo htmlspecialchars($site['address_line_1']); ?></td>
                                    <td><?php echo htmlspecialchars($site['contact_phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $site['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $site['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $vehicle_count; ?></td>
                                    <td>
                                        <a href="sites.php?action=edit&id=<?php echo $site['site_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="site_id" value="<?php echo $site['site_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- Form View -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $action === 'create' ? '<i class="fas fa-plus"></i> Create New Site' : '<i class="fas fa-edit"></i> Edit Site'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="form_action" value="<?php echo ($action === 'create') ? 'create' : 'update'; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="site_id" value="<?php echo $site_data['site_id']; ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="site_name" class="form-label">Site Name *</label>
                                        <input type="text" class="form-control" id="site_name" 
                                            name="site_name" value="<?php echo $site_data ? htmlspecialchars($site_data['site_name']) : ''; ?>" required >
                                        <div class="invalid-feedback">Site name is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="contact_phone" class="form-label">Contact Phone</label>
                                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo $site_data ? htmlspecialchars($site_data['contact_phone']) : ''; ?>" >
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="address_line_1" class="form-label">Address Line 1 *</label>
                                    <input type="text" class="form-control" id="address_line_1" name="address_line_1" value="<?php echo $site_data ? htmlspecialchars($site_data['address_line_1']) : ''; ?>" required >
                                    <div class="invalid-feedback">Address is required.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="address_city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="address_city" name="address_city" value="<?php echo $site_data ? htmlspecialchars($site_data['address_city']) : ''; ?>" required >
                                        <div class="invalid-feedback">City is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="address_postcode" class="form-label">Postcode *</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="address_postcode" 
                                            name="address_postcode" 
                                            value="<?php echo $site_data ? htmlspecialchars($site_data['address_postcode']) : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Postcode is required.</div>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-3">Site Map Location</h6>

                                <p class="text-muted">
                                    Search for a city or click the exact site location on the map.
                                </p>

                                <div class="row mb-3">
                                    <div class="col-md-9">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="map-search"
                                            placeholder="Example: Mandalay International Airport, Myanmar"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary w-100"
                                            id="search-location-button"
                                        >
                                            <i class="fas fa-search"></i>
                                            Search Map
                                        </button>
                                    </div>
                                </div>

                                <div
                                    id="site-map"
                                    style="
                                        height: 450px;
                                        width: 100%;
                                        border: 1px solid #ced4da;
                                        border-radius: 8px;
                                        margin-bottom: 15px;
                                    "
                                ></div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="latitude" class="form-label">
                                            Latitude *
                                        </label>

                                        <input
                                            type="number"
                                            step="0.0000001"
                                            class="form-control"
                                            id="latitude"
                                            name="latitude"
                                            value="<?php
                                                echo isset($site_data['latitude'])
                                                    ? htmlspecialchars($site_data['latitude'])
                                                    : '';
                                            ?>"
                                            readonly
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="longitude" class="form-label">
                                            Longitude *
                                        </label>

                                        <input
                                            type="number"
                                            step="0.0000001"
                                            class="form-control"
                                            id="longitude"
                                            name="longitude"
                                            value="<?php
                                                echo isset($site_data['longitude'])
                                                    ? htmlspecialchars($site_data['longitude'])
                                                    : '';
                                            ?>"
                                            readonly
                                            required
                                        >
                                    </div>
                                </div>

                                <div id="selected-address" class="alert alert-info">
                                    Click the map to select the site location.
                                </div>

                                <div class="form-check mb-4">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="is_active" 
                                        name="is_active"
                                        <?php echo ($site_data && $site_data['is_active']) ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label" for="is_active">
                                        Active Site
                                    </label>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $action === 'create' ? 'Create Site' : 'Update Site'; ?>
                                    </button>
                                    <a href="sites.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
     Bootstrap form validation
    */
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });


    /*
     Site location map
    */
    const mapElement = document.getElementById('site-map');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const searchInput = document.getElementById('map-search');
    const searchButton = document.getElementById('search-location-button');
    const selectedAddress = document.getElementById('selected-address');

    // Stop here if the map fields are not present on this page.
    if (!mapElement || !latitudeInput || !longitudeInput) {
        return;
    }

    const savedLatitude = parseFloat(latitudeInput.value);
    const savedLongitude = parseFloat(longitudeInput.value);

    // Default location: Mandalay
    const defaultLatitude = Number.isFinite(savedLatitude)
        ? savedLatitude
        : 21.9395615;

    const defaultLongitude = Number.isFinite(savedLongitude)
        ? savedLongitude
        : 96.1057290;

    const defaultZoom = (
        Number.isFinite(savedLatitude) &&
        Number.isFinite(savedLongitude)
    ) ? 16 : 11;

    const map = L.map('site-map').setView(
        [defaultLatitude, defaultLongitude],
        defaultZoom
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    let marker = null;

    function setSelectedLocation(latitude, longitude, label) {
        const lat = Number(latitude);
        const lon = Number(longitude);

        if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
            return;
        }

        latitudeInput.value = lat.toFixed(7);
        longitudeInput.value = lon.toFixed(7);

        if (marker) {
            marker.setLatLng([lat, lon]);
        } else {
            marker = L.marker(
                [lat, lon],
                {
                    draggable: true
                }
            ).addTo(map);

            marker.on('dragend', function (event) {
                const position = event.target.getLatLng();

                setSelectedLocation(
                    position.lat,
                    position.lng,
                    'Marker moved to selected location'
                );
            });
        }

        marker
            .bindPopup(label || 'Selected site location')
            .openPopup();

        if (selectedAddress) {
            selectedAddress.textContent =
                (label || 'Selected location') +
                ' — ' +
                lat.toFixed(7) +
                ', ' +
                lon.toFixed(7);
        }
    }

    // Show the existing database location when editing a site.
    if (
        Number.isFinite(savedLatitude) &&
        Number.isFinite(savedLongitude)
    ) {
        setSelectedLocation(
            savedLatitude,
            savedLongitude,
            'Saved site location'
        );
    }

    // Select coordinates by clicking the map.
    map.on('click', function (event) {
        setSelectedLocation(
            event.latlng.lat,
            event.latlng.lng,
            'Location selected from map'
        );
    });

    // Search by place name or address.
    if (searchButton && searchInput) {
        searchButton.addEventListener('click', async function () {
            const query = searchInput.value.trim();

            if (query === '') {
                alert('Enter a site name, city, or address.');
                searchInput.focus();
                return;
            }

            searchButton.disabled = true;
            searchButton.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Searching...';

            try {
                const url =
                    'https://nominatim.openstreetmap.org/search' +
                    '?format=jsonv2' +
                    '&limit=1' +
                    '&countrycodes=mm' +
                    '&q=' +
                    encodeURIComponent(query);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Location search failed.');
                }

                const results = await response.json();

                if (!Array.isArray(results) || results.length === 0) {
                    alert(
                        'Location not found. Try searching with city and ' +
                        'country, or click the location manually on the map.'
                    );
                    return;
                }

                const latitude = parseFloat(results[0].lat);
                const longitude = parseFloat(results[0].lon);
                const label = results[0].display_name;

                map.setView([latitude, longitude], 16);

                setSelectedLocation(
                    latitude,
                    longitude,
                    label
                );
            } catch (error) {
                console.error(error);
                alert(
                    'Could not search the location. You can still click ' +
                    'the exact location manually on the map.'
                );
            } finally {
                searchButton.disabled = false;
                searchButton.innerHTML =
                    '<i class="fas fa-search"></i> Search Map';
            }
        });

        // Allow Enter to search without submitting the whole form.
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchButton.click();
            }
        });
    }

    // Helpful when the map is inside a Bootstrap modal.
    document.querySelectorAll('.modal').forEach(function (modalElement) {
        modalElement.addEventListener(
            'shown.bs.modal',
            function () {
                setTimeout(function () {
                    map.invalidateSize();
                }, 150);
            }
        );
    });
});
</script>

<?php include '../includes/footer.php'; ?>
