<?php
// Topbar include with auth-aware links + redirects
if (session_status() === PHP_SESSION_NONE) session_start();

// Handle logout action (use link: ?logout=1)
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Redirect already-logged-in users away from login page
$user = $_SESSION['user'] ?? null;
$currentFile = basename($_SERVER['PHP_SELF']);
if ($currentFile === 'login.php' && $user) {
    if (isset($user['type']) && $user['type'] == 1) {
        header('Location: dashboard.php');
    } else {
        header('Location: calendar.php');
    }
    exit;
}

// Helper for safe output
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="assets/images/logo.png" alt="Logo" height="48" class="me-2">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="rates.php">Tickets</a></li>
        <li class="nav-item"><a class="nav-link" href="events-list.php">Events</a></li>
        <li class="nav-item"><a class="nav-link" href="club.php">Club 500</a></li>
        <li class="nav-item"><a class="nav-link" href="parking.php">Park Safety</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>

        <?php if ($user): ?>
          <li class="nav-item dropdown ms-3">
            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= esc($user['name'] ?? $user['email'] ?? 'User') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <?php if (isset($user['type']) && $user['type'] == 1): ?>
                <li><a class="dropdown-item" href="dashboard.php">Admin Dashboard</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="calendar.php">My Calendar</a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= esc($_SERVER['PHP_SELF']) ?>?logout=1">Sign out</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-3">
            <a href="pages-register.php" class="btn btn-outline-primary btn-sm rounded-pill">Create account</a>
          </li>
          <li class="nav-item ms-2">
            <a href="login.php" class="btn btn-link text-primary">Sign in</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>