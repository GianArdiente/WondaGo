<?php
// Minimal PHP wrapper - change or remove includes as needed
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alhambra Kingdom Ballroom & Chapel — Mothers Wonderland</title>

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional: Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.4/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#0B9FCF;
      --accent:#A8FC59;
    }
    body { background:#fff; color:#111; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
    .hero {
      background: linear-gradient(0deg, rgba(0,0,0,0.46), rgba(0,0,0,0.46)), var(--accent);
      color:#fff;
    }
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.12); }
    .venue-title { font-family: "Average", sans-serif; font-weight:700; }
    footer { background:#f8f9fa; }
    .muted-sm { color: rgba(0,0,0,0.65); font-size:0.95rem; }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>

<?php
// changed code: resolve subscription by venue slug
require_once __DIR__ . '/database/function.php';
$db = new DBFunctions();
$venueSlug = $_GET['venue'] ?? '';
$subscription = null;

if ($venueSlug) {
    $allRes = $db->select('subscriptions','*',[],'ORDER BY id ASC');
    if ($allRes['status'] === 'success') {
        foreach ($allRes['data'] as $row) {
            $slug = strtolower(preg_replace('~[^\pL\d]+~u', '-', $row['name']));
            if ($slug === $venueSlug) {
                $subscription = $row;
                break;
            }
        }
    }
}

// Fallback: if not found and subscription_id provided, fetch by id
if (!$subscription && isset($_GET['subscription_id'])) {
    $byId = $db->select('subscriptions','*',['id' => $_GET['subscription_id']]);
    if ($byId['status'] === 'success' && !empty($byId['data'])) {
        $subscription = $byId['data'][0];
    }
}

if (!$subscription) {
    // minimal fallback content
    $subscription = [
        'name' => 'Alhambra Kingdom Ballroom & Chapel',
        'description' => 'Versatile and spacious enough to fit up to 300 guests...',
        'pax' => 1,
        'capacity' => 300,
        'price' => ''
    ];
}
?>

<!-- Hero -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Book memorable events at <?=htmlspecialchars($subscription['name'])?> — elegant, spacious, and scenic.</p>
        <div class="mt-4">
          <a href="#details" class="btn btn-primary btn-lg me-2">Venue Details</a>
          <a href="#inquiry" class="btn btn-outline-light btn-lg">Book / Inquire</a>
        </div>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="https://placehold.co/848x565" alt="<?=htmlspecialchars($subscription['name'])?>" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<main class="container my-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <h2 id="details" class="venue-title mb-3"><?=htmlspecialchars($subscription['name'])?></h2>
      <p class="text-muted mb-2"><strong>Capacity:</strong> <?=htmlspecialchars($subscription['capacity'] ?? 'N/A')?></p>

      <div class="card mb-4 shadow-sm">
        <div class="card-body">
          <p class="muted-sm" style="line-height:1.6;"><?=nl2br(htmlspecialchars($subscription['description']))?></p>

          <hr>

          <div class="row">
            <div class="col-md-6">
              <p class="mb-1"><strong>Tel:</strong> 042-373-3504</p>
              <p class="mb-0"><strong>Mobile:</strong> 0949-879-4919</p>
            </div>
            <div class="col-md-6 text-md-end">
              <a href="login.php?next=<?php echo urlencode('subscribe.php?subscription_id=' . ($subscription['id'] ?? '')); ?>" class="btn btn-success rounded-pill me-2">Book Now</a>
              <a href="venue-details.php?subscription_id=<?=urlencode($subscription['id'] ?? '')?>" class="btn btn-outline-secondary rounded-pill">More</a>
            </div>
          </div>
        </div>
      </div>

      <?php
      // Only show gallery when subscription type indicates a venue (type == 1)
      $isVenue = isset($subscription['type']) && (int)$subscription['type'] === 1;
      ?>

      <div class="card p-3 shadow-sm">
        <h6 class="mb-2">Venue Gallery</h6>
        <div class="row g-2">
          <div class="col-6"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6 mt-2"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6 mt-2"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
        </div>
      </div>
    </div>

    <aside class="col-lg-4">
      <div class="card p-4 mb-4 shadow-sm">
        <h5 class="mb-3">Quick Info</h5>
        <p class="small mb-2"><strong>Capacity:</strong> 300</p>
        <p class="small mb-2"><strong>Location:</strong> Mothers Wonderland Events Grounds</p>
        <p class="small mb-2"><strong>Phone:</strong> 042-373-3504</p>
        <p class="small mb-0"><strong>Mobile:</strong> 0949-879-4919</p>
        <hr>
        <a href="login.php?next=<?php echo urlencode('subscribe.php?subscription_id=' . ($subscription['id'] ?? '')); ?>" class="btn btn-danger w-100 rounded-pill mb-2">Book Venue</a>
        <a href="contact.php" class="btn btn-outline-secondary w-100 rounded-pill">Contact Events</a>
      </div>

      <div class="card p-3 shadow-sm">
        <h6 class="mb-2">Venue Gallery</h6>
        <div class="row g-2">
          <div class="col-6"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6 mt-2"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
          <div class="col-6 mt-2"><img src="https://placehold.co/300x200" class="img-fluid rounded" alt="gallery"></div>
        </div>
      </div>
    </aside>
  </div>
</main>

<!-- Inquiry / Contact form -->
<section id="inquiry" class="container mb-5">
  <div class="row">
    <div class="col-lg-8">
      <div class="card p-4 shadow-sm">
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

            <div class="col-md-6">
              <label class="form-label">Preferred Date</label>
              <input type="date" name="date" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label">Message / Requirements</label>
              <textarea name="message" rows="6" class="form-control" required></textarea>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-danger rounded-pill px-4">Send Inquiry</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <aside class="col-lg-4">
      <div class="card p-4 shadow-sm h-100">
        <h5 class="mb-3">Event Support</h5>
        <p class="small text-muted mb-2"><strong>Phone:</strong> 042-373-3504</p>
        <p class="small text-muted mb-2"><strong>Mobile:</strong> 0949-879-4919</p>
        <p class="small text-muted mb-0">Include preferred dates, guest count, setup and catering notes. We will respond with availability and pricing.</p>
        <hr>
        <a href="subscribe.php?venue=alhambra" class="btn btn-success w-100 rounded-pill mb-2">Start Booking</a>
        <a href="contact.php" class="btn btn-outline-secondary w-100 rounded-pill">Contact Support</a>
      </div>
    </aside>
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