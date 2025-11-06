<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Directions Map</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
<style>
  html, body, #map { height: 100%; margin: 0; padding: 0; }
  .info-box {
    position: absolute;
    top: 60px; left: 10px;
    background: rgba(255,255,255,0.9);
    padding: 10px;
    border-radius: 6px;
    z-index: 1000;
    font-family: Arial, sans-serif;
    max-width: 250px;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  .leaflet-control-info {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 5px rgba(0,0,0,0.4);
    cursor: pointer;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ccc;
    margin-bottom: 4px;
  }
  .leaflet-control-info i { font-size: 20px; color: #007bff; }
  .speech-toggle {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
  }
</style>
</head>
<body>
<div id="map"></div>
<div class="info-box" id="infoBox"></div>
<button class="speech-toggle" id="speechToggle">🔊</button>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-rotatedmarker/leaflet.rotatedMarker.js"></script>

<script>
const urlParams = new URLSearchParams(window.location.search);
const toCoords = urlParams.get('to');
const toName = urlParams.get('name') || 'Destination';
if (!toCoords) { alert('No destination coordinates provided.'); throw new Error('Missing destination'); }
const [destLat, destLng] = toCoords.split(',').map(Number);

// --- BASE LAYERS ---
const map = L.map('map').setView([destLat, destLng], 18);

const googleRoadmap = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {maxZoom:20, subdomains:['mt0','mt1','mt2','mt3']});
const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {maxZoom:20, subdomains:['mt0','mt1','mt2','mt3']});
const googleTerrain = L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {maxZoom:20, subdomains:['mt0','mt1','mt2','mt3']});
const openStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19});
const openTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {maxZoom:17});
const cartoDB = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {maxZoom:19});

// Default layer
googleRoadmap.addTo(map);

// Layer control
L.control.layers({
  "Google Roadmap": googleRoadmap,
  "Google Satellite": googleSat,
  "Google Terrain": googleTerrain,
  "OpenStreetMap": openStreetMap,
  "OpenTopoMap": openTopoMap,
  "CartoDB Light": cartoDB
}, null, {position:'topleft'}).addTo(map);

// --- INFO CONTROL ---
L.Control.Info = L.Control.extend({
  onAdd: function(map) {
    const container = L.DomUtil.create('div','leaflet-control leaflet-control-info');
    container.title = "Show Info";
    container.innerHTML = '<i class="fa fa-info-circle"></i>';
    container.onclick = (e) => {
      e.stopPropagation();
      const infoBox = document.getElementById('infoBox');
      infoBox.style.display = infoBox.style.display==='none'?'block':'none';
    }
    return container;
  }
});
L.control.info = function(opts){return new L.Control.Info(opts);}
L.control.info({position:'topleft'}).addTo(map);

// --- MARKERS & ICON ---
const arrowIcon = L.icon({
  iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40">' +
    '<polygon points="20,0 35,35 20,28 5,35" fill="red"/>' +
    '</svg>'
  ),
  iconSize: [40,40],
  iconAnchor: [20,20]
});

let userMarker = null;
let currentHeading = 0;
const destMarker = L.marker([destLat,destLng]).addTo(map).bindPopup(toName).openPopup();
let routeLine = null;

