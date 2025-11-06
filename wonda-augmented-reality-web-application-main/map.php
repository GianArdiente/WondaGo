 

<?php require_once 'includes/config.php'; ?>  

<?php 
$ars = $db->select('ar', columns: '*');
 
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
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <h4 class="page-title">Map Info</h4>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-group" role="group" aria-label="Map Type">
                                    <button id="btnImageMap" class="btn btn-primary active">Image Map</button>
                                    <button id="btnLeafletMap" class="btn btn-outline-primary">GPS Map</button>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="imageMapContainer">
                            <div class="col-md-12">
                                <div class="card position-relative" style="overflow: hidden;">
                                    <img id="mapImage" src="assets/images/map.webp" class="card-img-top img-fluid w-100" style="max-width: 100%; height: auto;" alt="Map">
                                    <div id="markersContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="leafletMapContainer" style="display: none;">
                            <div class="col-md-12">
                                <div class="card">
                                    <div id="leafletMap" style="width: 100%; height: 500px;"></div>
                                </div>
                            </div>
                        </div>

                        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
                        <link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
                        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
                        <script src="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
                        <script src="https://unpkg.com/leaflet-providers"></script>

                        <script>
                            const initialMarkers = <?php echo json_encode(array_map(function($item) {
                                $coords = explode(',', $item['coordinates']);
                                $gmap = isset($item['gmapcoordinates']) ? explode(',', $item['gmapcoordinates']) : [null, null];
                                return [
                                    'id' => (int)$item['id'],
                                    'points' => (int)$item['points'],
                                    'name' => $item['name'],
                                    'description' => $item['description'],
                                    'x' => (int)$coords[0],
                                    'y' => (int)$coords[1],
                                    'lat' => isset($gmap[0]) ? floatval($gmap[0]) : null,
                                    'lng' => isset($gmap[1]) ? floatval($gmap[1]) : null
                                ];
                            }, $ars['data'] ?? [])); ?>;

                            // Image Map logic
                            const mapImage = document.getElementById('mapImage');
                            const markersContainer = document.getElementById('markersContainer');

                            function renderMarkers() {
                                markersContainer.innerHTML = '';
                                initialMarkers.forEach(marker => {
                                    const markerDiv = document.createElement('div');
                                    markerDiv.className = 'marker';
                                    markerDiv.style.width = '18px';
                                    markerDiv.style.height = '18px';
                                    markerDiv.style.lineHeight = '18px';
                                    markerDiv.style.fontSize = '11px';
                                    markerDiv.style.left = (marker.x / mapImage.naturalWidth * 100) + '%';
                                    markerDiv.style.top = (marker.y / mapImage.naturalHeight * 100) + '%';
                                    markerDiv.textContent = marker.points || '';
                                    markerDiv.title = marker.name || 'Marker';
                                    markerDiv.style.pointerEvents = 'auto';

                                    markerDiv.onclick = () => {
                                        Swal.fire({
                                            title: `${marker.name}`,
                                            html: `<p><strong>Points:</strong> ${marker.points}</p>
                                                   <p>${marker.description}</p>
                                                   <p><strong>Coordinates:</strong> ${marker.lat && marker.lng ? marker.lat + ', ' + marker.lng : 'N/A'}</p>`,
                                            icon: 'info',
                                            confirmButtonText: 'Close'
                                        });
                                    };

                                    markersContainer.appendChild(markerDiv);
                                });
                            }

                            // Leaflet Map logic
                            let leafletMap = null;
                            let leafletMarkers = [];
                            let userMarker = null;
                            let userCircle = null;
                            let userLatLng = null;
                            let baseLayers = null;
                            let layerControl = null;

                            function getDistanceAndDirection(from, to) {
                                if (!from || !to) return {distance: null, direction: null};
                                const R = 6371e3; // metres
                                const φ1 = from.lat * Math.PI/180;
                                const φ2 = to.lat * Math.PI/180;
                                const Δφ = (to.lat-from.lat) * Math.PI/180;
                                const Δλ = (to.lng-from.lng) * Math.PI/180;

                                const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                                          Math.cos(φ1) * Math.cos(φ2) *
                                          Math.sin(Δλ/2) * Math.sin(Δλ/2);
                                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                                const d = R * c;

                                // Bearing
                                const y = Math.sin(Δλ) * Math.cos(φ2);
                                const x = Math.cos(φ1)*Math.sin(φ2) -
                                          Math.sin(φ1)*Math.cos(φ2)*Math.cos(Δλ);
                                let θ = Math.atan2(y, x);
                                θ = θ * 180/Math.PI; // in degrees
                                θ = (θ + 360) % 360;

                                // Synthesize direction
                                let direction = '';
                                if (θ >= 337.5 || θ < 22.5) direction = 'head north';
                                else if (θ >= 22.5 && θ < 67.5) direction = 'head northeast';
                                else if (θ >= 67.5 && θ < 112.5) direction = 'head east';
                                else if (θ >= 112.5 && θ < 157.5) direction = 'head southeast';
                                else if (θ >= 157.5 && θ < 202.5) direction = 'head south';
                                else if (θ >= 202.5 && θ < 247.5) direction = 'head southwest';
                                else if (θ >= 247.5 && θ < 292.5) direction = 'head west';
                                else if (θ >= 292.5 && θ < 337.5) direction = 'head northwest';

                                return {distance: d, direction};
                            }

                            function renderLeafletMap() {
                                if (!leafletMap) {
                                    // Base layers
                                    baseLayers = {
                                        "OpenStreetMap": L.tileLayer.provider('OpenStreetMap.Mapnik'),
                                        "Satellite": L.tileLayer.provider('Esri.WorldImagery'),
                                        "Street View": L.tileLayer.provider('CartoDB.Positron'),
                                        "Topographic": L.tileLayer.provider('OpenTopoMap')
                                    };

                                    leafletMap = L.map('leafletMap', {
                                        center: [13.967083333333333, 121.55161111111111],
                                        zoom: 15,
                                        layers: [baseLayers["OpenStreetMap"]]
                                    });

                                    layerControl = L.control.layers(baseLayers).addTo(leafletMap);

                                    // Add locate control for "You are here"
                                    L.control.locate({
                                        position: 'topleft',
                                        flyTo: true,
                                        showPopup: false,
                                        drawCircle: true,
                                        keepCurrentZoomLevel: true,
                                        strings: {
                                            title: "Show your location"
                                        },
                                        onLocationError: function(err) {
                                            alert(err.message);
                                        },
                                        onLocationOutsideMapBounds: function(context) {
                                            alert(context.options.strings.outsideMapBoundsMsg);
                                        },
                                        setView: 'once',
                                        locateOptions: {
                                            enableHighAccuracy: true
                                        }
                                    }).addTo(leafletMap);

                                    // Try to get user location and show marker
                                    if (navigator.geolocation) {
                                        navigator.geolocation.getCurrentPosition(function(position) {
                                            const lat = position.coords.latitude;
                                            const lng = position.coords.longitude;
                                            userLatLng = {lat, lng};
                                            if (userMarker) leafletMap.removeLayer(userMarker);
                                            if (userCircle) leafletMap.removeLayer(userCircle);

                                            userMarker = L.marker([lat, lng], {
                                                icon: L.icon({
                                                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
                                                    iconSize: [32, 32],
                                                    iconAnchor: [16, 32],
                                                    popupAnchor: [0, -32]
                                                })
                                            }).addTo(leafletMap).bindPopup("<b>You are here</b>").openPopup();

                                            userCircle = L.circle([lat, lng], {
                                                radius: position.coords.accuracy,
                                                color: '#136AEC',
                                                fillColor: '#136AEC',
                                                fillOpacity: 0.15
                                            }).addTo(leafletMap);

                                            leafletMap.setView([lat, lng], 17);
                                        }, function(error) {
                                            // User denied or error, do nothing
                                        });
                                    }
                                }
                                // Remove old markers
                                leafletMarkers.forEach(m => leafletMap.removeLayer(m));
                                leafletMarkers = [];
                                // Add new markers
                                initialMarkers.forEach(marker => {
                                    if (marker.lat && marker.lng) {
                                        const m = L.marker([marker.lat, marker.lng]).addTo(leafletMap)
                                            .on('click', function() {
                                                let userInfo = '';
                                                if (userLatLng) {
                                                    const {distance, direction} = getDistanceAndDirection(userLatLng, {lat: marker.lat, lng: marker.lng});
                                                    if (distance !== null) {
                                                        userInfo = `<p><strong>Your distance:</strong> ${(distance/1e3).toFixed(2)} km, ${direction}</p>`;
                                                    }
                                                }
                                                Swal.fire({
                                                    title: marker.name,
                                                    html: `<p><strong>Points:</strong> ${marker.points}</p>
                                                           <p>${marker.description}</p>
                                                           <p><strong>Coordinates:</strong> ${marker.lat}, ${marker.lng}</p>
                                                           ${userInfo}`,
                                                    icon: 'info',
                                                    confirmButtonText: 'Close'
                                                });
                                            })
                                            .bindPopup(`<b>${marker.name}</b><br>Points: ${marker.points}<br>${marker.description}`);
                                        leafletMarkers.push(m);
                                    }
                                });
                            }

                            // Toggle logic
                            document.getElementById('btnImageMap').onclick = function() {
                                this.classList.add('btn-primary');
                                this.classList.remove('btn-outline-primary');
                                document.getElementById('btnLeafletMap').classList.remove('btn-primary');
                                document.getElementById('btnLeafletMap').classList.add('btn-outline-primary');
                                document.getElementById('imageMapContainer').style.display = '';
                                document.getElementById('leafletMapContainer').style.display = 'none';
                            };
                            document.getElementById('btnLeafletMap').onclick = function() {
                                this.classList.add('btn-primary');
                                this.classList.remove('btn-outline-primary');
                                document.getElementById('btnImageMap').classList.remove('btn-primary');
                                document.getElementById('btnImageMap').classList.add('btn-outline-primary');
                                document.getElementById('imageMapContainer').style.display = 'none';
                                document.getElementById('leafletMapContainer').style.display = '';
                                setTimeout(renderLeafletMap, 100); // ensure container is visible
                            };

                            window.onload = () => {
                                if (mapImage.complete) renderMarkers();
                                else mapImage.onload = renderMarkers;
                            };
                        </script>

                        <style>
                        .marker {
                            width: 18px;
                            height: 18px;
                            background-color: #007bff;
                            border-radius: 50%;
                            position: absolute;
                            transform: translate(-50%, -100%);
                            cursor: pointer;
                            z-index: 10;
                            color: white;
                            font-size: 11px;
                            font-weight: bold;
                            text-align: center;
                            line-height: 18px;
                            box-shadow: 0 0 0 2px #fff, 0 2px 8px rgba(0,0,0,0.2);
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
                            z-index: 2;
                        }
                        #leafletMap { min-height: 400px; }
                            .marker {
                                width: 28px;
                                height: 28px;
                                background-color: #007bff;
                                border-radius: 50%;
                                position: absolute;
                                transform: translate(-50%, -100%);
                                cursor: pointer;
                                z-index: 10;
                                color: white;
                                font-size: 14px;
                                font-weight: bold;
                                text-align: center;
                                line-height: 28px;
                                box-shadow: 0 0 0 2px #fff, 0 2px 8px rgba(0,0,0,0.2);
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
                                z-index: 2;
                            }
                            #leafletMap { min-height: 400px; }
                        </style>

                    </div>
                </div>
            </div>

