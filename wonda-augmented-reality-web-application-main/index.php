<?php
// Add session and database functionality
session_start();
require_once __DIR__ . '/database/function.php';
$db = new DBFunctions();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);

// Fetch active news only (not expired)
$currentDate = date('Y-m-d');
$newsResult = $db->custom('news', '*', [], "end_date >= '$currentDate' ORDER BY featured DESC, created_at DESC");
$newsItems = ($newsResult['status'] === 'success') ? $newsResult['data'] : [];

// Fetch subscriptions for ticket links
$subscriptionsResult = $db->select('subscriptions', '*', ['type' => 0], 'ORDER BY id ASC LIMIT 1');
$ticketSubscription = ($subscriptionsResult['status'] === 'success' && !empty($subscriptionsResult['data'])) ? $subscriptionsResult['data'][0] : null;

// Helper function for booking URLs
function getBookingUrl($subscriptionId, $isLoggedIn) {
    if ($isLoggedIn) {
        return 'subscribe.php?subscription_id=' . urlencode($subscriptionId);
    } else {
        $next = 'subscribe.php?subscription_id=' . urlencode($subscriptionId);
        return 'login.php?next=' . urlencode($next);
    }
}

// Helper function to format dates
function formatDateRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $now = new DateTime();
    
    if ($now > $end) {
        return '<span class="text-danger">Expired</span>';
    } elseif ($now < $start) {
        return '<span class="text-warning">Upcoming</span>';
    } else {
        return '<span class="text-success">Active</span>';
    }
}

// Get available homepage images
$homepageImages = [];
$homepageDir = __DIR__ . '/assets/images/Homepage/';
if (is_dir($homepageDir)) {
    $files = scandir($homepageDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $homepageImages[] = $file;
        }
    }
}

