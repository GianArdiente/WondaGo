<?php
// Minimal PHP wrapper - change or remove includes as needed
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact — Mothers Wonderland</title>

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
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.12); }
    footer { background:#f8f9fa; }
    .info-card { border: 1px solid rgba(0,0,0,0.06); }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>


<!-- Hero (aligned with index layout) -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Have questions? Reach out and we'll get back to you as soon as possible.</p>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="assets/images/contact.jpg" alt="Park" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Contact Info + Map -->
<section class="container my-5">
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card info-card p-4 h-100">
        <h3 class="mb-3">Contact Us</h3>
        <p class="mb-2"><strong>Operating Days:</strong> Thu - Sun & Holidays, 10:30am - 8:00pm</p>
        <p class="mb-2"><strong>Mobile:</strong> 0949-879-4919</p>
        <p class="mb-2"><strong>Email:</strong> <a href="mailto:enjoy@motherswonderland.com">enjoy@motherswonderland.com</a></p>
        <p class="mb-2"><strong>Address:</strong> Pan-Philippine Hwy, Tayabas, 4322 Quezon</p>
        <hr>
        <h5 class="mb-2">How to get here</h5>
        <p class="small text-muted">Located in Tayabas City along Maharlika Highway, less than 1 km from Sariaya and about 3 km from Lucena City. Entrance road to Grand Parking is beside WILCON Isabang.</p>
        <div class="mt-3">
          <a href="https://www.google.com/maps" target="_blank" class="btn btn-outline-primary btn-sm">View larger map</a>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card info-card h-100">
        <div class="ratio ratio-16x9">
          <iframe src="https://www.google.com/maps?q=Tayabas+Quezon&output=embed" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact Form -->
<section class="container mb-5">
  <div class="row">
    <div class="col-lg-8">
      <div class="card p-4">
        <h4 class="mb-3">Get in touch with us via Email</h4>
        <form action="#" method="post" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control" pattern="[0-9+\s()-]{7,20}">
            </div>

            <div class="col-12">
              <label class="form-label">Message</label>
              <textarea name="message" rows="6" class="form-control" required></textarea>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-danger rounded-pill px-4">Send</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-4 h-100">
        <h5>Visitor Information</h5>
        <p class="small text-muted">For group bookings, events, or special accessibility requests, please include details in your message and we will respond with availability and pricing.</p>
        <hr>
        <h6>Quick Contacts</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><i class="bi bi-telephone me-2"></i>0949-879-4919</li>
          <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:enjoy@motherswonderland.com">enjoy@motherswonderland.com</a></li>
          <li><i class="bi bi-geo-alt me-2"></i>Pan-Philippine Hwy, Tayabas, Quezon</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Footer (same style as index) -->
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