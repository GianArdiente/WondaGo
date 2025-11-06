<?php
session_start();
require_once 'includes/config.php';

// Handle join action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join') {
    if (!isset($_SESSION['user'])) {
        // Not logged in — send to login and preserve return
        header('Location: login.php?next=' . urlencode('club.php'));
        exit;
    }
    $userId = $_SESSION['user']['id'];

    // Check if already member
    $u = $db->select('users', 'id, club_500', ['id' => $userId]);
    if ($u['status'] === 'success' && !empty($u['data'][0]) && (int)$u['data'][0]['club_500'] === 1) {
        header('Location: club.php?already_member=1');
        exit;
    }

    // Check if pending request exists
    $existing = $db->select('requests', '*', ['user_id' => $userId, 'status' => 0]);
    if ($existing['status'] === 'success' && !empty($existing['data'])) {
        header('Location: club.php?pending=1');
        exit;
    }

    // Insert new request (status defaults to 0)
    $ins = $db->insert('requests', ['user_id' => $userId, 'status' => 0]);
    if ($ins['status'] === 'success') {
        header('Location: club.php?requested=1');
        exit;
    } else {
        $error = 'Failed to submit request: ' . ($ins['message'] ?? 'Unknown error');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Club 500 — Mothers Wonderland</title>

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
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
    .card-preview { border: 1px solid rgba(0,0,0,0.06); background:#fff; }
    .price-badge { font-size:1.25rem; font-weight:700; color: var(--brand); }
    .muted-sm { color: rgba(0,0,0,0.65); font-size:0.95rem; }
    .venue-title { font-family: "Average", serif; font-weight:700; }
    footer { background:#f8f9fa; }
  </style>
</head>
<body>

<?php include 'topbar-user.php'; ?>

<div class="container py-4">
  <?php if (isset($_GET['requested'])): ?>
    <div class="alert alert-success">Request submitted. We'll notify you when approved.</div>
  <?php elseif (isset($_GET['pending'])): ?>
    <div class="alert alert-info">You already have a pending Club 500 request.</div>
  <?php elseif (isset($_GET['already_member'])): ?>
    <div class="alert alert-success">You are already a Club 500 member.</div>
  <?php elseif (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
</div>

<!-- Hero -->
<section class="hero py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7 text-lg-start text-center">
        <h1 class="display-6 fw-bold">Club 500 — Privilege Card</h1>
        <p class="lead mt-3">Enjoy exclusive perks and discounted entrance — pay only ₱500 per visit for one year. Your Club 500 card is printed onsite upon entry.</p>
      </div>

      <div class="col-lg-5 text-center mt-4 mt-lg-0">
        <img src="https://placehold.co/420x280" alt="Club 500" class="img-fluid rounded shadow-sm">
      </div>
    </div>
  </div>
</section>

<!-- Quick Info bar -->
<section class="container my-4">
  <div class="row g-3">
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Card Fee</h6>
        <small class="text-muted">₱500 / visit (1 year)</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Printed Onsite</h6>
        <small class="text-muted">Picture taken for card</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Replacements</h6>
        <small class="text-muted">Available for a fee</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 rounded-pill-shadow bg-white text-center">
        <h6 class="mb-0">Validity</h6>
        <small class="text-muted">1 year from issue</small>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="container mb-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <h2 id="benefits" class="venue-title mb-3">Benefits</h2>
      <p class="muted-sm">Club 500 members receive entrance fee discounts and prioritized access for selected offerings. Additional privileges and partner discounts will be announced to members.</p>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="mb-3">What you get</h5>
          <ul>
            <li>Entrance fee discount – pay only ₱500 per visit for 1 year</li>
            <li>Printed membership card with your name and QR code</li>
            <li>Priority lane for admissions</li>
            <li>Early access to select events and promos</li>
            <li>Member-only announcements and partner offers</li>
          </ul>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="mb-3">Automatic Issuance</h5>
          <p class="muted-sm">A free Club 500 Privilege Card is issued automatically upon entry. We'll take your photo for printing on the card — bring a valid ID for verification.</p>
          <p class="mb-0">For group or bulk memberships, contact our events team for arrangements and pricing.</p>
        </div>
      </div>

      <div id="how-to-get" class="card mb-4">
        <div class="card-body">
          <h5 class="mb-3">How to get your Club 500 Card</h5>
          <ol class="muted-sm">
            <li>Visit Mothers Wonderland during operating days (Thu - Sun & holidays).</li>
            <li>Proceed to the membership booth at the main entrance.</li>
            <li>Have your photo taken and present a valid ID for printing.</li>
            <li>Pay the ₱500 fee to activate membership for one year.</li>
          </ol>
          <a href="contact.php" class="btn btn-outline-secondary btn-sm">Need assistance?</a>
        </div>
      </div>
    </div>

    <aside class="col-lg-4">
      <div class="card card-preview p-4 mb-4 shadow-sm">
        <div class="text-center mb-3">
          <div class="badge bg-white text-success rounded-pill px-3 py-2">Club 500</div>
        </div>

        <!-- <div class="position-relative" style="min-height:260px;">
          <div class="bg-light rounded-3 p-3" style="height:260px;">
            <div class="d-flex align-items-center">
              <img src="https://placehold.co/127x127" alt="member" class="rounded-circle me-3" width="96" height="96">
              <div>
                <div class="h5 mb-1 text-success">Aaron Noche</div>
                <div class="small text-muted">Valid until Sept. <?=date('Y')+1?></div>
              </div>
            </div>

            <div class="mt-3 text-center">
              <div class="d-inline-block bg-dark text-white p-3 rounded mb-2" style="width:96px; height:96px; line-height:1;">
                <div style="font-size:18px; line-height:1;">QR</div>
              </div>
              <div class="small text-muted d-block">Member QR code</div>
            </div>
          </div>
        </div> -->

        <div class="mt-3">
          <?php
            $isMember = false;
            $hasPending = false;
            if (isset($_SESSION['user'])) {
                $u = $db->select('users', 'id, club_500', ['id' => $_SESSION['user']['id']]);
                if ($u['status'] === 'success' && !empty($u['data'][0])) {
                    $isMember = (int)$u['data'][0]['club_500'] === 1;
                }
                $p = $db->select('requests', '*', ['user_id' => $_SESSION['user']['id'], 'status' => 0]);
                if ($p['status'] === 'success' && !empty($p['data'])) $hasPending = true;
            }
          ?>
          <?php if ($isMember): ?>
            <button class="btn btn-success w-100 rounded-pill mb-2" disabled>Member</button>
          <?php elseif ($hasPending): ?>
            <button class="btn btn-outline-secondary w-100 rounded-pill mb-2" disabled>Request Pending</button>
          <?php else: ?>
            <form method="post" action="club.php">
              <input type="hidden" name="action" value="join">
              <button type="submit" class="btn btn-danger w-100 rounded-pill mb-2">Join Club 500</button>
            </form>
          <?php endif; ?>

          <a href="contact.php" class="btn btn-outline-secondary w-100 rounded-pill">Contact Support</a>
        </div>
      </div>

      <div class="card p-3 shadow-sm">
        <h6 class="mb-2">Quick Info</h6>
        <p class="small mb-1"><strong>Fee:</strong> ₱500 / visit (1 year)</p>
        <p class="small mb-1"><strong>Printed:</strong> Onsite upon entry</p>
        <p class="small mb-0"><strong>Requirements:</strong> Photo, valid ID</p>
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