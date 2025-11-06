<?php require_once 'includes/config.php'; ?> <?php $ars = $db->select('ar', columns: '*'); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>AR Compass with 3D Tilt + Dots Trail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://aframe.io/releases/1.4.2/aframe.min.js"></script>
    <script src="https://rawcdn.githack.com/AR-js-org/AR.js/3.4.4/aframe/build/aframe-ar-nft.js"></script>
    <style>
      html,
      body {
        margin: 0;
        padding: 0;
        height: 100%;
        width: 100%;
        background: transparent;
        background-color: transparent;
        font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      }

      #location-bar {
        position: fixed;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10;
      }

      #location-bar select {
        border-radius: 6px;
        border: 1px solid #ccc;
        padding: 6px 10px;
        font-size: 14px;
      }

      #arrow-container {
        position: fixed;
        bottom: 6%;
        left: 50%;
        transform: translateX(-50%);
        width: 220px;
        height: 220px;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        perspective: 1400px;
      }

      #arrow-3d {
        width: 100%;
        height: 100%;
        transform-style: preserve-3d;
        will-change: transform;
        transition: transform 0.12s ease-out, filter 0.2s;
        filter: drop-shadow(0 12px 30px rgba(0, 0, 0, 0.55));
      }

      .compass-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(0, 0, 0, 0.08));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
        backdrop-filter: blur(4px);
        pointer-events: none;
        transform: translateZ(-20px) scale(1.02);
      }

      /* SVG arrow removed but keep circle/ticks */
      svg#arrow {
        width: 100%;
        height: 100%;
        transform-origin: 50% 50%;
        pointer-events: none;
      }

      #status {
        position: fixed;
        bottom: 2%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 14px;
        z-index: 30;
        backdrop-filter: blur(4px);
      }

      /* keep a-scene full-screen */
      a-scene {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        background: transparent !important;
        background-color: transparent !important;
      }

      /* ensure camera/video shows through and covers the viewport */
      a-scene video,
      video,
      .arjs-video {
        background: transparent !important;
        background-color: transparent !important;
        object-fit: cover;
        width: 100% !important;
        height: 100% !important;
      }

      /* extra rules to force AR.js / A-Frame video/canvas visible (avoid black) */
      .arjs-video,
      .arjs-video video,
      .a-canvas,
      canvas,
      video {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
        inset: 0;
        object-fit: cover;
        background: transparent !important;
        background-color: transparent !important;
        z-index: 0 !important; /* keep overlays above */
        pointer-events: none;
        -webkit-transform: translateZ(0); /* reduce black flicker on some mobiles */
      }

      #glow {
        position: absolute;
        width: 62%;
        height: 62%;
        left: 19%;
        top: 19%;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 20%, rgba(0, 200, 255, 0.16), transparent 40%);
        mix-blend-mode: screen;
        pointer-events: none;
        transform: translateZ(10px);
      }

      .dot {
        position: absolute;
        border-radius: 50%;
        background: #00ffca;
        pointer-events: none;
      }
    </style>
  </head>
  <body>
    <a-scene embedded vr-mode-ui="enabled: false" device-orientation-permission-ui="enabled: true" renderer="alpha: true; antialias: true; logarithmicDepthBuffer: true;" arjs="sourceType: webcam; videoTexture: true; debugUIEnabled: false;">
      <a-entity camera></a-entity>
      <a-entity light="type: ambient; intensity: 1"></a-entity>
    </a-scene>
    <div id="location-bar">
      <label>
        <b>Destination:</b>
      </label>
      <select id="destination">
        <?php foreach ($ars['data'] as $ar): ?>
          <option value="<?= htmlspecialchars($ar['gmapcoordinates']) ?>"><?= htmlspecialchars($ar['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="arrow-container">
      <div id="arrow-3d">
        <div class="compass-ring"></div>
        <div id="glow"></div>
        <svg id="arrow" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(100,100)">
            <circle r="90" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="2" />
            <g id="ticks"> <?php for($i=0;$i
								<360;$i+=10): ?>
              <rect x="-1" y="-90" width="2" height="
										<?= ($i%30===0)?14:7;?>" rx="1" transform="rotate(
										<?=$i?>)" fill="rgba(255,255,255,
										<?= ($i%30===0)?0.14:0.06;?>)" /> <?php endfor; ?>
            </g>
            <!-- needle group kept for rotation logic but arrow path removed -->
            <g id="needle" transform="translate(0,-18)">
              <!-- arrow removed as requested -->
            </g>
          </g>
        </svg>
        <!-- Dots container -->
        <div id="dots-container" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;"></div>
      </div>
    </div>
    <div id="status">Initializing...</div>
    <script>
      const destinations = <?php echo json_encode($ars['data']); ?>;
      let destination = (destinations && destinations.length) ? destinations[0] : null;
      let bearingToDest = 0;

      function getBearing(lat1, lon1, lat2, lon2) {
        const toRad = d => d * Math.PI / 180;
        const toDeg = r => r * 180 / Math.PI;
        const dLon = toRad(lon2 - lon1);
        const y = Math.sin(dLon) * Math.cos(toRad(lat2));
        const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLon);
        return (toDeg(Math.atan2(y, x)) + 360) % 360;
      }

      function updateBearing(position) {
        const { latitude, longitude } = position.coords;
        if (!destination || !destination.gmapcoordinates) return;
        const [destLat, destLng] = destination.gmapcoordinates.split(',').map(Number);
        bearingToDest = getBearing(latitude, longitude, destLat, destLng);
        document.getElementById('status').innerText = `Bearing to ${destination.name}: ${bearingToDest.toFixed(0)}°`;
      }

      if (navigator.geolocation) {
        navigator.geolocation.watchPosition(updateBearing, err => {
          Swal.fire({
            title: 'Location Error',
            text: 'Unable to get GPS.',
            icon: 'error'
          });
        }, {
          enableHighAccuracy: true,
          maximumAge: 0
        });
      }

      function normalizeDeg(d) {
        return ((d % 360) + 360) % 360;
      }

      const arrow3d = document.getElementById('arrow-3d');
      const svgNeedle = document.getElementById('needle');
      let state = {
        rotX: 0,
        rotY: 0,
        rotZ: 0,
        targetX: 0,
        targetY: 0,
        targetZ: 0,
        floatOffset: 0
      };

      function lerp(a, b, t) {
        return a + (b - a) * t;
      }

      function handleOrientation(e) {
        let heading = e.webkitCompassHeading || (typeof e.alpha === 'number' ? (360 - e.alpha) : null);
        if (heading == null) return;
        const beta = (typeof e.beta === 'number') ? e.beta : 0;
        const gamma = (typeof e.gamma === 'number') ? e.gamma : 0;
        const desiredZ = normalizeDeg(bearingToDest - heading);
        const desiredX = Math.max(-45, Math.min(45, -beta / 2));
        const desiredY = Math.max(-45, Math.min(45, gamma / 2));
        state.targetZ = desiredZ;
        state.targetX = desiredX;
        state.targetY = desiredY;
      }

      let lastTime = performance.now();
      const dotsContainer = document.getElementById('dots-container');

      function animate(now) {
        const dt = Math.min(60, now - lastTime) / 1000;
        lastTime = now;
        state.floatOffset = Math.sin(now * 0.0012) * 6;
        state.rotX = lerp(state.rotX, state.targetX + state.floatOffset * 0.12, 0.14);
        state.rotY = lerp(state.rotY, state.targetY, 0.14);
        let diffZ = state.targetZ - state.rotZ;
        if (diffZ > 180) diffZ -= 360;
        if (diffZ < -180) diffZ += 360;
        state.rotZ += diffZ * 0.18;

        arrow3d.style.transform = `rotateX(${state.rotX.toFixed(2)}deg) rotateY(${state.rotY.toFixed(2)}deg) scale(1.02)`;
        if (svgNeedle) svgNeedle.style.transform = `translateZ(20px) rotate(${state.rotZ.toFixed(2)}deg)`;

        // ✅ Draw enlarged dots trail
        dotsContainer.innerHTML = ''; // clear each frame
        const dotCount = 5;
        const sizes = [38, 32, 26, 18, 12]; // enlarged sizes, largest at back
        const spacing = 60; // increased spacing between dots
        const centerX = 100, centerY = 100; // center of SVG (viewBox 200x200)
        for (let i = 0; i < dotCount; i++) {
          const dot = document.createElement('div');
          dot.className = 'dot';
          dot.style.width = sizes[i] + 'px';
          dot.style.height = sizes[i] + 'px';
          const angleRad = (state.rotZ - 90) * Math.PI / 180;
          const offsetX = Math.cos(angleRad) * spacing * i;
          const offsetY = Math.sin(angleRad) * spacing * i;
          dot.style.left = (centerX + offsetX - sizes[i] / 2) + 'px';
          dot.style.top = (centerY + offsetY - sizes[i] / 2) + 'px';
          dot.style.opacity = (1 - i * 0.15).toString();
          dotsContainer.appendChild(dot);
        }

        requestAnimationFrame(animate);
      }

      requestAnimationFrame(animate);
      window.addEventListener("deviceorientationabsolute", handleOrientation, true);
      window.addEventListener("deviceorientation", handleOrientation, true);

      document.getElementById('destination').addEventListener('change', function() {
        // trim the value so accidental whitespace/newlines don't prevent a match
        const selected = (this.value || '').trim();
        destination = destinations.find(d => (d.gmapcoordinates || '').trim() === selected) || destinations.find(d => d.name === selected) || destination;
        Swal.fire({
          title: 'Destination Changed!',
          text: `Now pointing to ${destination ? destination.name : 'destination'}`,
          icon: 'info',
          timer: 1200,
          showConfirmButton: false
        });
      });

      // ensure camera video elements are inline/muted/autoplay (fixes black background on some devices)
      function fixCameraVideoAttrs() {
        document.querySelectorAll('video').forEach(v => {
          try {
            v.playsInline = true;
            v.muted = true;
            v.setAttribute('playsinline', '');
            v.setAttribute('muted', '');
            v.setAttribute('autoplay', '');
            v.style.background = 'transparent';
          } catch (e) {}
        });
      }
      // run after AR.js initializes
      window.addEventListener('load', () => setTimeout(fixCameraVideoAttrs, 800));
    </script>
  </body>
</html>