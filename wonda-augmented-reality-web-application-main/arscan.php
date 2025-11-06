<?php require_once 'includes/config.php'; ?>  

<?php 
$ars = $db->select('ar', columns: '*');

?>
<html>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script src="https://aframe.io/releases/1.4.2/aframe.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.2/dist/mindar-image-aframe.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  </head>
<body>
  <!-- Sticky Location Dropdowns -->
  <div id="location-dropdowns" style="
    position: fixed;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 100000;
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    padding: 12px 18px;
    display: flex;
    gap: 16px;
    align-items: center;
    font-family: sans-serif;
  ">
    <label style="font-weight:600;">Current:</label>
    <select id="current-location" style="margin-right:8px; padding:4px 8px; border-radius:6px; border:1px solid #ccc; "></select>
    <a id="directions-btn">Find Location</a>
  </div>
  
  <div id="ar-container"></div>

  <!-- Scanning Overlay -->
  <div id="example-scanning-overlay" class="hidden">
    <div class="inner">
      <img src="./assets/card-example/card.png" />
      <div class="scanline"></div>
    </div>
  </div>

<script>
  const arData = <?php echo json_encode($ars['data']); ?>;
  console.log(arData);

  // Populate dropdowns
  function populateDropdowns() {
    const currentSel = document.getElementById('current-location');
    currentSel.innerHTML = '';

    arData.forEach((point, idx) => {
      const opt = document.createElement('option');
      opt.value = idx;
      opt.textContent = point.name;
      currentSel.appendChild(opt);
    });

    currentSel.selectedIndex = 0;
  }

  // Register your custom component FIRST
  AFRAME.registerComponent('mytarget', {
    init: function () {
      console.log('[DEBUG] mytarget component init on', this.el);
      this.el.addEventListener('targetFound', (e) => {
        console.log('[DEBUG] mytarget: targetFound event', e);
        setTimeout(() => showInfo(), 300);
      });
      this.el.addEventListener('targetLost', (e) => {
        console.log('[DEBUG] mytarget: targetLost event', e);
      });
    }
  });

  function loadARScene(arPath) {
    const sceneHTML = `
      <a-scene id="ar-scene"
        mindar-image="imageTargetSrc: ${arPath}; showStats: false; uiScanning: #example-scanning-overlay;"
        embedded
        color-space="sRGB"
        renderer="colorManagement: true, physicallyCorrectLights"
        vr-mode-ui="enabled: false"
        device-orientation-permission-ui="enabled: false"
        style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:1;background:transparent"
      >
        <a-assets>
          <img id="card" src="./assets/card-example/card.png" />
          <img id="icon-web" src="./assets/card-example/icons/web.png" />
          <img id="icon-location" src="./assets/card-example/icons/location.png" />
          <img id="icon-profile" src="./assets/card-example/icons/profile.png" />
          <img id="icon-phone" src="./assets/card-example/icons/phone.png" />
          <img id="icon-email" src="./assets/card-example/icons/email.png" />
          <img id="icon-play" src="./assets/card-example/icons/play.png" />
          <img id="icon-left" src="./assets/card-example/icons/left.png" />
          <img id="icon-right" src="./assets/card-example/icons/right.png" />
          <img id="paintandquest-preview" src="./assets/card-example/portfolio/paintandquest-preview.png" />
          <video id="paintandquest-video-mp4" autoplay="false" loop="true" src="./assets/card-example/portfolio/paintandquest.mp4"></video>
          <video id="paintandquest-video-webm" autoplay="false" loop="true" src="./assets/card-example/portfolio/paintandquest.webm"></video>
          <img id="coffeemachine-preview" src="./assets/card-example/portfolio/coffeemachine-preview.png" />
          <img id="peak-preview" src="./assets/card-example/portfolio/peak-preview.png" />
        </a-assets>

        <a-camera position="0 0 0" look-controls="enabled: false"
          cursor="fuse: false; rayOrigin: mouse;"
          raycaster="far: 10000; objects: .clickable">
        </a-camera>

        <a-entity id="mytarget" mytarget mindar-image-target="targetIndex: 0">
          <a-plane src="#card" position="0 0 0" height="0.5" width="0.5" rotation="0 0 0"></a-plane>

          <a-image visible="false" id="profile-button" class="clickable" src="#icon-profile" position="-0.42 -0.5 0" height="0.15" width="0.15"
            animation="property: scale; to: 1.2 1.2 1.2; dur: 1000; easing: easeInOutQuad; loop: true; dir: alternate"
          ></a-image>

          <a-image visible="false" id="web-button" class="clickable" src="#icon-web" position="-0.14 -0.5 0" height="0.15" width="0.15"
            animation="property: scale; to: 1.2 1.2 1.2; dur: 1000; easing: easeInOutQuad; loop: true; dir: alternate"
          ></a-image>

          <a-image visible="false" id="email-button" class="clickable" src="#icon-email" position="0.14 -0.5 0" height="0.15" width="0.15"
            animation="property: scale; to: 1.2 1.2 1.2; dur: 1000; easing: easeInOutQuad; loop: true; dir: alternate"
          ></a-image>

          <a-image visible="false" id="location-button" class="clickable" src="#icon-location" position="0.42 -0.5 0" height="0.15" width="0.15"
            animation="property: scale; to: 1.2 1.2 1.2; dur: 1000; easing: easeInOutQuad; loop: true; dir: alternate"
          ></a-image>
        </a-entity>
      </a-scene>
    `;

    const container = document.getElementById('ar-container');
    container.innerHTML = '';
    container.insertAdjacentHTML('beforeend', sceneHTML);
    console.log('[DEBUG] AR scene inserted with target', arPath);

    // Wait for scene to load then register click events and MindAR listeners
    setTimeout(() => {
      const scene = document.querySelector("#ar-scene");
      if (scene) {
        scene.addEventListener("loaded", () => {
          console.log('[DEBUG] AR scene loaded (a-scene "loaded" event)');
          const mindarComp = scene.components && scene.components['mindar-image'];
          console.log('[DEBUG] mindar-image component on scene:', !!mindarComp);
        });

        scene.addEventListener('targetFound', (ev) => {
          console.log('[DEBUG] scene targetFound', ev);
        });
        scene.addEventListener('targetLost', (ev) => {
          console.log('[DEBUG] scene targetLost', ev);
        });

        setTimeout(() => {
          const targetEntity = scene.querySelector('[mindar-image-target]');
          if (targetEntity) {
            console.log('[DEBUG] found mindar-image-target entity', targetEntity);
            targetEntity.addEventListener('targetFound', (e) => {
              console.log('[DEBUG] mindar-image-target: targetFound', e);
              showInfo();
            });
            targetEntity.addEventListener('targetLost', (e) => {
              console.log('[DEBUG] mindar-image-target: targetLost', e);
            });
          } else {
            console.warn('[DEBUG] no mindar-image-target element found yet');
          }
        }, 400);
      } else {
        console.warn('[DEBUG] #ar-scene not found after insertion');
      }
    }, 500);
  }

  // Find Location button: redirect to findlocation.php with selected location coords and name
  document.getElementById('directions-btn').addEventListener('click', function() {
    const currentSel = document.getElementById('current-location');
    const idx = parseInt(currentSel.value, 10);

    if (isNaN(idx)) {
      Swal.fire({ title: 'Please select a location.', icon: 'warning', confirmButtonText: 'OK' });
      return;
    }

    const coords = arData[idx].gmapcoordinates;
    const name = arData[idx].name;

    window.location.href = `findlocation.php?to=${encodeURIComponent(coords)}&name=${encodeURIComponent(name)}`;
  });

  function showInfo() {
    // query buttons scoped INSIDE the AR scene to avoid selecting DOM elements outside
    const sceneRoot = document.querySelector('#ar-scene') || document;
    const profileButton = sceneRoot.querySelector("#profile-button");
    const webButton = sceneRoot.querySelector("#web-button");
    const emailButton = sceneRoot.querySelector("#email-button");
    const locationButton = sceneRoot.querySelector("#location-button");

    console.log('[DEBUG] showInfo called — buttons found:', {
      profile: !!profileButton,
      web: !!webButton,
      email: !!emailButton,
      location: !!locationButton
    });

    if (profileButton) profileButton.setAttribute("visible", true);
    if (webButton) setTimeout(() => webButton.setAttribute("visible", true), 300);
    if (emailButton) setTimeout(() => emailButton.setAttribute("visible", true), 600);
    if (locationButton) setTimeout(() => locationButton.setAttribute("visible", true), 900);

    if (webButton) webButton.addEventListener('click', () => window.open("https://motherswonderland.com/", "_blank"));
    if (emailButton) emailButton.addEventListener('click', () => {
      const email = "support@motherswonderland.com";
      if (navigator.clipboard) {
        navigator.clipboard.writeText(email).then(() => {
          Swal.fire({ title: 'Email copied!', text: email, icon: 'success', confirmButtonText: 'OK' });
        }).catch(() => {
          Swal.fire({ title: 'Copy failed', icon: 'error', confirmButtonText: 'OK' });
        });
      }
    });
    if (profileButton) profileButton.addEventListener('click', () => window.location.href = "calendar.php");
    if (locationButton) locationButton.addEventListener('click', () => {
      const currentSel = document.getElementById('current-location');
      const idx = parseInt(currentSel.value, 10);
      if (isNaN(idx)) return;

      const coords = arData[idx].gmapcoordinates;
      const name = arData[idx].name;
      window.location.href = `findlocation.php?to=${encodeURIComponent(coords)}&name=${encodeURIComponent(name)}`;
    });

    console.log('[DEBUG] Buttons shown (if present)');
  }

  function changeARPath(point) {
    if (!point || !point.ar_path) return;
    loadARScene(point.ar_path);
  }
  function setupDropdownEvents() {
    const currentSel = document.getElementById('current-location');

    // When current location changes, update AR scene only
    currentSel.addEventListener('change', () => {
      const currentIdx = parseInt(currentSel.value, 10);
      changeARPath(arData[currentIdx]);
    });
  }


  window.onload = () => {
    populateDropdowns();
    setupDropdownEvents();
    // Load AR for default current selection
    const currentSel = document.getElementById('current-location');
    const idx = currentSel.selectedIndex;
    changeARPath(arData[idx]);
  };

  // Removed trail/navigation and compass code to focus on scanning only
</script>

<?php include 'arstyle.php'; ?>

</body>

</html>