<?php require_once 'includes/config.php'; ?>  

<?php 
$ars = $db->select('ar', columns: '*');
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $points = trim($_POST['points'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $x = intval($_POST['x'] ?? 0);
    $y = intval($_POST['y'] ?? 0);
    $xy_coords = "$x,$y";

    // Parse lat/lng from coordinates field
    $gmapcoordinates = '';
    if (!empty($_POST['coordinates'])) {
        $gmapcoordinates = trim($_POST['coordinates']);
    }

    $ar_path = '';
    if (isset($_FILES['mind_file']) && $_FILES['mind_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/images/ar/';
        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid('ar_', true) . '_' . basename($_FILES['mind_file']['name']);
        $targetPath = "{$uploadDir}{$filename}";

        if (move_uploaded_file($_FILES['mind_file']['tmp_name'], $targetPath)) {
            $ar_path = $targetPath;
        }
    }

   if ($action === 'edit' && $id > 0) {
    $existingPath = '';
    $existing = $db->select('ar', '*', ['id' => $id]);

    if ($existing && isset($existing['data']) && is_array($existing['data']) && count($existing['data']) > 0) {
        $existingRow = $existing['data'][0];
        $existingPath = $existingRow['ar_path'] ?? '';
    }

    // If no new file uploaded, keep existing path
    if (empty($ar_path) && !empty($existingPath)) {
        $ar_path = $existingPath;
    }
}

    
    // Prepare data
    $data = [
        'name' => $name,
        'points' => $points,
        'description' => $description,
        'coordinates' => $xy_coords,
        'gmapcoordinates' => $gmapcoordinates,
        'ar_path' => $ar_path
    ];

    if ($action === 'edit' && $id > 0) {
        $result = $db->update('ar', $data, ['id' => $id]);
        if ($result && $result['status'] === 'success') {
            echo "<script>alert('Update success');window.location.href = window.location.pathname;</script>";
            exit;
        }
    } else {
        $result = $db->insert('ar', $data);
        if ($result) {
            echo "<script>alert('Insert success');window.location.href = window.location.pathname;</script>";
            exit;
        }
    }
}

?>
<!DOCTYPE html>
    <html lang="en">

        <?php require_once 'includes/head.php'; ?>  
    
    <body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
        <!-- Begin page -->
        <div class="wrapper">
            <?php require_once 'includes/sidebar.php'; ?>  
            <?php require_once 'includes/topbar.php'; ?>  

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">
               
                    
                    <!-- Start Content-->
                    <div class="container-fluid">
  <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                   
                                    <h4 class="page-title">Profile</h4>
                                </div>
                            </div>
                        </div>
     <div class="row">
    <div class="col-md-8">
        <div class="card position-relative" style="overflow: hidden;">
            <img id="mapImage" src="assets/images/map.webp" class="card-img-top img-fluid w-100" style="max-width: 100%; height: auto; cursor: crosshair;" alt="Full Width Image">
            <div id="markersContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
        </div>
        <button id="addMarkerBtn" class="btn btn-success mt-2">Add Marker</button>
    </div>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>

    <script>
    const initialMarkers = <?php echo json_encode(array_map(function($item) {
        $coords = explode(',', $item['coordinates']);
        return [
            'id' => (int)$item['id'],
            'points' => (int)$item['points'],
            'number' => '', // Can use $item['number'] if available
            'name' => $item['name'],
            'description' => $item['description'],
            'x' => (int)$coords[0],
            'y' => (int)$coords[1],
            'mind_path' => $item['ar_path'],
            'gmapcoordinates' => $item['gmapcoordinates'] ?? ''
        ];
    }, $ars['data'] ?? [])); ?>;
    </script>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <form id="markerForm" autocomplete="off" enctype="multipart/form-data" method="post">
                    <input type="hidden" name="action" id="markerAction" value="add">
                    <input type="hidden" id="markerId" name="id">
                    <input hidden type="text" class="form-control" id="markerNumber" name="number" placeholder="QR ID" maxlength="10" pattern="[A-F0-9]{10}" style="text-transform:uppercase" autocomplete="off"
                        value="<?php echo strtoupper(bin2hex(random_bytes(5))); ?>">
                    <div class="mb-2">
                        <label for="markerPoints" class="form-label">Points</label>
                        <input type="number" class="form-control" id="markerPoints" name="points" placeholder="Points">
                    </div>
                    <div class="mb-2">
                        <label for="markerName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="markerName" name="name" placeholder="Name">
                    </div>
                    <div class="mb-2">
                        <label for="markerDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="markerDescription" name="description" placeholder="Description"></textarea>
                    </div>
                    <script>
                    let ckeditorInstance;
                    document.addEventListener('DOMContentLoaded', function() {
                        ClassicEditor
                            .create(document.querySelector('#markerDescription'))
                            .then(editor => {
                                window.ckeditorInstance = editor;
                            })
                            .catch(error => {
                                console.error(error);
                            });
                    });
                    </script>
                    <div class="mb-2" >
                        <label for="markerMindFile" class="form-label">Augmented Compiled File (.mind)</label>
                        <input type="file" class="form-control" id="markerMindFile" name="mind_file" accept=".mind">
                        <small>
                            Don't have a .mind file? 
                            <a href="https://innovatechservicesph.com/ar" target="_blank">Create .mind file here</a>
                        </small>
                    </div>
                    <div class="mb-2">
                        <label for="coordinates" class="form-label">Coordinates (Decimal or DMS)</label>
                        <input type="text" class="form-control" id="coordinates" name="coordinates" placeholder="e.g. 13.96764,121.56432 or 13°58'03.5&quot;N 121°33'08.6&quot;E">
                        <div id="latlngDisplay" class="mt-1" style="font-size: 90%; color: #555;"></div>
                    </div>
                    <script>
                    function dmsToDecimal(dms, direction) {
                        // dms: "13°58'03.5\"", direction: "N"
                        const regex = /(\d+)°(\d+)'(\d+(?:\.\d+)?)/;
                        const parts = dms.match(regex);
                        if (!parts) return null;
                        let degrees = parseFloat(parts[1]);
                        let minutes = parseFloat(parts[2]);
                        let seconds = parseFloat(parts[3]);
                        let decimal = degrees + minutes / 60 + seconds / 3600;
                        if (direction === 'S' || direction === 'W') decimal *= -1;
                        return decimal;
                    }

                    function parseCoordinatesInput(val) {
                        val = val.trim();
                        // Try DMS pattern: 13°58'03.5"N 121°33'08.6"E
                        const dmsPattern = /(\d+°\d+'\d+(?:\.\d+)?")([NS])\s+(\d+°\d+'\d+(?:\.\d+)?")([EW])/i;
                        const match = val.match(dmsPattern);
                        if (match) {
                            const lat = dmsToDecimal(match[1], match[2].toUpperCase());
                            const lng = dmsToDecimal(match[3], match[4].toUpperCase());
                            if (lat !== null && lng !== null) {
                                return { value: lat + ',' + lng, lat, lng };
                            }
                        }
                        // Try decimal: 13.96764,121.56432
                        const decPattern = /^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/;
                        const decMatch = val.match(decPattern);
                        if (decMatch) {
                            return { value: decMatch[1] + ',' + decMatch[2], lat: parseFloat(decMatch[1]), lng: parseFloat(decMatch[2]) };
                        }
                        return { value: '', lat: null, lng: null };
                    }

                    function updateLatLngDisplay(val) {
                        const result = parseCoordinatesInput(val);
                        const display = document.getElementById('latlngDisplay');
                        if (result.lat !== null && result.lng !== null) {
                            display.innerHTML = 'Latitude: <b>' + result.lat + '</b><br>Longitude: <b>' + result.lng + '</b>';
                        } else {
                            display.innerHTML = '';
                        }
                    }

                    document.getElementById('coordinates').addEventListener('blur', function(e) {
                        const val = e.target.value;
                        const result = parseCoordinatesInput(val);
                        if (result.value && result.value !== val) {
                            e.target.value = result.value;
                        }
                        updateLatLngDisplay(e.target.value);
                    });
                    document.getElementById('coordinates').addEventListener('input', function(e) {
                        updateLatLngDisplay(e.target.value);
                    });
                    </script>
                    <div class="mb-2">
                        <label for="markerX" class="form-label">X</label>
                        <input type="number" class="form-control" id="markerX" name="x" placeholder="X" readonly>
                    </div>
                    <div class="mb-2">
                        <label for="markerY" class="form-label">Y</label>
                        <input type="number" class="form-control" id="markerY" name="y" placeholder="Y" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Marker</button>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script>
    let markers = [];
    let selectedMarkerId = null;
    const markersContainer = document.getElementById('markersContainer');
    const mapImage = document.getElementById('mapImage');
    const addMarkerBtn = document.getElementById('addMarkerBtn');
    const markerForm = document.getElementById('markerForm');
    const coordinatesInput = document.getElementById('coordinates');

    // Prevent dragging image
    mapImage.ondragstart = () => false;

    function getRelativeCoords(event) {
        const rect = mapImage.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * mapImage.naturalWidth;
        const y = ((event.clientY - rect.top) / rect.height) * mapImage.naturalHeight;
        return { x: Math.round(x), y: Math.round(y) };
    }

    function renderMarkers() {
        markersContainer.innerHTML = '';
        markers.forEach(marker => {
            const markerDiv = document.createElement('div');
            markerDiv.className = 'marker';
            markerDiv.style.background = marker.id === selectedMarkerId ? 'red' : 'blue';
            markerDiv.style.position = 'absolute';
            markerDiv.style.left = (marker.x / mapImage.naturalWidth * 100) + '%';
            markerDiv.style.top = (marker.y / mapImage.naturalHeight * 100) + '%';
            markerDiv.title = marker.name || 'Marker';
            markerDiv.setAttribute('data-id', marker.id);
            markerDiv.textContent = marker.points || '';
            markerDiv.style.pointerEvents = 'auto';

            markerDiv.onmousedown = function(e) {
                e.preventDefault();
                selectedMarkerId = marker.id;
                fillForm(marker);
                let shiftX = e.clientX - markerDiv.getBoundingClientRect().left;
                let shiftY = e.clientY - markerDiv.getBoundingClientRect().top;

                function moveAt(e) {
                    const rect = mapImage.getBoundingClientRect();
                    let x = ((e.clientX - rect.left) / rect.width) * mapImage.naturalWidth;
                    let y = ((e.clientY - rect.top) / rect.height) * mapImage.naturalHeight;
                    x = Math.max(0, Math.min(mapImage.naturalWidth, x));
                    y = Math.max(0, Math.min(mapImage.naturalHeight, y));
                    marker.x = Math.round(x);
                    marker.y = Math.round(y);
                    fillForm(marker);
                    renderMarkers();
                }

                function onMouseMove(e) {
                    moveAt(e);
                }

                document.addEventListener('mousemove', onMouseMove);

                document.onmouseup = function() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.onmouseup = null;
                };
            };

            markerDiv.onclick = function(e) {
                e.stopPropagation();
                selectedMarkerId = marker.id;
                fillForm(marker);
                renderMarkers();
            };

            markersContainer.appendChild(markerDiv);
        });
    }

    function fillForm(marker) {
        markerForm.markerId.value = marker.id;
        markerForm.markerPoints.value = marker.points || '';
        markerForm.markerNumber.value = marker.number || '';
        markerForm.markerX.value = marker.x;
        markerForm.markerY.value = marker.y;
        // Fill coordinates field with gmap coordinates if available
        if (marker.gmapcoordinates) {
            markerForm.coordinates.value = marker.gmapcoordinates;
        } else {
            markerForm.coordinates.value = '';
        }
        // Update lat/lng display
        if (typeof updateLatLngDisplay === 'function') updateLatLngDisplay(markerForm.coordinates.value);
        markerForm.markerName.value = marker.name || '';
        // Set CKEditor value for description
        if (window.ckeditorInstance) {
            window.ckeditorInstance.setData(marker.description || '');
        } else {
            markerForm.markerDescription.value = marker.description || '';
        }
        markerForm.markerAction.value = 'edit';
    }

    addMarkerBtn.onclick = function() {
        mapImage.style.cursor = 'crosshair';
        function onClick(e) {
            const coords = getRelativeCoords(e);
            const newId = Date.now();
            const pointValue = markers.length + 1;
            const marker = {
                id: newId,
                points: pointValue,
                number: '',
                x: coords.x,
                y: coords.y,
                name: '',
                description: '',
                gmapcoordinates: ''
            };
            markers.push(marker);
            selectedMarkerId = newId;
            renderMarkers();
            fillForm(marker);
            mapImage.removeEventListener('click', onClick);
            mapImage.style.cursor = 'pointer';
            markerForm.markerAction.value = 'add';
        }
        mapImage.addEventListener('click', onClick);
    };

    markerForm.onsubmit = function(e) {
        e.preventDefault();
        const id = parseInt(markerForm.markerId.value);
        const marker = markers.find(m => m.id === id);
        // Get CKEditor value for description
        let descriptionValue = '';
        if (window.ckeditorInstance) {
            descriptionValue = window.ckeditorInstance.getData();
            markerForm.markerDescription.value = descriptionValue;
        } else {
            descriptionValue = markerForm.markerDescription.value;
        }
        if (marker) {
            marker.points = markerForm.markerPoints.value;
            marker.number = markerForm.markerNumber.value;
            marker.name = markerForm.markerName.value;
            marker.description = descriptionValue;
            // Save gmap coordinates from form back to marker so it will be sent
            marker.gmapcoordinates = markerForm.coordinates.value;
        }
        renderMarkers();
        markerForm.submit();
    };

    window.onload = function() {
        mapImage.onload = () => {
            markers = initialMarkers || [];
            renderMarkers();
        };
        if (mapImage.complete) {
            markers = initialMarkers || [];
            renderMarkers();
        }
    };

    mapImage.onclick = function () {
        selectedMarkerId = null;
        markerForm.reset();
        markerForm.markerId.value = '';
        markerForm.markerX.value = '';
        markerForm.markerY.value = '';
        markerForm.markerAction.value = 'add';
        // clear coordinates and latlng display
        markerForm.coordinates.value = '';
        if (typeof updateLatLngDisplay === 'function') updateLatLngDisplay('');
        // Clear CKEditor value
        if (window.ckeditorInstance) {
            window.ckeditorInstance.setData('');
        }
        renderMarkers();
    };
    </script>

    <style>
.marker {
    width: 28px;
    height: 28px;
    background-color: blue;
    border-radius: 50%;
    position: absolute;
    transform: translate(-50%, -100%);
    cursor: pointer;
    z-index: 10;
    color: white;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    line-height: 28px; /* vertically center the number */
    box-shadow: 0 0 0 2px #fff, 0 2px 8px rgba(0,0,0,0.2);
    pointer-events: auto;
    user-select: none;
}

                        #mapImage {
    position: relative;
    z-index: 1;
}

#markersContainer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none; /* allows events to fall through except for children */
    z-index: 2;
}

                        </style>

                    </div>
                    <!-- container -->

                </div>
                <!-- content -->



            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

    <?php require_once 'includes/right-sidebar.php'; ?>

        <div class="rightbar-overlay"></div>
        <!-- /End-bar -->

        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>

    </body>

</html>