// --- UTILITY ---
function haversine(lat1, lon1, lat2, lon2){
  const R = 6371;
  const dLat = (lat2-lat1)*Math.PI/180;
  const dLon = (lon2-lon1)*Math.PI/180;
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
  return 2*R*Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
function bearing(lat1, lon1, lat2, lon2){
  let dLon = (lon2-lon1)*Math.PI/180;
  lat1 = lat1*Math.PI/180;
  lat2 = lat2*Math.PI/180;
  let y = Math.sin(dLon)*Math.cos(lat2);
  let x = Math.cos(lat1)*Math.sin(lat2) - Math.sin(lat1)*Math.cos(lat2)*Math.cos(dLon);
  return (Math.atan2(y,x)*180/Math.PI + 360)%360;
}
function bearingToDirection(b){ 
  const dirs = ['North','North-East','East','South-East','South','South-West','West','North-West'];
  return dirs[Math.round(b/45)%8]; 
}
function estimateTime(distanceKm){
  const mins = Math.round((distanceKm/5)*60);
  return mins>=60? `${Math.floor(mins/60)} hr ${mins%60} min`: `${mins} min`;
}
function updateInfoBox(distanceKm, timeStr, directionStr){
  document.getElementById('infoBox').innerHTML = 
    `<b>Destination:</b> ${toName}<br>
     <b>Distance:</b> ${distanceKm.toFixed(2)} km<br>
     <b>Estimated Time:</b> ${timeStr}<br>
     <b>Direction:</b> Head ${directionStr}`;
}

// --- SMOOTH ROTATION ---
function rotateMarker(marker, targetAngle){
  if(!marker) return;
  // normalize to [-180,180]
  let startAngle = currentHeading;
  let diff = ((targetAngle - startAngle + 540) % 360) - 180;
  let duration = 250; // slightly longer for smoothness
  let startTime = null;
  function animate(ts){
    if(!startTime) startTime = ts;
    let progress = Math.min((ts-startTime)/duration,1);
    // ease (smoothstep)
    let t = progress*progress*(3-2*progress);
    let angle = startAngle + diff * t;
    marker.setRotationAngle(angle);
    if(progress<1) requestAnimationFrame(animate);
    else currentHeading = (startAngle + diff + 360) % 360;
  }
  requestAnimationFrame(animate);
}

// --- TEXT-TO-SPEECH ---
let speechEnabled = false;
document.getElementById('speechToggle').addEventListener('click', ()=>{
  if(!userMarker) return;
  speechEnabled = true; 
  const dist = haversine(userMarker.getLatLng().lat,userMarker.getLatLng().lng,destLat,destLng);
  const brng = bearing(userMarker.getLatLng().lat,userMarker.getLatLng().lng,destLat,destLng);
  const dirStr = bearingToDirection(brng);
  const timeStr = estimateTime(dist);
  speakOnce(`Head ${dirStr}. It will take you ${timeStr} to reach ${toName}, ${dist.toFixed(2)} kilometers away.`);
});

function speakOnce(text){
  if(!speechEnabled || !('speechSynthesis' in window)) return;
  window.speechSynthesis.cancel();
  let utter = new SpeechSynthesisUtterance(text);
  window.speechSynthesis.speak(utter);
  speechEnabled = false;
}

// --- POSITION ANIMATION ---
let posAnimation = null;
function animatePosition(marker, toLat, toLng, duration = 500){
  if(!marker) return;
  if(posAnimation && posAnimation.raf) cancelAnimationFrame(posAnimation.raf);
  const from = marker.getLatLng();
  const startLat = from.lat, startLng = from.lng;
  const startTimeRef = {t:null};
  posAnimation = {raf:null};
  function step(ts){
    if(!startTimeRef.t) startTimeRef.t = ts;
    let progress = Math.min((ts - startTimeRef.t)/duration, 1);
    // ease in-out
    let t = progress*progress*(3-2*progress);
    const lat = startLat + (toLat - startLat) * t;
    const lng = startLng + (toLng - startLng) * t;
    marker.setLatLng([lat, lng]);
    // update route line smoothly if present
    if(routeLine) routeLine.setLatLngs([[lat,lng],[destLat,destLng]]);
    if(progress < 1) posAnimation.raf = requestAnimationFrame(step);
    else {
      // ensure final position exact
      marker.setLatLng([toLat,toLng]);
      if(routeLine) routeLine.setLatLngs([[toLat,toLng],[destLat,destLng]]);
      posAnimation = null;
    }
  }
  posAnimation.raf = requestAnimationFrame(step);
}

// --- UPDATE ROUTE ---
function updateRoute(userLat,userLng){
  if(routeLine) routeLine.setLatLngs([[userLat,userLng],[destLat,destLng]]);
  else routeLine = L.polyline([[userLat,userLng],[destLat,destLng]],{color:'blue'}).addTo(map);
  const dist = haversine(userLat,userLng,destLat,destLng);
  const brng = bearing(userLat,userLng,destLat,destLng);
  const dirStr = bearingToDirection(brng);
  const timeStr = estimateTime(dist);
  updateInfoBox(dist,timeStr,dirStr);
}

// --- LOCATION TRACKING ---
function locateAndUpdate(){
  if(navigator.geolocation){
    let lastTs = 0;
    navigator.geolocation.watchPosition(pos=>{
      const now = Date.now();
      // throttle to at most ~5 updates/sec
      if(now - lastTs < 200) {
        // still compute route info but skip heavy moves
        lastTs = now;
      }
      lastTs = now;
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      if(!userMarker){
        userMarker = L.marker([lat,lng],{icon:arrowIcon, rotationAngle:0}).addTo(map);
        map.setView([lat,lng]); // initial center
      } else {
        // animate smoothly to new position
        animatePosition(userMarker, lat, lng, 400);
      }
      // update route info (distance, etc.) using the target pos
      updateRoute(lat,lng);

      // compute bearing and smoothly rotate toward it (so arrow points to destination)
      const brng = bearing(lat,lng,destLat,destLng);
      // keep using rotateMarker for smooth rotation
      rotateMarker(userMarker, brng);
    }, ()=>alert('Unable to get location.'),{enableHighAccuracy:true, maximumAge:500, timeout:5000});
  } else alert('Geolocation not supported.');
}
locateAndUpdate();

// --- DEVICE ORIENTATION ---
let headingSmooth = null;
window.addEventListener('deviceorientationabsolute', handleOrientation, true);
window.addEventListener('deviceorientation', handleOrientation, true);
function handleOrientation(e){
  if(!userMarker) return;
  let heading = e.alpha;
  if(typeof e.webkitCompassHeading !== "undefined") heading = e.webkitCompassHeading;
  if(heading == null) return;
  // convert device heading to map bearing (optional flip depending on device)
  let target = (360 - heading) % 360;
  if(headingSmooth == null) headingSmooth = target;
  // small alpha for low-pass filtering to reduce jitter
  const alpha = 0.12;
  // compute shortest delta
  let delta = ((target - headingSmooth + 540) % 360) - 180;
  headingSmooth = (headingSmooth + delta * alpha + 360) % 360;
  // only update rotation when meaningful change to avoid tiny jitters
  if(Math.abs(delta) > 0.7){
    rotateMarker(userMarker, headingSmooth);
  } else {
    // still set final small adjustments without animation
    userMarker.setRotationAngle(headingSmooth);
    currentHeading = headingSmooth;
  }
}
</script>
</body>
</html>
