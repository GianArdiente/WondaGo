<?php
session_start();
require_once 'includes/config.php';

// Require login
if (!isset($_SESSION['user'])) {
    header('Location: login.php?next=' . urlencode('ratings.php'));
    exit;
}

$userId = $_SESSION['user']['id'];
$userType = $_SESSION['user']['type'];

// Check if user has any transactions (booking history)
$hasTransactions = false;
$txRes = $db->select('transactions', 'id', ['user_id' => $userId]);
if ($txRes['status'] === 'success' && !empty($txRes['data'])) {
    $hasTransactions = true;
}

$error = '';
$success = '';

// Handle submission (only regular users submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    // Only non-admin users can insert ratings through this page
    if ($userType == 1) {
        $error = 'Admins cannot submit ratings here.';
    } else {
        // New: require booking history
        if (!$hasTransactions) {
            $error = 'You must have at least one booking before submitting feedback.';
        } else {
            $stars = isset($_POST['stars']) ? intval($_POST['stars']) : 0;
            $rating = isset($_POST['rating']) ? trim($_POST['rating']) : '';

            if ($stars < 1 || $stars > 5) {
                $error = 'Please select a valid star rating (1-5).';
            } elseif (strlen($rating) < 5) {
                $error = 'Please provide a rating message (at least 5 characters).';
            } else {
                // Check if user already submitted (no updates allowed)
                $existing = $db->select('feedback', '*', ['user_id' => $userId]);
                if ($existing['status'] === 'success' && !empty($existing['data'])) {
                    $error = 'You have already submitted a rating. Updates are not allowed.';
                } else {
                    $ins = $db->insert('feedback', [
                        'user_id' => $userId,
                        'stars' => $stars,
                        'rating' => $rating,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    if ($ins['status'] === 'success') {
                        // Redirect to avoid resubmission
                        header('Location: ratings.php?submitted=1');
                        exit;
                    } else {
                        $error = 'Failed to submit rating: ' . ($ins['message'] ?? 'Unknown error');
                    }
                }
            }
        }
    }
}

// Fetch user's feedback (to detect if already submitted)
$userFeedback = null;
$ufRes = $db->select('feedback', '*', ['user_id' => $userId]);
if ($ufRes['status'] === 'success' && !empty($ufRes['data'][0])) {
    $userFeedback = $ufRes['data'][0];
}

// If admin, fetch all feedbacks for display
$allFeedback = [];
if ($userType == 1) {
    $fbRes = $db->select('feedback');
    if ($fbRes['status'] === 'success') {
        $allFeedback = $fbRes['data'];
        // get user map
        $uids = array_column($allFeedback, 'user_id');
        if (!empty($uids)) {
            $usersRes = $db->select('users', ['id', 'name'], ['id' => $uids]);
            $userMap = [];
            if ($usersRes['status'] === 'success') {
                foreach ($usersRes['data'] as $u) $userMap[$u['id']] = $u['name'];
            }
        } else {
            $userMap = [];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>User Ratings — Mothers Wonderland</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding:20px; }
    .stars { color: #f0ad4e; }
  </style>
</head>
<body>
<?php include 'topbar-user.php'; ?>

<div class="container py-4">
  <h3>User Ratings</h3>

  <?php if (isset($_GET['submitted'])): ?>
    <div class="alert alert-success">Thank you — your rating was submitted.</div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if ($userType == 1): ?>
    <!-- Admin: show all feedbacks -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">All Ratings</h5>
        <?php if (empty($allFeedback)): ?>
          <div class="alert alert-info">No ratings yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Stars</th>
                  <th>Rating</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allFeedback as $f): ?>
                  <tr>
                    <td><?php echo (int)$f['id']; ?></td>
                    <td><?php echo htmlspecialchars($userMap[$f['user_id']] ?? $f['user_id']); ?></td>
                    <td class="stars"><?php echo str_repeat('★', max(0,(int)$f['stars'])) . str_repeat('☆', 5 - max(0,(int)$f['stars'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($f['rating'])); ?></td>
                    <td><?php echo htmlspecialchars($f['created_at'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <!-- Regular user: allow submit if not already submitted -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Submit Your Rating</h5>

        <?php if (!empty($userFeedback)): ?>
          <div class="alert alert-info">
            You already submitted a rating. Thank you!
            <div class="mt-2"><strong>Stars:</strong> <span class="stars"><?php echo str_repeat('★', max(0,(int)$userFeedback['stars'])) . str_repeat('☆', 5 - max(0,(int)$userFeedback['stars'])); ?></span></div>
            <div class="mt-2"><strong>Message:</strong><div><?php echo nl2br(htmlspecialchars($userFeedback['rating'])); ?></div></div>
            <div class="mt-2 text-muted small">Submitted at <?php echo htmlspecialchars($userFeedback['created_at'] ?? ''); ?></div>
          </div>
        <?php else: ?>
          <form method="post" action="ratings.php">
            <input type="hidden" name="action" value="submit">
            <div class="mb-3">
              <label class="form-label">Stars</label>
              <select name="stars" class="form-select" required>
                <option value="" disabled selected>Select stars</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Very Good</option>
                <option value="3">3 - Good</option>
                <option value="2">2 - Fair</option>
                <option value="1">1 - Poor</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Your feedback</label>
              <textarea name="rating" class="form-control" rows="4" required placeholder="Tell us about your experience..."></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Submit Rating</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <p><a href="index.php" class="btn btn-secondary btn-sm">Back to Dashboard</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>