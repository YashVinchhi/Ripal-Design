<?php
require_once __DIR__ . '/../includes/init.php';
$projectId = $_GET['id'] ?? 1;

// Sample static project data for UI prototype
$project = [
  'id' => $projectId,
  'name' => 'Renovation — Oak Street Residence',
  'status' => 'ongoing',
  'address' => '123 Oak St, Rajkot, Gujarat',
  'budget' => '₹ 45,00,000',
  'owner' => ['name' => 'Amitbhai Patel', 'contact' => '+91 98765 43210'],
  'workers' => [
    ['role' => 'Plumber', 'name' => 'Ramesh Kumar'],
    ['role' => 'Electrician', 'name' => 'Suresh Bhai'],
  ],
];

  // Handle POST to create/update project in DB
  // use centralized init for config, DB, and helpers
  require_once __DIR__ . '/../includes/init.php';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $status = trim($_POST['status'] ?? 'ongoing');
    $progress = (int) ($_POST['progress'] ?? 0);
    $due = $_POST['due'] ?? date('Y-m-d');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_contact = trim($_POST['owner_contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = is_numeric($_POST['lat'] ?? null) ? (float)$_POST['lat'] : null;
    $longitude = is_numeric($_POST['lng'] ?? null) ? (float)$_POST['lng'] : null;
    try {
      if (isset($pdo) && $pdo instanceof PDO) {
        // Ensure projects table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(255) NOT NULL,
          status VARCHAR(50) DEFAULT 'ongoing',
          progress INT DEFAULT 0,
          due DATE DEFAULT NULL,
          location VARCHAR(255) DEFAULT NULL,
          latitude DOUBLE DEFAULT NULL,
          longitude DOUBLE DEFAULT NULL,
          owner_name VARCHAR(255) DEFAULT NULL,
          owner_contact VARCHAR(100) DEFAULT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        if (!empty($projectId) && is_numeric($projectId)) {
          // try update if exists
          $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
          $stmt->execute(['id' => $projectId]);
          if ($stmt->fetch()) {
            $upd = $pdo->prepare('UPDATE projects SET name=:name, status=:status, progress=:progress, due=:due, location=:location, latitude=:latitude, longitude=:longitude, owner_name=:owner_name, owner_contact=:owner_contact WHERE id=:id');
            $upd->execute(['name'=>$name,'status'=>$status,'progress'=>$progress,'due'=>$due,'location'=>$location,'latitude'=>$latitude,'longitude'=>$longitude,'owner_name'=>$owner_name,'owner_contact'=>$owner_contact,'id'=>$projectId]);
          } else {
            $ins = $pdo->prepare('INSERT INTO projects (id,name,status,progress,due,location,latitude,longitude,owner_name,owner_contact) VALUES (:id,:name,:status,:progress,:due,:location,:latitude,:longitude,:owner_name,:owner_contact)');
            $ins->execute(['id'=>$projectId,'name'=>$name,'status'=>$status,'progress'=>$progress,'due'=>$due,'location'=>$location,'latitude'=>$latitude,'longitude'=>$longitude,'owner_name'=>$owner_name,'owner_contact'=>$owner_contact]);
          }
        } else {
          $ins = $pdo->prepare('INSERT INTO projects (name,status,progress,due,location,latitude,longitude,owner_name,owner_contact) VALUES (:name,:status,:progress,:due,:location,:latitude,:longitude,:owner_name,:owner_contact)');
          $ins->execute(['name'=>$name,'status'=>$status,'progress'=>$progress,'due'=>$due,'location'=>$location,'latitude'=>$latitude,'longitude'=>$longitude,'owner_name'=>$owner_name,'owner_contact'=>$owner_contact]);
          $projectId = $pdo->lastInsertId();
        }
      }
    } catch (Exception $e) {
      error_log('Project save failed: '.$e->getMessage());
    }
    // Redirect to avoid resubmission
    header('Location: project_details.php?id=' . urlencode($projectId));
    exit;
  }

// If DB available, load project values to populate the UI
if (isset($pdo) && $pdo instanceof PDO && !empty($projectId) && is_numeric($projectId)) {
  try {
    $stmt = $pdo->prepare('SELECT id,name,status,progress,due,location,latitude,longitude,owner_name,owner_contact FROM projects WHERE id = :id LIMIT 1');
    $stmt->execute(['id'=>$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $project['id'] = $row['id'];
      $project['name'] = $row['name'];
      $project['status'] = $row['status'] ?: $project['status'];
      $project['budget'] = $project['budget'] ?? '';
      $project['owner']['name'] = $row['owner_name'] ?? $project['owner']['name'];
      $project['owner']['contact'] = $row['owner_contact'] ?? $project['owner']['contact'];
      $project['progress'] = isset($row['progress']) ? (int)$row['progress'] : 0;
      $project['due'] = $row['due'];
      $project['address'] = $row['location'] ?? $project['address'];
      $project['location'] = $row['location'] ?? $project['address'];
      $project['latitude'] = isset($row['latitude']) ? $row['latitude'] : null;
      $project['longitude'] = isset($row['longitude']) ? $row['longitude'] : null;
    }
  } catch (Exception $e) {
    error_log('Load project failed: '.$e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($project['name']); ?> — Project Details</title>
  <?php asset_enqueue_css('/styles.css'); ?>
  <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
  <?php asset_enqueue_js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'); ?>
  <!--
    Google Maps: replace YOUR_API_KEY with a valid key that has Maps JavaScript API and Geocoding API enabled.
    Example URL: https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap&libraries=places
  -->
  <?php asset_enqueue_js('https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap&libraries=places', ['defer'=>true]); ?>
  <style>
    .project-header { margin-bottom: 18px; }
    .tab-pane { padding-top: 12px; }
  </style>
  <script>
    // map initialisation and geocoding
    let map, marker, geocoder;
    function initMap() {
      geocoder = new google.maps.Geocoder();
      const initial = { lat: 22.3039, lng: 70.8022 }; // fallback center (Rajkot)
      const lat = parseFloat(document.getElementById('lat-input')?.value || NaN);
      const lng = parseFloat(document.getElementById('lng-input')?.value || NaN);
      const start = (isFinite(lat) && isFinite(lng)) ? {lat: lat, lng: lng} : initial;
      map = new google.maps.Map(document.getElementById('map'), { center: start, zoom: isFinite(lat) ? 15 : 11 });
      // prefer AdvancedMarkerElement when available
      if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
        marker = new google.maps.marker.AdvancedMarkerElement({ position: start, map: map });
        marker.options = marker.options || {};
        marker.draggable = true;
        if (!isFinite(lat) || !isFinite(lng)) {
          marker.map = null; // hide
        }
        map.addListener('click', (e) => {
          placeMarkerAndPan(e.latLng);
          geocodeLatLng(e.latLng);
        });
        // AdvancedMarkerElement doesn't have drag events in the same way; fallback: listen to map dragend if necessary
      } else {
        marker = new google.maps.Marker({ position: start, map: map, draggable: true });
        if (!isFinite(lat) || !isFinite(lng)) {
          marker.setVisible(false);
        }
        map.addListener('click', (e) => {
          placeMarkerAndPan(e.latLng);
          geocodeLatLng(e.latLng);
        });
        marker.addListener('dragend', function(e){
          geocodeLatLng(e.latLng);
        });
      }

      document.getElementById('geocode-btn').addEventListener('click', function(){
        const addr = document.getElementById('address-search').value || document.getElementById('location-input').value;
        if (!addr) return alert('Enter address to geocode');
        geocoder.geocode({ address: addr }, function(results, status){
          if (status === 'OK' && results[0]) {
            const loc = results[0].geometry.location;
            placeMarkerAndPan(loc);
            document.getElementById('location-input').value = results[0].formatted_address;
            setLatLngInputs(loc.lat(), loc.lng());
          } else {
            alert('Geocode failed: ' + status);
          }
        });
      });
    }

    function placeMarkerAndPan(latLng) {
      if (google.maps.marker && google.maps.marker.AdvancedMarkerElement && marker instanceof google.maps.marker.AdvancedMarkerElement) {
        marker.position = latLng;
        marker.map = map;
      } else if (marker && marker.setPosition) {
        marker.setPosition(latLng);
        marker.setVisible(true);
      }
      map.panTo(latLng);
    }

    function setLatLngInputs(lat, lng) {
      document.getElementById('lat-input').value = lat;
      document.getElementById('lng-input').value = lng;
    }

    function geocodeLatLng(latLng) {
      geocoder.geocode({ location: latLng }, function(results, status){
        if (status === 'OK' && results[0]) {
          document.getElementById('location-input').value = results[0].formatted_address;
          setLatLngInputs(latLng.lat(), latLng.lng());
        } else {
          // still set coords
          setLatLngInputs(latLng.lat(), latLng.lng());
        }
      });
    }
  </script>
</head>
<body>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard">
    <div class="project-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1><?php echo htmlspecialchars($project['name']); ?></h1>
          <div class="muted"><?php echo htmlspecialchars($project['address']); ?></div>
        </div>
        <div class="avatar" aria-hidden="true"><?php echo strtoupper(substr(($project['name'] ?? 'P'),0,2)); ?></div>
      </div>
    </div>

    <ul class="nav nav-tabs" id="projTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab">Team</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">Files</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Activity</button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <section class="info-card">
          <h3>Overview</h3>
          <div class="row">
            <div class="col-md-6">
              <dl class="data-list">
                <dt>Budget</dt>
                <dd><?php echo $project['budget']; ?></dd>
                <dt>Status</dt>
                <dd><span class="status-badge ongoing"><?php echo ucfirst($project['status']); ?></span></dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h4>Owner</h4>
              <p><?php echo htmlspecialchars($project['owner']['name']); ?><br><a href="tel:<?php echo $project['owner']['contact']; ?>"><?php echo $project['owner']['contact']; ?></a></p>
            </div>
          </div>
          <hr>
          <form method="post" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Name</label>
              <input name="name" class="form-control" value="<?php echo htmlspecialchars($project['name']); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="ongoing" <?php if($project['status']=='ongoing') echo 'selected'; ?>>Ongoing</option>
                <option value="paused" <?php if($project['status']=='paused') echo 'selected'; ?>>Paused</option>
                <option value="completed" <?php if($project['status']=='completed') echo 'selected'; ?>>Completed</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Progress (%)</label>
              <input type="number" name="progress" class="form-control" min="0" max="100" value="<?php echo intval($project['progress'] ?? 0); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Due Date</label>
              <input type="date" name="due" class="form-control" value="<?php echo htmlspecialchars($project['due'] ?? date('Y-m-d')); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Owner Name</label>
              <input name="owner_name" class="form-control" value="<?php echo htmlspecialchars($project['owner']['name']); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Owner Contact</label>
              <input name="owner_contact" class="form-control" value="<?php echo htmlspecialchars($project['owner']['contact']); ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Site Location (address)</label>
                <input id="location-input" name="location" class="form-control" placeholder="Enter full site address or landmark" value="<?php echo htmlspecialchars($project['address'] ?? ($project['location'] ?? '')); ?>">
            </div>
            <div class="col-12">
              <button class="btn btn-primary">Save Project</button>
            </div>
          </form>
            <div class="mt-3">
              <div class="form-text">Use the map below: click to place marker or enter address and click "Geocode".</div>
              <div id="map" style="height:320px; width:100%; border:1px solid #ddd; margin-top:8px;"></div>
              <div class="mt-2">
                <input id="address-search" class="form-control d-inline-block" style="width:70%;" placeholder="Search address to geocode">
                <button id="geocode-btn" class="btn btn-secondary">Geocode</button>
              </div>
              <input type="hidden" id="lat-input" name="lat" value="<?php echo htmlspecialchars($project['latitude'] ?? ''); ?>">
              <input type="hidden" id="lng-input" name="lng" value="<?php echo htmlspecialchars($project['longitude'] ?? ''); ?>">
            </div>
        </section>
      </div>

      <div class="tab-pane fade" id="team" role="tabpanel">
        <section class="info-card">
          <h3>Assigned Team</h3>
          <div class="list-group">
            <?php foreach($project['workers'] as $w): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold"><?php echo htmlspecialchars($w['name']); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($w['role']); ?></small>
                </div>
                <div>
                  <button class="btn outline">Message</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="files" role="tabpanel">
        <section class="info-card">
          <h3>Files & Drawings</h3>
          <div class="drawing-grid">
            <div class="drawing-card">
              <i class="bi bi-file-earmark-pdf drawing-icon"></i>
              <div class="fw-bold">Ground Floor Plan.pdf</div>
              <small class="text-muted">2026-01-15</small>
            </div>
            <div class="drawing-card">
              <i class="bi bi-file-earmark-image drawing-icon"></i>
              <div class="fw-bold">Plumbing Diagram.jpg</div>
              <small class="text-muted">2026-01-22</small>
            </div>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="activity" role="tabpanel">
        <section class="info-card">
          <h3>Activity Log</h3>
          <ul class="list-group list-group-flush">
            <li class="list-group-item">2026-02-01 — Project created by employee01</li>
            <li class="list-group-item">2026-02-05 — Worker assigned: Suresh Bhai</li>
          </ul>
        </section>
      </div>
    </div>

  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>