<!-- Scripts -->
<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <!-- Right Sidebar -->
        <div class="end-bar">

            <div class="rightbar-title">
                <a href="javascript:void(0);" class="end-bar-toggle float-end">
                    <i class="dripicons-cross noti-icon"></i>
                </a>
                <h5 class="m-0">Settings</h5>
            </div>

            <div class="rightbar-content h-100" data-simplebar>

                <div class="p-3">
                    <div class="alert alert-warning" role="alert">
                        <strong>Customize </strong> the overall color scheme, sidebar menu, etc.
                    </div>

                    <!-- Settings -->
                    <h5 class="mt-3">Color Scheme</h5>
                    <hr class="mt-1" />

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="light" id="light-mode-check" checked>
                        <label class="form-check-label" for="light-mode-check">Light Mode</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="dark" id="dark-mode-check">
                        <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
                    </div>
       

                    <!-- Width -->
                    <h5 class="mt-4">Width</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="fluid" id="fluid-check" checked>
                        <label class="form-check-label" for="fluid-check">Fluid</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="boxed" id="boxed-check">
                        <label class="form-check-label" for="boxed-check">Boxed</label>
                    </div>
        

                    <!-- Left Sidebar-->
                    <h5 class="mt-4">Left Sidebar</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="default" id="default-check">
                        <label class="form-check-label" for="default-check">Default</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="light" id="light-check" checked>
                        <label class="form-check-label" for="light-check">Light</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="theme" value="dark" id="dark-check">
                        <label class="form-check-label" for="dark-check">Dark</label>
                    </div>

                               
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="fixed" id="fixed-check" checked>
                        <label class="form-check-label" for="fixed-check">Fixed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="condensed" id="condensed-check">
                        <label class="form-check-label" for="condensed-check">Condensed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="scrollable" id="scrollable-check">
                        <label class="form-check-label" for="scrollable-check">Scrollable</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
            
                        
                    </div>
                </div> <!-- end padding-->

            </div>
        </div>

        <div class="rightbar-overlay"></div>
        <!-- /End-bar -->

        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>

        <!-- Right Sidebar -->
        <div class="end-bar">

            <div class="rightbar-title">
                <a href="javascript:void(0);" class="end-bar-toggle float-end">
                    <i class="dripicons-cross noti-icon"></i>
                </a>
                <h5 class="m-0">Settings</h5>
            </div>

            <div class="rightbar-content h-100" data-simplebar>

                <div class="p-3">
                    <div class="alert alert-warning" role="alert">
                        <strong>Customize </strong> the overall color scheme, sidebar menu, etc.
                    </div>

                    <!-- Settings -->
                    <h5 class="mt-3">Color Scheme</h5>
                    <hr class="mt-1" />

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="light" id="light-mode-check" checked>
                        <label class="form-check-label" for="light-mode-check">Light Mode</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="dark" id="dark-mode-check">
                        <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
                    </div>
       

                    <!-- Width -->
                    <h5 class="mt-4">Width</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="fluid" id="fluid-check" checked>
                        <label class="form-check-label" for="fluid-check">Fluid</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="boxed" id="boxed-check">
                        <label class="form-check-label" for="boxed-check">Boxed</label>
                    </div>
        

                    <!-- Left Sidebar-->
                    <h5 class="mt-4">Left Sidebar</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="default" id="default-check">
                        <label class="form-check-label" for="default-check">Default</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="light" id="light-check" checked>
                        <label class="form-check-label" for="light-check">Light</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="theme" value="dark" id="dark-check">
                        <label class="form-check-label" for="dark-check">Dark</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="fixed" id="fixed-check" checked>
                        <label class="form-check-label" for="fixed-check">Fixed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="condensed" id="condensed-check">
                        <label class="form-check-label" for="condensed-check">Condensed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="scrollable" id="scrollable-check">
                        <label class="form-check-label" for="scrollable-check">Scrollable</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
            
                        
                    </div>
                </div> <!-- end padding-->

            </div>
        </div>

        <div class="rightbar-overlay"></div>
        <!-- /End-bar -->


        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>

        <!-- third party js -->
        <script src="assets/js/vendor/jquery.dataTables.min.js"></script>
        <script src="assets/js/vendor/dataTables.bootstrap5.js"></script>
        <script src="assets/js/vendor/dataTables.responsive.min.js"></script>
        <script src="assets/js/vendor/responsive.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/dataTables.buttons.min.js"></script>
        <script src="assets/js/vendor/buttons.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/buttons.html5.min.js"></script>
        <script src="assets/js/vendor/buttons.flash.min.js"></script>
        <script src="assets/js/vendor/buttons.print.min.js"></script>
        <script src="assets/js/vendor/jszip.min.js"></script>
        <script src="assets/js/vendor/pdfmake.min.js"></script>
        <script src="assets/js/vendor/vfs_fonts.js"></script>
        <script src="assets/js/vendor/dataTables.keyTable.min.js"></script>
        <script src="assets/js/vendor/dataTables.select.min.js"></script>
        <script src="assets/js/vendor/fixedColumns.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/fixedHeader.bootstrap5.min.js"></script>
        <!-- third party js ends -->

        <!-- demo app -->
        <script src="assets/js/pages/demo.datatable-init.js"></script>
        <!-- end demo js-->

    </body>

</html>