// Check if Park Map exists
$parkMapExists = file_exists($homepageDir . 'Park Map.jpg');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mothers Wonderland</title>

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
      background: linear-gradient(0deg, rgba(0,0,0,0.46), rgba(0,0,0,0.46)), url('assets/images/Homepage/1200x420.png');
      background-size: cover;
      background-position: center;
      color:#fff;
      min-height: 70vh;
    }
    .card-img-placeholder { background:#e9ecef; height:220px; display:flex; align-items:center; justify-content:center; color:#6c757d; }
    .rounded-pill-shadow { border-radius:9999px; box-shadow:0 6px 4px rgba(0,0,0,0.25); }
    footer { background:#f8f9fa; }
    .news-carousel-item {
      height: 420px;
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .news-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(transparent, rgba(0,0,0,0.8));
      color: white;
      padding: 2rem;
    }
    .featured-badge {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: var(--brand);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
    }
    .feature-card {
      transition: transform 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .feature-card:hover {
      transform: translateY(-5px);
    }
    .feature-card .stretched-link::after {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      z-index: 1;
      content: "";
    }
    .feature-card .card-img-top {
      transition: transform 0.3s ease;
    }
    .feature-card:hover .card-img-top {
      transform: scale(1.05);
    }
    .park-map-container {
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    .park-map-container:hover {
      transform: scale(1.02);
    }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>

<!-- Hero -->
<section class="hero py-5 d-flex align-items-center">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Open every Thursday through Sunday, and on holidays!</h1>
        <p class="lead mt-3">Our wonderful park map invites you to chart your own adventure. Explore at your own pace and uncover the beauty of nature on your terms.</p>
        <div class="mt-4">
          <?php if ($ticketSubscription): ?>
            <a href="<?= getBookingUrl($ticketSubscription['id'], $isLoggedIn) ?>" class="btn btn-primary btn-lg me-2">Buy Tickets</a>
          <?php else: ?>
            <a href="rates.php" class="btn btn-primary btn-lg me-2">Buy Tickets</a>
          <?php endif; ?>
          <a href="events-list.php" class="btn btn-outline-light btn-lg">Learn More</a>
        </div>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <?php if (count($homepageImages) > 1): ?>
          <img src="assets/images/Homepage/360x240.jpg" alt="Mothers Wonderland" class="img-fluid rounded shadow-lg" style="max-height: 400px;">
        <?php else: ?>
          <img src="https://placehold.co/360x240" alt="Park" class="img-fluid rounded shadow-sm">
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
 <!-- Quick Info bar -->
<section class="container my-5">
  <div class="row g-3">
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center h-100 d-flex flex-column justify-content-center">
        <i class="bi bi-calendar-week text-primary mb-2" style="font-size: 2rem;"></i>
        <h6 class="mb-1">Opening Days</h6>
        <small class="text-muted">Thu - Sun & Holidays</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center h-100 d-flex flex-column justify-content-center">
        <i class="bi bi-ticket-perforated text-success mb-2" style="font-size: 2rem;"></i>
        <h6 class="mb-1">Tickets</h6>
        <small class="text-muted">Online & Onsite</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center h-100 d-flex flex-column justify-content-center">
        <i class="bi bi-shield-check text-warning mb-2" style="font-size: 2rem;"></i>
        <h6 class="mb-1">Safety</h6>
        <small class="text-muted">Park Safety Guidelines</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center h-100 d-flex flex-column justify-content-center">
        <i class="bi bi-headset text-info mb-2" style="font-size: 2rem;"></i>
        <h6 class="mb-1">Support</h6>
        <small class="text-muted">Contact Us</small>
      </div>
    </div>
  </div>
</section>

<!-- News & Promotions Carousel -->
<?php if (!empty($newsItems)): ?>
<section class="container mb-5">
  <div class="row mb-3">
    <div class="col">
      <h2 class="text-center">Latest News & Promotions</h2>
      <p class="text-center text-muted">Stay updated with our latest offers and announcements</p>
    </div>
  </div>
  
  <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner rounded">
      <?php foreach ($newsItems as $index => $news): ?>
        <?php
        $photos = !empty($news['photo']) ? explode(',', $news['photo']) : [];
        $firstPhoto = !empty($photos) ? trim($photos[0]) : '';
        
        // Use news photo if available, otherwise use homepage images as fallback
        if (!empty($firstPhoto)) {
            $imageUrl = "assets/images/news/{$firstPhoto}";
        } elseif (!empty($homepageImages)) {
            $imageUrl = "assets/images/Homepage/" . $homepageImages[array_rand($homepageImages)];
        } else {
            $imageUrl = "https://placehold.co/1200x420";
        }
        ?>
        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
          <div class="news-carousel-item" style="background-image: url('<?= htmlspecialchars($imageUrl) ?>');">
            
            <?php if ($news['featured']): ?>
              <div class="featured-badge">
                <i class="bi bi-star-fill"></i> Featured
              </div>
            <?php endif; ?>
            
            <div class="news-overlay">
              <div class="row align-items-end">
                <div class="col-md-8">
                  <h3 class="fw-bold"><?= htmlspecialchars($news['name']) ?></h3>
                  <p class="mb-2"><?= htmlspecialchars($news['description']) ?></p>
                  <small class="opacity-75">
                    <?= formatDateRange($news['start_date'], $news['end_date']) ?>
                    • Valid: <?= date('M j', strtotime($news['start_date'])) ?> - <?= date('M j, Y', strtotime($news['end_date'])) ?>
                  </small>
                </div>
                <div class="col-md-4 text-end">
                  <?php if (!empty($news['discount'])): ?>
                    <div class="bg-success text-white px-3 py-2 rounded mb-2">
                      <small>Special Discounts Available!</small>
                    </div>
                  <?php endif; ?>
                  <?php if ($ticketSubscription): ?>
                    <a href="<?= getBookingUrl($ticketSubscription['id'], $isLoggedIn) ?>" class="btn btn-light btn-lg">
                      <i class="bi bi-ticket-perforated"></i> Book Now
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <?php if (count($newsItems) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
      <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
    
    <!-- Carousel indicators -->
    <?php if (count($newsItems) > 1): ?>
    <div class="carousel-indicators">
      <?php foreach ($newsItems as $index => $news): ?>
        <button type="button" data-bs-target="#newsCarousel" data-bs-slide-to="<?= $index ?>" 
                <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                aria-label="Slide <?= $index + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- News Cards Section -->
<?php if (!empty($newsItems)): ?>
<section class="container mb-5">
  <div class="row mb-3">
    <div class="col">
      <h3 class="text-center">All Promotions & Updates</h3>
    </div>
  </div>
  
  <div class="row g-4">
    <?php foreach (array_slice($newsItems, 0, 3) as $news): ?>
      <?php
      $photos = !empty($news['photo']) ? explode(',', $news['photo']) : [];
      $firstPhoto = !empty($photos) ? trim($photos[0]) : '';
      
      // Use news photo if available, otherwise use homepage images as fallback
      if (!empty($firstPhoto)) {
          $imageUrl = "assets/images/news/{$firstPhoto}";
      } elseif (!empty($homepageImages)) {
          $imageUrl = "assets/images/Homepage/" . $homepageImages[array_rand($homepageImages)];
      } else {
          $imageUrl = "https://placehold.co/400x250";
      }
      ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="position-relative">
            <img src="<?= htmlspecialchars($imageUrl) ?>" class="card-img-top" alt="<?= htmlspecialchars($news['name']) ?>" style="height: 200px; object-fit: cover;">
            
            <?php if ($news['featured']): ?>
              <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                <i class="bi bi-star-fill"></i> Featured
              </span>
            <?php endif; ?>
            
            <div class="position-absolute top-0 start-0 m-2">
              <?= formatDateRange($news['start_date'], $news['end_date']) ?>
            </div>
          </div>
          
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($news['name']) ?></h5>
            <p class="card-text flex-grow-1"><?= htmlspecialchars($news['description']) ?></p>
            
            <?php if (!empty($news['discount'])): ?>
              <div class="alert alert-success py-2 mb-2">
                <small><i class="bi bi-tag-fill"></i> Special discounts available!</small>
              </div>
            <?php endif; ?>
            
            <div class="card-footer bg-transparent border-0 px-0 pb-0">
              <small class="text-muted">
                Valid: <?= date('M j', strtotime($news['start_date'])) ?> - <?= date('M j, Y', strtotime($news['end_date'])) ?>
              </small>
              
              <?php if ($ticketSubscription): ?>
                <div class="mt-2">
                  <a href="<?= getBookingUrl($ticketSubscription['id'], $isLoggedIn) ?>" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-ticket-perforated"></i> Book with Promo
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  
  <?php if (count($newsItems) > 3): ?>
    <div class="text-center mt-4">
      <a href="rates.php" class="btn btn-outline-primary">View All Promotions</a>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- Updated feature card section -->
<section class="container mb-5">
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card h-100 feature-card">
        <a href="assets/images/Homepage/family attractions.jpg" target="_blank" class="image-link">
          <img src="assets/images/Homepage/family attractions.jpg" class="card-img-top" alt="Family Attractions" style="height: 200px; object-fit: cover;">
        </a>
        <div class="card-body">
          <h5 class="card-title">Family Attractions</h5>
          <p class="card-text small text-muted">Fun rides, playgrounds, and relaxing picnic spots for the whole family.</p>
          <a href="events-list.php" class="btn btn-sm btn-primary">Explore</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 feature-card">
        <a href="assets/images/Homepage/Events and shows.jpg" target="_blank" class="image-link">
          <img src="assets/images/Homepage/Events and shows.jpg" class="card-img-top" alt="Events & Shows" style="height: 200px; object-fit: cover;">
        </a>
        <div class="card-body">
          <h5 class="card-title">Events & Shows</h5>
          <p class="card-text small text-muted">Seasonal events, performances, and special celebrations.</p>
          <a href="events-list.php" class="btn btn-sm btn-primary">View events</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 feature-card">
        <a href="assets/images/Homepage/Membership.jpg" target="_blank" class="image-link">
          <img src="assets/images/Homepage/Membership.jpg" class="card-img-top" alt="Membership" style="height: 200px; object-fit: cover;">
        </a>
        <div class="card-body">
          <h5 class="card-title">Membership</h5>
          <p class="card-text small text-muted">Join Club 500 for exclusive perks and discounts.</p>
          <a href="rates.php" class="btn btn-sm btn-primary">Join now</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Park Map Section -->
<section class="container mb-5">
  <div class="row align-items-center">
    <div class="col-md-6 mb-3 mb-md-0">
      <h3>Park Map</h3>
      <p class="text-muted">See the park layout and plan your visit. Click to open the interactive map.</p>
      <?php if ($parkMapExists): ?>
        <a href="assets/images/Homepage/Park Map.jpg" target="_blank" class="btn btn-outline-primary">
          <i class="bi bi-map"></i> Open Map
        </a>
      <?php else: ?>
        <a href="#" class="btn btn-outline-primary">
          <i class="bi bi-map"></i> Open Map
        </a>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <div class="park-map-container">
        <?php if ($parkMapExists): ?>
          <a href="assets/images/Homepage/Park Map.jpg" target="_blank">
            <img src="assets/images/Homepage/Park Map.jpg" 
                 alt="Park Map" 
                 class="img-fluid rounded shadow-sm w-100" 
                 style="max-height: 400px; object-fit: contain;">
          </a>
        <?php else: ?>
          <div class="ratio ratio-16x9 rounded overflow-hidden">
            <iframe src="https://placehold.co/800x450" style="border:0;" loading="lazy"></iframe>
          </div>
        <?php endif; ?>
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