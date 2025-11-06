<?php
// Minimal PHP wrapper - change or remove includes as needed
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Events & Venues — Mothers Wonderland</title>

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
      background: linear-gradient(0deg, rgba(0,0,0,0.46), rgba(0,0,0,0.46)), url('assets/images/Events/491363683_1200636678738141_2041642355239108331_n.jpg');
      background-size: cover;
      background-position: center;
      color:#fff;
      min-height: 70vh;
    }
    .card-img-placeholder { background:#e9ecef; height:220px; display:flex; align-items:center; justify-content:center; color:#6c757d; }
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.12); }
    .venue-title { font-family: "Average", sans-serif; font-weight:700; }
    footer { background:#f8f9fa; }
    .event-image {
      height: 220px;
      object-fit: cover;
      width: 100%;
    }
    .card-img-overlay {
      background: rgba(0,0,0,0.3);
      transition: all 0.3s ease;
    }
    .card:hover .card-img-overlay {
      background: rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>

<?php
require_once __DIR__ . '/database/function.php';
$db = new DBFunctions();
// Fetch venues (type 1)
$venuesRes = $db->select('subscriptions', '*', ['type' => 1], 'ORDER BY id ASC');
$venues = ($venuesRes['status'] === 'success') ? $venuesRes['data'] : [];
// Fetch events (type 2)
$eventsRes = $db->select('subscriptions', '*', ['type' => 2], 'ORDER BY id DESC');
$events = ($eventsRes['status'] === 'success') ? $eventsRes['data'] : [];
function slugify($text){ $text = preg_replace('~[^\pL\d]+~u', '-', $text); $text = iconv('utf-8','us-ascii//TRANSLIT',$text); $text = preg_replace('~[^-\w]+~', '', $text); $text = trim($text,'-'); $text = strtolower($text); return $text ?: 'n-a'; }
?>

<!-- Hero -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Browse our event venues and book spaces for meetings, celebrations, or private gatherings.</p>
      </div>
      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="assets/images/Events/DJI_0845_1024x1024.jpg" alt="Events" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Quick Info -->
<section class="container my-4">
  <div class="row g-3">
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <i class="bi bi-people-fill fs-2 text-primary mb-2"></i>
        <h6 class="mb-0">Capacity</h6>
        <small class="text-muted">Small to Large Venues</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <i class="bi bi-cup-straw fs-2 text-success mb-2"></i>
        <h6 class="mb-0">Catering</h6>
        <small class="text-muted">On-request options</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <i class="bi bi-layout-text-sidebar-reverse fs-2 text-warning mb-2"></i>
        <h6 class="mb-0">Setup</h6>
        <small class="text-muted">Custom layouts available</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <i class="bi bi-calendar-check fs-2 text-danger mb-2"></i>
        <h6 class="mb-0">Bookings</h6>
        <small class="text-muted">Reserve online</small>
      </div>
    </div>
  </div>
</section>

<!-- Venues list -->
<section id="venues" class="container mb-5">
  <h3 class="mb-4">Venues</h3>
  <div class="row g-4">
    <?php if (empty($venues)): ?>
      <div class="col-12"><div class="alert alert-info">No venues available.</div></div>
    <?php else: ?>
      <?php foreach ($venues as $key => $v): $venue_slug = slugify($v['name']);
            $cardImg = !empty($v['image']) ? 'assets/images/subscriptions/' . $v['image'] : 'https://placehold.co/750x500';
            $next = 'subscribe.php?venue=' . urlencode($venue_slug) . '&subscription_id=' . urlencode($v['id']);
            $loginHref = 'login.php?next=' . urlencode($next);
      ?>
        <div class="col-lg-4">
          <div class="card h-100 shadow-sm overflow-hidden">
            <img src="<?=htmlspecialchars($cardImg)?>" class="card-img-top event-image" alt="<?=htmlspecialchars($v['name'])?>">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title venue-title"><?=htmlspecialchars($v['name'])?></h5>
              <p class="text-muted mb-2"><?=nl2br(htmlspecialchars(substr($v['description'],0,120)))?><?=strlen($v['description'])>120 ? '...' : ''?></p>
              <p class="mb-2"><strong>Capacity:</strong> <?=htmlspecialchars($v['capacity'] ?? 'N/A')?></p>
              <div class="mt-auto d-flex gap-2">
                <a href="login.php?next=<?php echo urlencode('subscribe.php?subscription_id=' . $v['id']); ?>" class="btn btn-success rounded-pill">Book Now</a>
                <a href="event.php?venue=<?=urlencode($venue_slug)?>&subscription_id=<?=urlencode($v['id'])?>" class="btn btn-outline-secondary">Details</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>


<!-- Contact / Booking CTA -->
<section id="contact" class="container mb-5">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card p-4">
        <h4 class="mb-3">Get in touch / Booking Inquiry</h4>
        <form action="booking-inquiry.php" method="post" novalidate>
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
              <label class="form-label">Preferred Venue</label>
              <select name="venue" class="form-select">
                <?php foreach ($venues as $v): ?>
                <option value="<?=htmlspecialchars(slugify($v['name']))?>"><?=htmlspecialchars($v['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Message / Requirements</label>
              <textarea name="message" rows="5" class="form-control"></textarea>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-danger rounded-pill px-4">Send Inquiry</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <aside class="col-lg-5">
      <div class="card p-4 h-100">
        <h5 class="mb-3">Event Support</h5>
        <p class="small text-muted mb-2"><strong>Phone:</strong> 0949-879-4919</p>
        <p class="small text-muted mb-2"><strong>Email:</strong> <a href="mailto:events@motherswonderland.com">events@motherswonderland.com</a></p>
        <p class="small text-muted mb-0">Provide your preferred dates, guest count, setup requirements and any catering requests. We will follow up with availability and pricing.</p>
        <hr>
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