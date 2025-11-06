<?php
// Minimal PHP wrapper - change or remove includes as needed
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parking — Mothers Wonderland</title>

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional: Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#0B9FCF;
      --accent:#A8FC59;
    }
    body { background:#fff; color:#111; }
    .hero {
      background: linear-gradient(0deg, rgba(0,0,0,0.46), rgba(0,0,0,0.46)), var(--accent);
      color:#fff;
    }
    .card-img-placeholder { background:#e9ecef; height:220px; display:flex; align-items:center; justify-content:center; color:#6c757d; }
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.25); }
    footer { background:#f8f9fa; }
    .hero-title { font-family: "Average", sans-serif; font-weight:700; }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>
 

<!-- Hero -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold hero-title">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Parking safety and guidelines — please follow these precautions to ensure a safe visit for everyone.</p>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="assets/images/parking.jpg" alt="Parking" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Quick Info bar -->
<section class="container my-4">
  <div class="row g-3">
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Parking Capacity</h6>
        <small class="text-muted">Large vehicle area available</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Entrance</h6>
        <small class="text-muted">Main gate - Maharlika Hwy</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Security</h6>
        <small class="text-muted">On-site personnel</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Accessible</h6>
        <small class="text-muted">Designated spots available</small>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="container mb-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <h2 class="mb-3">Parking Safety</h2>
      <h5 class="text-muted mb-4">Safety Precautions</h5>

      <div class="card p-4 mb-4">
        <p class="mb-0" style="line-height:1.6;">
          1. Supervise Children: Always keep children attended and within sight at all times.<br/><br/>
          2. Watch Your Step: Be cautious of slippery surfaces, especially when wet or on slopes. Use handrails for added safety.<br/><br/>
          3. Follow Safety Guidelines: Adhere to all safety instructions for activities such as ziplining, horseback riding, wall climbing, boating, climbing Gaia’s Peak, strolling on the Skywalk, and exploring the Treehouse. Your safety is our priority.<br/><br/>
          Enjoy responsibly!
        </p>
      </div>

      <div class="card mb-4">
        <div class="row g-0">
          <div class="col-md-5">
            <img src="assets/images/parking-2.jpg" class="img-fluid rounded-start" alt="parking area">
          </div>
          <div class="col-md-7">
            <div class="card-body">
              <h5 class="card-title">Parking Tips</h5>
              <ul>
                <li>Park within marked bays only.</li>
                <li>Lock your vehicle and do not leave valuables in plain sight.</li>
                <li>Follow directional signs and speed limits inside the complex.</li>
                <li>Report any suspicious activity to security immediately.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col-lg-4">
      <div class="card p-4 mb-4">
        <h5 class="mb-3">Quick Info</h5>
        <p class="small text-muted mb-2"><strong>Address:</strong> Pan-Philippine Hwy, Tayabas, Quezon</p>
        <p class="small text-muted mb-2"><strong>Mobile:</strong> 0949-879-4919</p>
        <p class="small text-muted mb-2"><strong>Hours:</strong> Thu - Sun & Holidays, 10:30am - 8:00pm</p>
        <a href="#" class="btn btn-outline-primary btn-sm">View full parking map</a>
      </div>

      <div class="card p-3">
        <h6 class="mb-3">Safety Reminders</h6>
        <ul class="small">
          <li>Use marked pedestrian crossings.</li>
          <li>Keep children close when crossing lanes.</li>
          <li>Observe signage for one-way lanes.</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Map / Contact CTA -->
<section class="container mb-5">
  <div class="row align-items-center">
    <div class="col-md-6 mb-3 mb-md-0">
      <h4>Need assistance?</h4>
      <p class="text-muted">Contact security for parking help or to report concerns.</p>
      <a href="contact.php" class="btn btn-outline-primary">Contact Us</a>
    </div>
    <div class="col-md-6">
      <div class="ratio ratio-16x9 rounded overflow-hidden">
        <iframe src="https://www.google.com/maps?q=Tayabas+Quezon&output=embed" style="border:0;" loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
        <small class="text-muted">&copy; <?=date('Y')?> Mothers Wonderland. All rights reserved.</small>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
        <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
        <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS (bundle) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>