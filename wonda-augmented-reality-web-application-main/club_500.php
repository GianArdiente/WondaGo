<?php
// Admin management page for Club 500 requests
session_start();
require_once 'includes/config.php';

// Only admin (type == 1) can access this management page
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 1) {
    header('Location: login.php');
    exit;
}

// Handle actions: approve / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'approve' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        // fetch request row
        $r = $db->select('requests', '*', ['id' => $id]);
        if ($r['status'] === 'success' && !empty($r['data'][0])) {
            $req = $r['data'][0];
            $userId = $req['user_id'];
            // Update request to approved
            $u1 = $db->update('requests', ['status' => 1], ['id' => $id]);
            // Set user club_500 flag
            $u2 = $db->update('users', ['club_500' => 1], ['id' => $userId]);
            header('Location: club_500.php?approved=1');
            exit;
        } else {
            $error = 'Request not found.';
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $del = $db->delete('requests', ['id' => $id]);
        header('Location: club_500.php?deleted=1');
        exit;
    }
}

// Fetch requests and user map
$requestsRes = $db->select('requests');
$usersRes = $db->select('users', 'id, name, club_500');
$userMap = [];
if ($usersRes['status'] === 'success') {
    foreach ($usersRes['data'] as $u) {
        $userMap[$u['id']] = $u;
    }
}
$requests = ($requestsRes['status'] === 'success') ? $requestsRes['data'] : [];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Club 500 Requests — Admin</title>
    <?php require_once 'includes/head.php'; ?>
    <style>
        .badge-pending { background:#ffc107; color:#111; }
        .badge-approved { background:#28a745; color:#fff; }
    </style>
</head>

<body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
    <div class="wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Club 500 Requests</h4>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['approved'])): ?>
                        <div class="alert alert-success">Request approved & user updated.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-success">Request deleted.</div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="datatable-buttons" class="table table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>User</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($requests)): ?>
                                                    <tr><td colspan="5">No requests found.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($requests as $r): ?>
                                                        <tr>
                                                            <td><?php echo (int)$r['id']; ?></td>
                                                            <td>
                                                                <?php
                                                                    $u = $userMap[$r['user_id']] ?? null;
                                                                    echo $u ? htmlspecialchars($u['name']) . " (ID: ".htmlspecialchars($r['user_id']).")" : htmlspecialchars($r['user_id']);
                                                                    if ($u && isset($u['club_500']) && $u['club_500'] == 1) {
                                                                        echo ' <span class="badge bg-success">Member</span>';
                                                                    }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if ((int)$r['status'] === 1): ?>
                                                                    <span class="badge badge-approved">Approved</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-pending">Pending</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                                            <td>
                                                                <?php if ((int)$r['status'] === 0): ?>
                                                                    <form method="post" style="display:inline">
                                                                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                                        <input type="hidden" name="action" value="approve">
                                                                        <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Approve this request and set user as Club 500 member?')">Approve</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <form method="post" style="display:inline">
                                                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this request?')">Delete</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div> <!-- table-responsive -->
                                </div> <!-- card-body -->
                            </div> <!-- card -->
                        </div> <!-- col -->
                    </div> <!-- row -->

                </div> <!-- container -->
            </div> <!-- content -->

            <?php require 'includes/right-sidebar.php'; ?>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <script>document.write(new Date().getFullYear())</script> © WondaGo
                        </div>
                        <div class="col-md-6">
                            <div class="text-md-end footer-links d-none d-md-block">
                                <a href="javascript: void(0);">About</a>
                                <a href="javascript: void(0);">Support</a>
                                <a href="javascript: void(0);">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>

        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <!-- keep the same vendor/js includes as table.php for identical design/behavior -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/js/vendor/jquery.dataTables.min.js"></script>
    <script src="assets/js/vendor/dataTables.bootstrap5.js"></script>
    <script src="assets/js/vendor/dataTables.responsive.min.js"></script>
    <script src="assets/js/vendor/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/vendor/dataTables.buttons.min.js"></script>
    <script src="assets/js/vendor/buttons.bootstrap5.min.js"></script>
    <script src="assets/js/vendor/buttons.html5.min.js"></script>
    <script src="assets/js/vendor/buttons.flash.min.js"></script>
    <script src="assets/js/vendor/buttons.print.min.js"></script>
    <script src="assets/js/vendor/jszip.min.js"></script>
    <script src="assets/js/vendor/pdfmake.min.js"></script>
    <script src="assets/js/vendor/vfs_fonts.js"></script>
    <script src="assets/js/vendor/dataTables.keyTable.min.js"></script>
    <script src="assets/js/vendor/dataTables.select.min.js"></script>
    <script src="assets/js/vendor/fixedColumns.bootstrap5.min.js"></script>
    <script src="assets/js/vendor/fixedHeader.bootstrap5.min.js"></script>

 

    <script src="assets/js/pages/demo.datatable-init.js"></script>
</body>
</html>
